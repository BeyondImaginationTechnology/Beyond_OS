/* Beyond OS Home: native development desktop. */
#define _POSIX_C_SOURCE 200809L
#define SDL_MAIN_HANDLED
#include <SDL.h>
#include <SDL_ttf.h>
#include <dirent.h>
#include <errno.h>
#include <limits.h>
#include <math.h>
#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/stat.h>
#include <time.h>
#ifdef _WIN32
#include <windows.h>
#else
#include <signal.h>
#include <sys/types.h>
#include <sys/utsname.h>
#include <unistd.h>
#endif

#define NOTE_CAP 8192
#define ENTRY_CAP 512
#define PATH_CAP 4096
#define W 1280
#define H 800

enum Page { HOME, FILES, NOTES, ABOUT, VIEWER };
typedef struct { char name[256]; bool directory; } Entry;
static SDL_Renderer *renderer;
static TTF_Font *small_font, *font, *title_font;
static enum Page page = HOME;
static char note[NOTE_CAP], status[256], directory[PATH_CAP], note_path[PATH_CAP];
static char file_text[NOTE_CAP], file_title[256];
static Entry entries[ENTRY_CAP];
static int entry_count, scroll, selected;
static bool dirty, note_writable = true;
static const SDL_Color white = {239, 242, 255, 255};
static const SDL_Color muted = {153, 170, 195, 255};
static const SDL_Color accent = {150, 174, 255, 255};
static const char *titles[] = {"Files", "Notes", "Terminal", "About Beyond OS"};
static const char *subtitles[] = {"Explore your computer", "A place to put your thoughts",
                                  "Your Linux command line", "Your system, at a glance"};

static void box(int x, int y, int w, int h, int r, int g, int b)
{
    SDL_Rect rect = {x, y, w, h};
    SDL_SetRenderDrawColor(renderer, (Uint8)r, (Uint8)g, (Uint8)b, 255);
    SDL_RenderFillRect(renderer, &rect);
}

static void text(TTF_Font *face, const char *value, int x, int y, SDL_Color color)
{
    if (!*value) return;
    SDL_Surface *surface = TTF_RenderUTF8_Blended(face, value, color);
    if (!surface) return;
    SDL_Texture *texture = SDL_CreateTextureFromSurface(renderer, surface);
    if (texture) {
        SDL_Rect dest = {x, y, surface->w, surface->h};
        SDL_RenderCopy(renderer, texture, NULL, &dest);
        SDL_DestroyTexture(texture);
    }
    SDL_FreeSurface(surface);
}

static void paragraph(const char *value, int x, int y, int width, int height)
{
    if (!*value) return;
    SDL_Surface *surface = TTF_RenderUTF8_Blended_Wrapped(font, value, white, (Uint32)width);
    if (!surface) return;
    SDL_Texture *texture = SDL_CreateTextureFromSurface(renderer, surface);
    if (texture) {
        int shown = surface->h < height ? surface->h : height;
        int top = page == NOTES && surface->h > height ? surface->h - height : 0;
        SDL_Rect source = {0, top, surface->w, shown};
        SDL_Rect dest = {x, y, surface->w, shown};
        SDL_RenderCopy(renderer, texture, &source, &dest);
        SDL_DestroyTexture(texture);
    }
    SDL_FreeSurface(surface);
}

static void orbit(int cx, int cy, double radius)
{
    for (int ring = 0; ring < 3; ring++) {
        double angle = ring * 3.141592653589793 / 3.0;
        for (int i = 0; i < 180; i++) {
            double a = i * 6.283185307179586 / 180.0;
            double b = (i + 1) * 6.283185307179586 / 180.0;
            double ax = radius * cos(a), ay = radius * 0.345 * sin(a);
            double bx = radius * cos(b), by = radius * 0.345 * sin(b);
            SDL_SetRenderDrawColor(renderer, (Uint8)(105 + i / 3), 150, 250, 255);
            SDL_RenderDrawLine(renderer, cx + (int)(ax*cos(angle)-ay*sin(angle)),
                              cy + (int)(ax*sin(angle)+ay*cos(angle)),
                              cx + (int)(bx*cos(angle)-by*sin(angle)),
                              cy + (int)(bx*sin(angle)+by*cos(angle)));
        }
    }
    box(cx - 2, cy - 5, 4, 10, 219, 215, 255);
}

