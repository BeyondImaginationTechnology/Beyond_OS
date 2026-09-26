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
#ifndef BIT_EDITION_CYBER
static SDL_Texture *wallpaper_texture;
#endif
static TTF_Font *small_font, *font, *title_font;
static enum Page page = HOME;
static char note[NOTE_CAP], status[256], directory[PATH_CAP], note_path[PATH_CAP];
static char file_text[NOTE_CAP], file_title[256];
static Entry entries[ENTRY_CAP];
static int entry_count, scroll, selected;
static bool dirty, note_writable = true;
static bool media_mode;
static const SDL_Color white = {239, 242, 255, 255};
static const SDL_Color muted = {153, 170, 195, 255};
static const SDL_Color accent = {150, 174, 255, 255};
#ifdef BIT_EDITION_CYBER
#define CARD_COUNT 4
#define CARD_WIDTH 551
#define CARD_HEIGHT 130
#define EDITION_LABEL "CYBER EDITION 1.0"
#define HOME_KICKER "AUTHORIZED SECURITY WORKSPACE"
#define HOME_TITLE "Know your scope."
#define HOME_COPY "Start with permission. Preserve the evidence."
#define FOUNDATION_NOTE "Authorized inventory, evidence and reporting foundation"
#define UPCOMING_NOTE "Packet capture, browser research and signed updates are upcoming milestones."
static const char *titles[] = {"Scope & Inventory", "Evidence", "Terminal", "About Cyber"};
static const char *subtitles[] = {"Record authorization before network discovery", "Keep local findings together",
                                  "Your Linux command line", "Your system, at a glance"};
#else
#define EDITION_LABEL "HOME 0.1"
#define HOME_KICKER "YOUR SPACE, READY TO GO"
#define HOME_TITLE "Welcome home."
#define HOME_COPY "Your desktop for everyday work and play."
#define FOUNDATION_NOTE "HOME 0.1 PREVIEW  /  FILES, NOTES, WEB AND MEDIA"
#define UPCOMING_NOTE "Choose a tile to begin. Tab and Enter work with the keyboard."
#define CARD_COUNT 6
#define CARD_WIDTH 371
#define CARD_HEIGHT 124
static const char *titles[] = {"Files", "Notes", "Browser", "Media", "Terminal", "About Home"};
static const char *subtitles[] = {"Browse your folders", "Write something down",
                                  "Explore the web", "Play your files",
                                  "Open the command line", "System and edition details"};
static const char *symbols[] = {"F", "N", "W", "M", ">", "i"};
#endif

static void box(int x, int y, int w, int h, int r, int g, int b)
{
    SDL_Rect rect = {x, y, w, h};
    SDL_SetRenderDrawColor(renderer, (Uint8)r, (Uint8)g, (Uint8)b, 255);
    SDL_RenderFillRect(renderer, &rect);
}

#ifndef BIT_EDITION_CYBER
static void glass(int x, int y, int w, int h, int r, int g, int b, int alpha)
{
    SDL_Rect rect = {x, y, w, h};
    SDL_SetRenderDrawBlendMode(renderer, SDL_BLENDMODE_BLEND);
    SDL_SetRenderDrawColor(renderer, (Uint8)r, (Uint8)g, (Uint8)b, (Uint8)alpha);
    SDL_RenderFillRect(renderer, &rect);
    SDL_SetRenderDrawBlendMode(renderer, SDL_BLENDMODE_NONE);
}

#endif

static void card_rect(int index, int *x, int *y)
{
#ifdef BIT_EDITION_CYBER
    *x = 74 + (index % 2) * 575;
    *y = 320 + (index / 2) * 153;
#else
    *x = 62 + (index % 3) * 393;
    *y = 345 + (index / 3) * 146;
#endif
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
    snprintf(status, sizeof status, "%d items%s. %s",
             entry_count, entry_count == ENTRY_CAP ? " (first 512 shown)" : "",
             media_mode ? "Select a folder or a media file to play." :
                          "Select a folder or UTF-8 text file.");
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
#ifdef BIT_EDITION_CYBER
        execlp("xterm", "xterm", "-T", "BIT OS Cyber Terminal", "-bg", "#090d16",
               "-fg", "#eff2ff", "-fa", "DejaVu Sans Mono", "-fs", "12",
               "-e", "bit-cyber-menu", (char *)NULL);
#else
        execlp("xterm", "xterm", "-T", "Beyond Terminal", "-bg", "#090d16",
               "-fg", "#eff2ff", "-fa", "DejaVu Sans Mono", "-fs", "12", (char *)NULL);
#endif
        _exit(127);
    }
    if (child < 0) snprintf(status, sizeof status, "Could not open terminal: %s", strerror(errno));
    else snprintf(status, sizeof status, "Terminal opened. Alt+Tab switches windows.");
