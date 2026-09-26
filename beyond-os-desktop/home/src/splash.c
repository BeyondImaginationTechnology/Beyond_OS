/* Beyond OS framebuffer splash. Original source: MIT; artwork separately reserved. */
#define _POSIX_C_SOURCE 200809L
#include <errno.h>
#include <fcntl.h>
#include <linux/fb.h>
#include <stdint.h>
#include <signal.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/ioctl.h>
#include <sys/mman.h>
#include <time.h>
#include <unistd.h>

static volatile sig_atomic_t stop_animation;

static void request_stop(int signal_number)
{
    (void)signal_number;
    stop_animation = 1;
}

static uint32_t channel(unsigned value, struct fb_bitfield field)
{
    if (!field.length || field.length > 8 || field.offset > 31) return 0;
    return (value >> (8 - field.length)) << field.offset;
}

static void put_pixel(unsigned char *pixels, size_t memory_size,
                      const struct fb_var_screeninfo *v,
                      const struct fb_fix_screeninfo *f, unsigned bytes,
                      unsigned x, unsigned y, unsigned r, unsigned g, unsigned b)
{
    if (x >= v->xres || y >= v->yres) return;
    uint32_t color = channel(r, v->red) | channel(g, v->green) |
                     channel(b, v->blue) | channel(255, v->transp);
    size_t at = ((size_t)y + v->yoffset) * f->line_length +
                ((size_t)x + v->xoffset) * bytes;
    if (at + bytes > memory_size) return;
    for (unsigned byte = 0; byte < bytes; byte++)
        pixels[at + byte] = (unsigned char)(color >> (byte * 8));
}

static void draw_dot(unsigned char *pixels, size_t memory_size,
                     const struct fb_var_screeninfo *v,
                     const struct fb_fix_screeninfo *f, unsigned bytes,
                     unsigned left, unsigned top, unsigned draw_w, unsigned draw_h,
                     unsigned width, unsigned height, int center_x,
                     unsigned r, unsigned g, unsigned b)
{
    for (int dy = -2; dy <= 2; dy++) {
        for (int dx = -2; dx <= 2; dx++) {
            if (dx * dx + dy * dy > 5) continue;
            unsigned sx = (unsigned)(center_x + dx);
            unsigned sy = (unsigned)(311 + dy);
            unsigned x = left + (unsigned)((uint64_t)sx * draw_w / width);
            unsigned y = top + (unsigned)((uint64_t)sy * draw_h / height);
            put_pixel(pixels, memory_size, v, f, bytes, x, y, r, g, b);
        }
    }
}

int main(int argc, char **argv)
{
    if (argc != 2) { fprintf(stderr, "Usage: beyond-splash image.ppm\n"); return 2; }
    FILE *file = fopen(argv[1], "rb");
    unsigned width, height, maximum;
    if (!file) return 1;
    if (fscanf(file, "P6\n%u %u\n%u", &width, &height, &maximum) != 3 ||
        maximum != 255 || !width || !height || width > 4096 || height > 4096 ||
        fgetc(file) != '\n') { fclose(file); return 1; }
    size_t size = (size_t)width * height * 3;
    unsigned char *image = malloc(size);
    if (!image) { fclose(file); return 1; }
    size_t count = fread(image, 1, size, file);
    fclose(file);
    if (count != size) { free(image); return 1; }

    int fd = open("/dev/fb0", O_RDWR);
    struct fb_var_screeninfo v;
    struct fb_fix_screeninfo f;
    if (fd < 0) { free(image); return 1; }
    if (ioctl(fd, FBIOGET_VSCREENINFO, &v) || ioctl(fd, FBIOGET_FSCREENINFO, &f) ||
        (v.bits_per_pixel != 16 && v.bits_per_pixel != 24 && v.bits_per_pixel != 32) ||
        !v.xres || !v.yres || !f.smem_len || v.red.msb_right ||
        v.green.msb_right || v.blue.msb_right || f.visual != FB_VISUAL_TRUECOLOR) {
        close(fd); free(image); return 1;
    }
    unsigned char *pixels = mmap(NULL, f.smem_len, PROT_READ | PROT_WRITE, MAP_SHARED, fd, 0);
    if (pixels == MAP_FAILED) { close(fd); free(image); return 1; }
    unsigned bytes = v.bits_per_pixel / 8;
    /* Scale down only, and center at every supported framebuffer resolution. */
    unsigned draw_w = width, draw_h = height;
    if (draw_w > v.xres) { draw_h = (unsigned)((uint64_t)draw_h * v.xres / draw_w); draw_w = v.xres; }
    if (draw_h > v.yres) { draw_w = (unsigned)((uint64_t)draw_w * v.yres / draw_h); draw_h = v.yres; }
    if (!draw_w || !draw_h) { munmap(pixels, f.smem_len); close(fd); free(image); return 1; }
    unsigned left = (v.xres - draw_w) / 2, top = (v.yres - draw_h) / 2;
    for (unsigned y = 0; y < v.yres; y++) {
        for (unsigned x = 0; x < v.xres; x++) {
            unsigned r = 9, g = 13, b = 22;
            if (x >= left && x < left + draw_w && y >= top && y < top + draw_h) {
                unsigned sx = (unsigned)((uint64_t)(x - left) * width / draw_w);
                unsigned sy = (unsigned)((uint64_t)(y - top) * height / draw_h);
                size_t at = ((size_t)sy * width + sx) * 3;
                r = image[at]; g = image[at + 1]; b = image[at + 2];
            }
            uint32_t color = channel(r, v.red) | channel(g, v.green) |
                             channel(b, v.blue) | channel(255, v.transp);
            size_t at = ((size_t)y + v.yoffset) * f.line_length + ((size_t)x + v.xoffset) * bytes;
            if (at + bytes > f.smem_len) continue;
            for (unsigned byte = 0; byte < bytes; byte++)
                pixels[at + byte] = (unsigned char)(color >> (byte * 8));
        }
    }
    /* Pulse the emerald loading dots until the graphical session takes over. */
    signal(SIGTERM, request_stop);
    signal(SIGINT, request_stop);
    while (!stop_animation) {
        for (int active = 0; active < 3; active++) {
            if (stop_animation) break;
            for (int dot = 0; dot < 3; dot++) {
                unsigned r = dot == active ? 112 : 28;
                unsigned g = dot == active ? 236 : 61;
                unsigned b = dot == active ? 164 : 48;
                draw_dot(pixels, f.smem_len, &v, &f, bytes, left, top,
                         draw_w, draw_h, width, height, 307 + dot * 13,
                         r, g, b);
            }
            struct timespec pulse = {0, 110000000};
            while (nanosleep(&pulse, &pulse) && errno == EINTR && !stop_animation) {}
        }
    }
    for (int dot = 0; dot < 3; dot++)
        draw_dot(pixels, f.smem_len, &v, &f, bytes, left, top,
                 draw_w, draw_h, width, height, 307 + dot * 13,
                 145, 222, 166);
    munmap(pixels, f.smem_len);
    close(fd);
    free(image);
    return 0;
}