static bool join_path(char *dest, size_t size, const char *base, const char *name)
{
    int count = snprintf(dest, size, "%s%s%s", base,
                         *base && base[strlen(base)-1] == '/' ? "" : "/", name);
    return count >= 0 && (size_t)count < size;
}

static int compare_entry(const void *a, const void *b)
{
    const Entry *one = a, *two = b;
    if (one->directory != two->directory) return one->directory ? -1 : 1;
    return strcmp(one->name, two->name);
}

static void load_directory(void)
{
    entry_count = scroll = 0;
    DIR *dir = opendir(directory);
    if (!dir) { snprintf(status, sizeof status, "Cannot open folder: %s", strerror(errno)); return; }
    struct dirent *entry;
    while ((entry = readdir(dir)) && entry_count < ENTRY_CAP) {
        if (!strcmp(entry->d_name, ".") || !strcmp(entry->d_name, "..")) continue;
        if (entry->d_name[0] == '.') continue;
        char path[PATH_CAP];
        struct stat info;
        if (!join_path(path, sizeof path, directory, entry->d_name) || stat(path, &info)) continue;
        snprintf(entries[entry_count].name, sizeof entries[entry_count].name, "%s", entry->d_name);
        entries[entry_count++].directory = S_ISDIR(info.st_mode);
    }
    closedir(dir);
    qsort(entries, (size_t)entry_count, sizeof entries[0], compare_entry);
    snprintf(status, sizeof status, "%d items%s. Select a folder or UTF-8 text file.",
             entry_count, entry_count == ENTRY_CAP ? " (first 512 shown)" : "");
}

static bool save_note(void)
{
    if (!note_writable) return false;
    char temporary[PATH_CAP];
    int n = snprintf(temporary, sizeof temporary, "%s.tmp", note_path);
    if (n < 0 || (size_t)n >= sizeof temporary) return false;
    FILE *file = fopen(temporary, "wb");
    if (!file) { snprintf(status, sizeof status, "Save failed: %s", strerror(errno)); return false; }
    size_t len = strlen(note);
    bool okay = fwrite(note, 1, len, file) == len && fflush(file) == 0;
#ifndef _WIN32
    if (okay && fsync(fileno(file))) okay = false;
#endif
    if (fclose(file)) okay = false;
    #ifdef _WIN32
    bool replaced = okay && MoveFileExA(temporary, note_path, MOVEFILE_REPLACE_EXISTING | MOVEFILE_WRITE_THROUGH);
#else
    bool replaced = okay && rename(temporary, note_path) == 0;
#endif
    if (!replaced) {
        snprintf(status, sizeof status, "Save failed; your text is still here.");
        remove(temporary);
        return false;
    }
    dirty = false;
    snprintf(status, sizeof status, "Saved to Documents/Home Note.txt");
    return true;
}

static void go_home(void)
{
    if (page == NOTES && dirty && !save_note()) return;
    page = HOME;
    SDL_StopTextInput();
    status[0] = 0;
}

static void launch_terminal(void)
{
#ifndef _WIN32
    pid_t child = fork();
    if (child == 0) {
        execlp("xterm", "xterm", "-T", "Beyond Terminal", "-bg", "#090d16",
               "-fg", "#eff2ff", "-fa", "DejaVu Sans Mono", "-fs", "12", (char *)NULL);
        _exit(127);
    }
    if (child < 0) snprintf(status, sizeof status, "Could not open terminal: %s", strerror(errno));
    else snprintf(status, sizeof status, "Terminal opened. Alt+Tab switches windows.");
#else
    snprintf(status, sizeof status, "The terminal is available in the Linux image.");
#endif
}