#else
    snprintf(status, sizeof status, "The terminal is available in the Linux image.");
#endif
}

#ifndef BIT_EDITION_CYBER
static void launch_browser(void)
{
#ifndef _WIN32
    pid_t child = fork();
    if (child == 0) {
        execlp("MiniBrowser", "MiniBrowser", "https://os.beyondimagination.co.technology/", (char *)NULL);
        _exit(127);
    }
    if (child < 0) snprintf(status, sizeof status, "Could not open Browser: %s", strerror(errno));
    else snprintf(status, sizeof status, "Browser opened. Alt+Tab switches windows.");
#else
    snprintf(status, sizeof status, "Browser is included in the Linux image.");
#endif
}

static bool media_file(const char *path)
{
    const char *dot = strrchr(path, '.');
    if (!dot) return false;
    return !SDL_strcasecmp(dot, ".mp3") || !SDL_strcasecmp(dot, ".ogg") ||
           !SDL_strcasecmp(dot, ".wav") || !SDL_strcasecmp(dot, ".flac") ||
           !SDL_strcasecmp(dot, ".mp4") || !SDL_strcasecmp(dot, ".mkv") ||
           !SDL_strcasecmp(dot, ".webm");
}

static void launch_media(const char *path)
{
#ifndef _WIN32
    pid_t child = fork();
    if (child == 0) {
        execlp("ffplay", "ffplay", "-autoexit", "-window_title", "BIT OS Home Media", path, (char *)NULL);
        _exit(127);
    }
    if (child < 0) snprintf(status, sizeof status, "Could not open Media: %s", strerror(errno));
    else snprintf(status, sizeof status, "Media opened. Alt+Tab switches windows.");
#else
    (void)path;
    snprintf(status, sizeof status, "Media playback is included in the Linux image.");
#endif
}
#endif

static void activate(int card)
{
#ifdef BIT_EDITION_CYBER
    if (card == 0 || card == 2) launch_terminal();
    else if (card == 1) {
        const char *home = getenv("HOME");
        int count = home ? snprintf(directory, sizeof directory, "%s/Documents/Cyber/Evidence", home) : -1;
        if (count < 0 || (size_t)count >= sizeof directory) {
            snprintf(status, sizeof status, "Evidence folder path is unavailable.");
            return;
        }
        page = FILES;
        load_directory();
    } else { page = ABOUT; status[0] = 0; }
#else
    if (card == 0) {
        const char *home = getenv("HOME");
        if (home && strlen(home) < sizeof directory)
            snprintf(directory, sizeof directory, "%s", home);
        media_mode = false; page = FILES; load_directory();
    }
    else if (card == 1) { if (!note_writable) { snprintf(status, sizeof status, "Notes could not load the existing file (unreadable or over 8 KB). It has not been changed."); return; } page = NOTES; SDL_StartTextInput(); snprintf(status, sizeof status, "Type a note. Ctrl+S saves. Home also saves before leaving."); }
    else if (card == 2) launch_browser();
    else if (card == 3) {
        const char *home = getenv("HOME");
        int count = home ? snprintf(directory, sizeof directory, "%s/Media", home) : -1;
        if (count < 0 || (size_t)count >= sizeof directory) {
            snprintf(status, sizeof status, "Media folder path is unavailable.");
            return;
        }
        media_mode = true; page = FILES; load_directory();
    }
    else if (card == 4) launch_terminal();
    else { page = ABOUT; status[0] = 0; }
#endif
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
#ifndef BIT_EDITION_CYBER
    if (media_mode && media_file(path)) { launch_media(path); return; }
#endif
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
#ifdef BIT_EDITION_CYBER
    for (int y = 0; y < H; y++) box(0, y, W, 1, 9 + y/110, 13 + y/100, 22 + y/60);
    box(0, 0, W, 64, 12, 18, 30);
    orbit(35, 32, 20);
    text(font, "Beyond OS", 68, 18, white);
    text(small_font, EDITION_LABEL, 224, 24, muted);
#else
    if (wallpaper_texture) {
        SDL_Rect desktop = {0, 0, W, H};
        SDL_RenderCopy(renderer, wallpaper_texture, NULL, &desktop);
        glass(0, 0, W, H, 5, 15, 10, 24);
    } else {
        for (int y = 0; y < H; y++) {
            int glow = y < 520 ? y / 12 : (H - y) / 14;
            box(0, y, W, 1, 10 + glow / 2, 39 + glow, 76 + glow);
        }
        for (int i = 0; i < 10; i++) {
            SDL_SetRenderDrawColor(renderer, 81, 157, 207, (Uint8)(34 - i * 3));
            SDL_SetRenderDrawBlendMode(renderer, SDL_BLENDMODE_BLEND);
            SDL_RenderDrawLine(renderer, 0, 532 + i * 13, W, 428 + i * 22);
        }
        SDL_SetRenderDrawBlendMode(renderer, SDL_BLENDMODE_NONE);
    }
    glass(0, 0, W, 76, 5, 19, 39, 211);
    glass(0, 75, W, 1, 176, 218, 255, 114);
    orbit(37, 37, 22);
    text(font, "Beyond Imagination OS", 73, 21, white);
    text(small_font, EDITION_LABEL, 395, 27, accent);
#endif
    time_t now = time(NULL);
    struct tm *local = localtime(&now);
    char clock[80] = "";
    if (local) strftime(clock, sizeof clock, "%a %d %b   %H:%M", local);
    text(small_font, clock, 1010, 28, white);

    if (page == HOME) {
#ifdef BIT_EDITION_CYBER
        text(small_font, HOME_KICKER, 74, 133, accent);
        text(title_font, HOME_TITLE, 70, 170, white);
        text(font, HOME_COPY, 74, 232, muted);
        orbit(1060, 217, 93);
        for (int i = 0; i < 4; i++) {
            int x = 74 + (i % 2)*575, y = 320 + (i/2)*153;
            box(x, y, 551, 130, i == selected ? 35 : 23, i == selected ? 46 : 33, i == selected ? 70 : 49);
            char number[8]; snprintf(number, sizeof number, "0%d", i + 1);
            text(small_font, number, x+25, y+22, accent);
            text(font, titles[i], x+80, y+28, white);
            text(small_font, subtitles[i], x+80, y+67, muted);
        }
        text(small_font, FOUNDATION_NOTE, 74, 653, accent);
        text(small_font, UPCOMING_NOTE, 74, 682, muted);
#else
        glass(40, 105, 1200, 210, 10, 28, 57, 194);
        glass(40, 105, 1200, 2, 174, 224, 255, 148);
        glass(60, 124, 750, 173, 15, 49, 90, 125);
        text(small_font, HOME_KICKER, 83, 144, accent);
        text(title_font, HOME_TITLE, 79, 179, white);
        text(font, HOME_COPY, 83, 245, white);
        orbit(1050, 207, 75);
        text(small_font, "DASHBOARD", 61, 319, white);
        text(small_font, "Everything in one place", 1074, 319, muted);
        for (int i = 0; i < CARD_COUNT; i++) {
            int x, y; card_rect(i, &x, &y);
            glass(x, y + 4, CARD_WIDTH, 128, 0, 8, 24, 98);
            glass(x, y, CARD_WIDTH, CARD_HEIGHT, i == selected ? 65 : 17,
                  i == selected ? 112 : 51, i == selected ? 159 : 87, 218);
            glass(x, y, CARD_WIDTH, 2, 177, 223, 255, i == selected ? 225 : 96);
            glass(x + 17, y + 22, 60, 60, 107, 178, 230, 136);
            text(font, symbols[i], x + 37, y + 36, white);
            text(font, titles[i], x + 94, y + 24, white);
            text(small_font, subtitles[i], x + 94, y + 62, muted);
            text(small_font, "OPEN  >", x + 94, y + 93, accent);
        }
        text(small_font, FOUNDATION_NOTE, 62, 659, white);
        text(small_font, UPCOMING_NOTE, 62, 687, muted);
#endif
    } else {
#ifdef BIT_EDITION_CYBER
        box(50, 90, 110, 44, 33, 45, 65);
#else
        glass(36, 91, 1208, 641, 8, 25, 49, 225);
        glass(50, 90, 110, 44, 68, 132, 181, 195);
#endif
        text(font, "Home", 72, 99, white);
        const char *heading = page == FILES ? (media_mode ? "Media" : "Files") : page == NOTES ? "Notes" : page == VIEWER ? file_title : "About Home";
        text(title_font, heading, 50, 153, white);
        if (page == FILES) {
            if (media_mode && entry_count == 0)
                text(small_font, "Add music or video files to your Media folder to play them here.", 52, 262, muted);
            SDL_Rect clip = {50, 221, 1000, 34};
            SDL_RenderSetClipRect(renderer, &clip);
            text(font, directory, 50, 222, muted);
            SDL_RenderSetClipRect(renderer, NULL);
            box(1080, 215, 150, 42, 33, 45, 65);
            text(small_font, "Up a folder", 1100, 228, white);
            for (int i = scroll; i < entry_count && i < scroll+9; i++) {
                int y = 278 + (i-scroll)*46;
                box(50, y, 1180, 42, 24, 34, 51);
#ifdef BIT_EDITION_CYBER
                text(small_font, entries[i].directory ? "FOLDER" : "FILE", 68, y+13, accent);
#else
                text(small_font, entries[i].directory ? "FOLDER" :
                     (media_mode && media_file(entries[i].name) ? "MEDIA" : "FILE"),
                     68, y+13, accent);
#endif
                text(font, entries[i].name, 173, y+8, white);
            }
        } else if (page == NOTES || page == VIEWER) {
            box(50, 220, 1180, 469, 20, 29, 43);
            paragraph(page == NOTES ? note : file_text, 73, 241, 1130, 425);
            if (page == NOTES) {
                text(small_font, dirty ? "Unsaved changes  /  Ctrl+S to save" : "Documents/Home Note.txt", 51, 704, accent);
            }
        } else {
#ifdef BIT_EDITION_CYBER
            paragraph("BIT OS Cyber Edition 1.0\nDevelopment build: cyber-dev.1\n\nAn independent Linux workspace for authorized assessment, evidence handling and reporting.\n\nLinux kernel / musl / BusyBox / X.Org / Openbox / SDL2 / Nmap\n\nThe inventory launcher requires a local authorization record and runs a limited TCP connect inventory. Packet capture, browser research, user setup, installation and signed updates are still in development.",
#else
            paragraph("BIT OS Home 0.1\nDevelopment candidate\n\nAn independent Linux system, assembled from upstream source.\n\nLinux kernel / musl / BusyBox / X.Org / Openbox / SDL2\n\nHome includes Files, Notes, a WebKit browser, and local media playback.\nHardware support and release validation are still in progress.",
#endif
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
#ifdef BIT_EDITION_CYBER
    box(0, 751, W, 49, 12, 18, 30);
    text(small_font, *status ? status : "Beyond Imagination Technology", 50, 768, muted);
#else
    glass(0, 740, W, 60, 6, 23, 48, 223);
    glass(0, 740, W, 2, 177, 225, 255, 146);
    glass(17, 748, 150, 43, 51, 116, 174, 213);
    orbit(43, 770, 15);
    text(small_font, "HOME", 72, 761, white);
    for (int i = 0; i < 3; i++) {
        int x = 183 + i * 57;
        glass(x, 749, 48, 42, 83, 148, 194, 128);
        text(small_font, i == 0 ? "F" : i == 1 ? "W" : "M", x + 18, 761, white);
    }
    SDL_Rect status_clip = {376, 744, 650, 49};
    SDL_RenderSetClipRect(renderer, &status_clip);
    text(small_font, *status ? status : "Beyond Imagination Technology", 376, 761, white);
    SDL_RenderSetClipRect(renderer, NULL);
    text(small_font, clock, 1055, 761, white);
#endif
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
    SDL_Window *window = SDL_CreateWindow("BIT OS " EDITION_LABEL, 0, 0,
                         preview || !display.w ? W : display.w,
                         preview || !display.h ? H : display.h,
                         SDL_WINDOW_BORDERLESS | (preview ? SDL_WINDOW_HIDDEN : 0));
    if (!window) { fprintf(stderr, "%s\n", SDL_GetError()); return 1; }
    renderer = SDL_CreateRenderer(window, -1, SDL_RENDERER_SOFTWARE);
    if (!renderer) { fprintf(stderr, "%s\n", SDL_GetError()); return 1; }
    SDL_RenderSetLogicalSize(renderer, W, H);
#ifndef BIT_EDITION_CYBER
    const char *wallpaper_path = getenv("BEYOND_WALLPAPER");
    if (!wallpaper_path) wallpaper_path = "/usr/share/beyond-home/wallpaper.bmp";
    SDL_Surface *wallpaper_surface = SDL_LoadBMP(wallpaper_path);
    if (wallpaper_surface) {
        wallpaper_texture = SDL_CreateTextureFromSurface(renderer, wallpaper_surface);
        SDL_FreeSurface(wallpaper_surface);
    }
#endif
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
#ifndef BIT_EDITION_CYBER
                    if (y >= 749 && y < 791 && x >= 183 && x < 345) {
                        int shortcut = (x - 183) / 57;
                        if (page != HOME) go_home();
                        if (page == HOME) activate(shortcut == 0 ? 0 : shortcut == 1 ? 2 : 3);
                        continue;
                    }
#endif
                    if (page != HOME && ((x>=50 && x<=160 && y>=90 && y<=134) ||
                                         (x>=17 && x<=167 && y>=748 && y<=791))) go_home();
                    else if (page == HOME) {
                        for (int i=0; i<CARD_COUNT; i++) {
                            int bx, by; card_rect(i, &bx, &by);
                            if (x>=bx && x<bx+CARD_WIDTH && y>=by && y<by+CARD_HEIGHT) {
                                selected=i; activate(i); break;
                            }
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
                        if (key == SDLK_TAB || key == SDLK_RIGHT || key == SDLK_DOWN) selected=(selected+1)%CARD_COUNT;
                        else if (key == SDLK_LEFT || key == SDLK_UP) selected=(selected+CARD_COUNT-1)%CARD_COUNT;
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