static void activate(int card)
{
    if (card == 0) { page = FILES; load_directory(); }
    else if (card == 1) { if (!note_writable) { snprintf(status, sizeof status, "Notes could not load the existing file (unreadable or over 8 KB). It has not been changed."); return; } page = NOTES; SDL_StartTextInput(); snprintf(status, sizeof status, "Type a note. Ctrl+S saves. Home also saves before leaving."); }
    else if (card == 2) launch_terminal();
    else { page = ABOUT; status[0] = 0; }
}

static void open_entry(int index)
{
    if (index < 0 || index >= entry_count) return;
    char path[PATH_CAP];
    if (!join_path(path, sizeof path, directory, entries[index].name)) return;
    if (entries[index].directory) {
        snprintf(directory, sizeof directory, "%s", path);
        load_directory();
        return;
    }
    struct stat info;
    if (stat(path, &info) || !S_ISREG(info.st_mode)) {
        snprintf(status, sizeof status, "This viewer opens regular text files only."); return;
    }
    FILE *file = fopen(path, "rb");
    if (!file) { snprintf(status, sizeof status, "Cannot read file: %s", strerror(errno)); return; }
    size_t length = fread(file_text, 1, sizeof file_text - 1, file);
    bool failed = ferror(file) != 0;
    bool truncated = !feof(file);
    fclose(file);
    if (failed) { snprintf(status, sizeof status, "The file could not be read."); return; }
    for (size_t i = 0; i < length; i++) {
        unsigned char c = (unsigned char)file_text[i];
        if (c < 32 && c != '\n' && c != '\r' && c != '\t') {
            snprintf(status, sizeof status, "This is not a plain text file."); return;
        }
    }
    file_text[length] = 0;
    /* Reject malformed UTF-8 instead of handing arbitrary binary data to SDL_ttf. */
    char *utf8 = SDL_iconv_string("UTF-8", "UTF-8", file_text, length + 1);
    if (!utf8) { snprintf(status, sizeof status, "The viewer supports UTF-8 text."); return; }
    SDL_free(utf8);
    snprintf(file_title, sizeof file_title, "%s", entries[index].name);
    snprintf(status, sizeof status, "%s", truncated ? "Preview limited to 8 KB." : "Read-only text preview. Backspace returns to Files.");
    page = VIEWER;
}

static void draw(void)
{
    for (int y = 0; y < H; y++) box(0, y, W, 1, 9 + y/110, 13 + y/100, 22 + y/60);
    box(0, 0, W, 64, 12, 18, 30);
    orbit(35, 32, 20);
    text(font, "Beyond OS", 68, 18, white);
    text(small_font, "HOME EDITION 1.0", 224, 24, muted);
    time_t now = time(NULL);
    struct tm *local = localtime(&now);
    char clock[80] = "";
    if (local) strftime(clock, sizeof clock, "%a %d %b   %H:%M", local);
    text(small_font, clock, 1000, 24, muted);

    if (page == HOME) {
        text(small_font, "YOUR SPACE. YOUR POSSIBILITIES.", 74, 133, accent);
        text(title_font, "Welcome home.", 70, 170, white);
        text(font, "A little room to think. A new place to begin.", 74, 232, muted);
        orbit(1060, 217, 93);
        for (int i = 0; i < 4; i++) {
            int x = 74 + (i % 2)*575, y = 320 + (i/2)*153;
            box(x, y, 551, 130, i == selected ? 35 : 23, i == selected ? 46 : 33, i == selected ? 70 : 49);
            char number[8]; snprintf(number, sizeof number, "0%d", i + 1);
            text(small_font, number, x+25, y+22, accent);
            text(font, titles[i], x+80, y+28, white);
            text(small_font, subtitles[i], x+80, y+67, muted);
        }
        text(small_font, "Linux foundation preview", 74, 653, accent);
        text(small_font, "Browsing, streaming, device settings and updates are upcoming milestones.", 74, 682, muted);
    } else {
        box(50, 90, 110, 44, 33, 45, 65);
        text(font, "Home", 72, 99, white);
        const char *heading = page == FILES ? "Files" : page == NOTES ? "Notes" : page == VIEWER ? file_title : "About Beyond OS";
        text(title_font, heading, 50, 153, white);
        if (page == FILES) {
            SDL_Rect clip = {50, 221, 1000, 34};
            SDL_RenderSetClipRect(renderer, &clip);
            text(font, directory, 50, 222, muted);
            SDL_RenderSetClipRect(renderer, NULL);
            box(1080, 215, 150, 42, 33, 45, 65);
            text(small_font, "Up a folder", 1100, 228, white);
            for (int i = scroll; i < entry_count && i < scroll+9; i++) {
                int y = 278 + (i-scroll)*46;
                box(50, y, 1180, 42, 24, 34, 51);
                text(small_font, entries[i].directory ? "FOLDER" : "FILE", 68, y+13, accent);
                text(font, entries[i].name, 173, y+8, white);
            }
        } else if (page == NOTES || page == VIEWER) {
            box(50, 220, 1180, 469, 20, 29, 43);
            paragraph(page == NOTES ? note : file_text, 73, 241, 1130, 425);
            if (page == NOTES) {
                text(small_font, dirty ? "Unsaved changes  /  Ctrl+S to save" : "Documents/Home Note.txt", 51, 704, accent);
            }
        } else {
            paragraph("Beyond OS Home Edition 1.0\nDevelopment build: home-dev.1\n\nAn independent Linux system, assembled from upstream source.\n\nLinux kernel / musl / BusyBox / X.Org / Openbox / SDL2\n\nLocal Files and Notes work in this preview. Modern browsing, media,\nuser setup, installation and signed updates are still in development.",
                      54, 236, 1150, 365);
#ifndef _WIN32
            struct utsname system;
            if (!uname(&system)) {
                char detail[256];
                snprintf(detail, sizeof detail, "Running kernel: %.100s  /  %.80s", system.release, system.machine);
                text(small_font, detail, 54, 641, accent);
            }
#endif
        }
    }
    box(0, 751, W, 49, 12, 18, 30);
    text(small_font, *status ? status : "Beyond Imagination Technology", 50, 768, muted);
    SDL_RenderPresent(renderer);
}

int main(int argc, char **argv)
{
    const char *font_path = getenv("BEYOND_FONT");
    if (!font_path) font_path = "/usr/share/fonts/dejavu/DejaVuSans.ttf";
    const char *home = getenv("BEYOND_DATA_HOME");
    if (!home) home = getenv("HOME");
    if (!home || !*home) { fprintf(stderr, "HOME must be set.\n"); return 1; }
    if (!join_path(note_path, sizeof note_path, home, "Documents/Home Note.txt") ||
        strlen(home) >= sizeof directory) return 1;
    snprintf(directory, sizeof directory, "%s", home);
    FILE *stored = fopen(note_path, "rb");
    if (stored) {
        size_t n = fread(note, 1, sizeof note-1, stored); note[n] = 0;
        note_writable = !ferror(stored) && fgetc(stored) == EOF;
        fclose(stored);
    } else if (errno != ENOENT) note_writable = false;
#ifndef _WIN32
    signal(SIGCHLD, SIG_IGN);
#endif
    SDL_SetMainReady();
    if (SDL_Init(SDL_INIT_VIDEO) || TTF_Init()) {
        fprintf(stderr, "Display initialization: %s\n", SDL_GetError()); return 1;
    }
    small_font = TTF_OpenFont(font_path, 16);
    font = TTF_OpenFont(font_path, 23);
    title_font = TTF_OpenFont(font_path, 46);
    if (!small_font || !font || !title_font) {
        fprintf(stderr, "Font initialization: %s\n", TTF_GetError()); return 1;
    }
    bool preview = argc == 3 && !strcmp(argv[1], "--screenshot");
    SDL_DisplayMode display = {0};
    SDL_GetCurrentDisplayMode(0, &display);
    SDL_SetHint(SDL_HINT_X11_WINDOW_TYPE, "desktop");
    SDL_Window *window = SDL_CreateWindow("Beyond OS Home Edition 1.0", 0, 0,
                         preview || !display.w ? W : display.w,
                         preview || !display.h ? H : display.h,
                         SDL_WINDOW_BORDERLESS | (preview ? SDL_WINDOW_HIDDEN : 0));
    if (!window) { fprintf(stderr, "%s\n", SDL_GetError()); return 1; }
    renderer = SDL_CreateRenderer(window, -1, SDL_RENDERER_SOFTWARE);
    if (!renderer) { fprintf(stderr, "%s\n", SDL_GetError()); return 1; }
    SDL_RenderSetLogicalSize(renderer, W, H);
    if (preview) {
        draw();
        SDL_Surface *shot = SDL_CreateRGBSurfaceWithFormat(0, W, H, 32, SDL_PIXELFORMAT_ARGB8888);
        if (!shot || SDL_RenderReadPixels(renderer, NULL, SDL_PIXELFORMAT_ARGB8888, shot->pixels, shot->pitch) ||
            SDL_SaveBMP(shot, argv[2])) { fprintf(stderr, "%s\n", SDL_GetError()); return 1; }
        SDL_FreeSurface(shot);
    } else {
        bool running = true;
        while (running) {
            SDL_Event event;
            while (SDL_PollEvent(&event)) {
                if (event.type == SDL_QUIT) {
                    if (page != NOTES || !dirty || save_note()) running = false;
                } else if (event.type == SDL_MOUSEBUTTONDOWN && event.button.button == SDL_BUTTON_LEFT) {
                    int x = event.button.x, y = event.button.y;
                    if (page != HOME && x>=50 && x<=160 && y>=90 && y<=134) go_home();
                    else if (page == HOME) {
                        for (int i=0; i<4; i++) {
                            int bx=74+(i%2)*575, by=320+(i/2)*153;
                            if (x>=bx && x<bx+551 && y>=by && y<by+130) { selected=i; activate(i); break; }
                        }
                    } else if (page == FILES) {
                        if (x>=1080 && y>=215 && y<257) {
                            char *slash=strrchr(directory, '/');
                            if (slash && slash != directory) *slash=0;
                            else if (slash) directory[1]=0;
                            load_directory();
                        } else if (x>=50 && x<1230 && y>=278 && y<278+9*46)
                            open_entry(scroll+(y-278)/46);
                    }
                } else if (event.type == SDL_MOUSEWHEEL && page == FILES) {
                    scroll -= event.wheel.y * 3;
                    int maximum=entry_count>9 ? entry_count-9 : 0;
                    if (scroll<0) scroll=0;
                    if (scroll>maximum) scroll=maximum;
                } else if (event.type == SDL_TEXTINPUT && page == NOTES) {
                    size_t n=strlen(note), add=strlen(event.text.text);
                    if (n+add < sizeof note) { memcpy(note+n,event.text.text,add+1); dirty=true; }
                    else snprintf(status,sizeof status,"Note limit reached (8 KB). Save before continuing elsewhere.");
                } else if (event.type == SDL_KEYDOWN) {
                    SDL_Keycode key=event.key.keysym.sym;
                    if (key == SDLK_ESCAPE) go_home();
                    else if (page == HOME) {
                        if (key == SDLK_TAB || key == SDLK_RIGHT || key == SDLK_DOWN) selected=(selected+1)%4;
                        else if (key == SDLK_LEFT || key == SDLK_UP) selected=(selected+3)%4;
                        else if (key == SDLK_RETURN) activate(selected);
                    } else if (page == VIEWER && key == SDLK_BACKSPACE) { page=FILES; load_directory(); }
                    else if (page == NOTES) {
                        size_t n=strlen(note);
                        if (key==SDLK_s && (event.key.keysym.mod & KMOD_CTRL)) save_note();
                        else if (key==SDLK_BACKSPACE && n) {
                            do { n--; } while (n && ((unsigned char)note[n]&0xc0)==0x80);
                            note[n]=0; dirty=true;
                        } else if (key==SDLK_RETURN && n+1<sizeof note) { note[n]='\n'; note[n+1]=0; dirty=true; }
                    }
                }
            }
            draw();
            SDL_Delay(33);
        }
    }
    TTF_CloseFont(small_font); TTF_CloseFont(font); TTF_CloseFont(title_font);
    SDL_DestroyRenderer(renderer); SDL_DestroyWindow(window);
    TTF_Quit(); SDL_Quit();
    return 0;
}
