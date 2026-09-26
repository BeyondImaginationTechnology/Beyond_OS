/* Beyond OS Home: native development desktop. */
#define _POSIX_C_SOURCE 200809L
#define SDL_MAIN_HANDLED
#include <SDL.h>
#include <SDL_ttf.h>
#include <dirent.h>
#include <errno.h>
#include <fcntl.h>
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

enum Page { HOME, LAUNCHER, FILES, NOTES, ABOUT, VIEWER };
typedef struct { char name[256]; bool directory; } Entry;
typedef struct { char name[256]; char path[PATH_CAP]; bool directory; int x, y; } DesktopIcon;
static SDL_Renderer *renderer;
#ifndef BIT_EDITION_CYBER
static SDL_Texture *wallpaper_texture;
#endif
static TTF_Font *small_font, *font, *title_font;
static enum Page page = HOME;
static char note[NOTE_CAP], status[256], directory[PATH_CAP], note_path[PATH_CAP];
static char file_text[NOTE_CAP], file_title[256];
static Entry entries[ENTRY_CAP];
#ifndef BIT_EDITION_CYBER
#define DESKTOP_ICON_CAP 48
static DesktopIcon desktop_icons[DESKTOP_ICON_CAP];
static int desktop_icon_count, desktop_selected = -1, desktop_drag = -1;
static int desktop_drag_dx, desktop_drag_dy, desktop_mouse_x, desktop_mouse_y, desktop_mouse_clicks;
static bool desktop_dragged;
static char home_directory[PATH_CAP], desktop_layout_path[PATH_CAP];
static int file_drag = -1;
static int file_mouse_x, file_mouse_y;
static bool file_dragged;
#endif
static int entry_count, scroll, selected;
static bool dirty, note_writable = true;
static bool media_mode;
static const SDL_Color white = {239, 242, 255, 255};
static const SDL_Color muted = {153, 170, 195, 255};
#ifdef BIT_EDITION_CYBER
static const SDL_Color accent = {150, 174, 255, 255};
#else
static const SDL_Color accent = {126, 243, 173, 255};
#endif
#ifndef BIT_EDITION_CYBER
static void open_entry(int index);
static bool media_file(const char *path);
static void load_directory(void);
#endif
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

#ifdef BIT_EDITION_CYBER
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
#endif

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
#ifdef BIT_EDITION_CYBER
            SDL_SetRenderDrawColor(renderer, (Uint8)(105 + i / 3), 150, 250, 255);
#else
            SDL_SetRenderDrawColor(renderer, (Uint8)(58 + i / 5), 214, (Uint8)(130 + i / 4), 255);
#endif
            SDL_RenderDrawLine(renderer, cx + (int)(ax*cos(angle)-ay*sin(angle)),
                              cy + (int)(ax*sin(angle)+ay*cos(angle)),
                              cx + (int)(bx*cos(angle)-by*sin(angle)),
                              cy + (int)(bx*sin(angle)+by*cos(angle)));
        }
    }
#ifdef BIT_EDITION_CYBER
    box(cx - 2, cy - 5, 4, 10, 219, 215, 255);
#else
    box(cx - 2, cy - 5, 4, 10, 221, 255, 232);
#endif
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

#ifndef BIT_EDITION_CYBER
static bool has_control_chars(const char *value)
{
    for (const unsigned char *p = (const unsigned char *)value; *p; p++)
        if (*p < 32 || *p == 127) return true;
    return false;
}

static bool path_is_in_home(const char *path)
{
    size_t n = strlen(home_directory);
    return !strncmp(path, home_directory, n) && path[n] == '/' && path[n + 1] != 0;
}

static int find_desktop_icon(const char *path)
{
    for (int i = 0; i < desktop_icon_count; i++)
        if (!strcmp(desktop_icons[i].path, path)) return i;
    return -1;
}

static bool add_desktop_icon(const char *path, const char *name, bool is_directory, int x, int y)
{
    if (desktop_icon_count >= DESKTOP_ICON_CAP || has_control_chars(name) || has_control_chars(path)) return false;
    int existing = find_desktop_icon(path);
    if (existing >= 0) return true;
    DesktopIcon *icon = &desktop_icons[desktop_icon_count++];
    snprintf(icon->name, sizeof icon->name, "%s", name);
    snprintf(icon->path, sizeof icon->path, "%s", path);
    icon->directory = is_directory;
    icon->x = x;
    icon->y = y;
    return true;
}

static void save_desktop_layout(void)
{
    if (!*desktop_layout_path) return;
    char temporary[PATH_CAP];
    int n = snprintf(temporary, sizeof temporary, "%s.tmp", desktop_layout_path);
    if (n < 0 || (size_t)n >= sizeof temporary) return;
    FILE *file = fopen(temporary, "wb");
    if (!file) return;
    bool okay = true;
    for (int i = 0; i < desktop_icon_count; i++) {
        DesktopIcon *icon = &desktop_icons[i];
        if (fprintf(file, "%d\t%d\t%s\n", icon->x, icon->y, icon->path) < 0) { okay = false; break; }
    }
    if (fclose(file)) okay = false;
    if (!okay || rename(temporary, desktop_layout_path)) remove(temporary);
}

static void load_desktop(void)
{
    desktop_icon_count = 0;
    char config_dir[PATH_CAP];
    if (!join_path(config_dir, sizeof config_dir, home_directory, ".config/beyond-home") ||
        !join_path(desktop_layout_path, sizeof desktop_layout_path, config_dir, "desktop-layout")) return;
    char parent[PATH_CAP];
    if (join_path(parent, sizeof parent, home_directory, ".config")) mkdir(parent, 0755);
    mkdir(config_dir, 0755);

    FILE *layout = fopen(desktop_layout_path, "rb");
    if (layout) {
        char line[PATH_CAP + 64];
        while (fgets(line, sizeof line, layout) && desktop_icon_count < DESKTOP_ICON_CAP) {
            char *first = strchr(line, '\t');
            if (!first) continue;
            *first++ = 0;
            char *second = strchr(first, '\t');
            if (!second) continue;
            *second++ = 0;
            char *end = strchr(second, '\n');
            if (end) *end = 0;
            char *x_end, *y_end;
            long x = strtol(line, &x_end, 10), y = strtol(first, &y_end, 10);
            if (*x_end || *y_end || !path_is_in_home(second)) continue;
            struct stat info;
            if (stat(second, &info) || (!S_ISDIR(info.st_mode) && !S_ISREG(info.st_mode))) continue;
            const char *name = strrchr(second, '/');
            add_desktop_icon(second, name ? name + 1 : second, S_ISDIR(info.st_mode),
                             (int)x, (int)y);
        }
        fclose(layout);
    }

    DIR *dir = opendir(home_directory);
    if (!dir) return;
    Entry home_entries[DESKTOP_ICON_CAP];
    int count = 0;
    struct dirent *entry;
    while ((entry = readdir(dir)) && count < DESKTOP_ICON_CAP) {
        if (entry->d_name[0] == '.' || !strcmp(entry->d_name, "..") || has_control_chars(entry->d_name)) continue;
        char path[PATH_CAP];
        struct stat info;
        if (!join_path(path, sizeof path, home_directory, entry->d_name) || stat(path, &info) ||
            (!S_ISDIR(info.st_mode) && !S_ISREG(info.st_mode))) continue;
        snprintf(home_entries[count].name, sizeof home_entries[count].name, "%s", entry->d_name);
        home_entries[count++].directory = S_ISDIR(info.st_mode);
    }
    closedir(dir);
    qsort(home_entries, (size_t)count, sizeof home_entries[0], compare_entry);
    for (int i = 0; i < count && desktop_icon_count < DESKTOP_ICON_CAP; i++) {
        char path[PATH_CAP];
        if (!join_path(path, sizeof path, home_directory, home_entries[i].name)) continue;
        if (find_desktop_icon(path) >= 0) continue;
        int slot = desktop_icon_count;
        int x = 24 + (slot / 7) * 112;
        int y = 24 + (slot % 7) * 88;
        add_desktop_icon(path, home_entries[i].name, home_entries[i].directory, x, y);
    }
}

static int desktop_hit(int x, int y)
{
    for (int i = desktop_icon_count - 1; i >= 0; i--)
        if (x >= desktop_icons[i].x && x < desktop_icons[i].x + 88 &&
            y >= desktop_icons[i].y && y < desktop_icons[i].y + 78) return i;
    return -1;
}

static void desktop_open(int index)
{
    if (index < 0 || index >= desktop_icon_count) return;
    DesktopIcon *icon = &desktop_icons[index];
    struct stat info;
    if (stat(icon->path, &info) || (!S_ISDIR(info.st_mode) && !S_ISREG(info.st_mode))) {
        snprintf(status, sizeof status, "That desktop item is no longer available.");
        return;
    }
    media_mode = media_file(icon->path);
    if (S_ISDIR(info.st_mode)) {
        snprintf(directory, sizeof directory, "%s", icon->path);
        page = FILES;
        load_directory();
        return;
    }
    char parent[PATH_CAP];
    snprintf(parent, sizeof parent, "%s", icon->path);
    char *slash = strrchr(parent, '/');
    if (!slash) return;
    if (slash == parent) slash[1] = 0;
    else *slash = 0;
    snprintf(directory, sizeof directory, "%s", parent);
    page = FILES;
    load_directory();
    for (int i = 0; i < entry_count; i++)
        if (!strcmp(entries[i].name, icon->name)) { open_entry(i); return; }
}

static bool desktop_pin_entry(int index)
{
    if (index < 0 || index >= entry_count) return false;
    char path[PATH_CAP];
    struct stat info;
    if (!join_path(path, sizeof path, directory, entries[index].name) ||
        !path_is_in_home(path) || stat(path, &info) ||
        (!S_ISDIR(info.st_mode) && !S_ISREG(info.st_mode))) return false;
    if (find_desktop_icon(path) >= 0) {
        snprintf(status, sizeof status, "%s is already on the Desktop.", entries[index].name);
        return true;
    }
    int slot = desktop_icon_count;
    int x = 24 + (slot / 7) * 112;
    int y = 24 + (slot % 7) * 88;
    if (!add_desktop_icon(path, entries[index].name, S_ISDIR(info.st_mode), x, y)) {
        snprintf(status, sizeof status, "The Desktop is full.");
        return false;
    }
    save_desktop_layout();
    snprintf(status, sizeof status, "Added %s to the Desktop.", entries[index].name);
    return true;
}
#endif

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
    int report[2];
    if (pipe(report)) {
        snprintf(status, sizeof status, "Could not start Browser: %s", strerror(errno));
        return;
    }
    (void)fcntl(report[1], F_SETFD, FD_CLOEXEC);
    pid_t child = fork();
    if (child == 0) {
        close(report[0]);
        /* Prefer the software path on the live image's basic VGA adapter. */
        setenv("WEBKIT_DISABLE_COMPOSITING_MODE", "1", 0);
        execlp("MiniBrowser", "MiniBrowser", "https://os.beyondimagination.co.technology/", (char *)NULL);
        int launch_error = errno;
        (void)write(report[1], &launch_error, sizeof launch_error);
        _exit(127);
    }
    close(report[1]);
    if (child < 0) {
        close(report[0]);
        snprintf(status, sizeof status, "Could not start Browser: %s", strerror(errno));
        return;
    }
    int launch_error = 0;
    ssize_t received;
    do { received = read(report[0], &launch_error, sizeof launch_error); } while (received < 0 && errno == EINTR);
    close(report[0]);
    if (received > 0) snprintf(status, sizeof status, "Browser could not start: %s", strerror(launch_error));
    else snprintf(status, sizeof status, "Browser opened with software rendering. If the page stays blank, check network and WebKit logs.");
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

#ifndef BIT_EDITION_CYBER
static void draw_desktop_icon(int index)
{
    DesktopIcon *icon = &desktop_icons[index];
    int x = icon->x, y = icon->y;
    if (index == desktop_selected || index == desktop_drag)
        glass(x - 5, y - 6, 88, 82, 111, 171, 229, index == desktop_drag ? 110 : 72);
    if (icon->directory) {
        glass(x + 12, y + 14, 46, 29, 15, 43, 29, 238);
        glass(x + 15, y + 10, 22, 9, 15, 43, 29, 238);
        glass(x + 9, y + 18, 49, 30, 44, 145, 82, 248);
        glass(x + 9, y + 18, 49, 2, 197, 255, 211, 232);
    } else {
        glass(x + 18, y + 8, 35, 42, 12, 28, 45, 238);
        glass(x + 18, y + 8, 35, 2, 211, 232, 255, 206);
        glass(x + 24, y + 20, 22, 2, 174, 207, 234, 205);
        glass(x + 24, y + 27, 22, 2, 174, 207, 234, 180);
        glass(x + 24, y + 34, 16, 2, 174, 207, 234, 150);
    }
    char label[40];
    size_t n = strlen(icon->name);
    if (n > 15) {
        memcpy(label, icon->name, 12);
        memcpy(label + 12, "...", 4);
    } else snprintf(label, sizeof label, "%s", icon->name);
    text(small_font, label, x - 8, y + 53, white);
}

static void draw_launcher(void)
{
    glass(18, 83, 548, 644, 5, 27, 18, 241);
    glass(18, 83, 548, 2, 173, 255, 204, 205);
    text(title_font, "Home", 43, 95, white);
    text(small_font, "APPS & PLACES", 46, 153, accent);
    for (int i = 0; i < CARD_COUNT; i++) {
        int x = 44 + (i % 2) * 257;
        int y = 184 + (i / 2) * 145;
        glass(x, y, 236, 128, i == selected ? 49 : 13,
              i == selected ? 120 : 49, i == selected ? 76 : 38, 226);
        glass(x, y, 236, 2, 173, 255, 204, i == selected ? 230 : 112);
        glass(x + 15, y + 18, 45, 45, 62, 153, 92, 178);
        text(font, symbols[i], x + 29, y + 27, white);
        text(font, titles[i], x + 73, y + 20, white);
        text(small_font, subtitles[i], x + 73, y + 57, muted);
        text(small_font, "OPEN  >", x + 73, y + 91, accent);
    }
    glass(19, 665, 546, 61, 255, 255, 255, 19);
    text(small_font, "Drag files from Files onto Desktop to pin them", 42, 684, muted);
}
#endif

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
#endif
    time_t now = time(NULL);
    struct tm *local = localtime(&now);
    char clock[80] = "";
    if (local) strftime(clock, sizeof clock, "%a %d %b   %H:%M", local);
#ifdef BIT_EDITION_CYBER
    text(small_font, clock, 1010, 28, white);
#endif

    if (page == HOME || page == LAUNCHER) {
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
        if (page == HOME) {
            for (int i = 0; i < desktop_icon_count; i++) draw_desktop_icon(i);
        } else {
            draw_launcher();
        }
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
            glass(50, 697, 1180, 36, file_dragged ? 57 : 18,
                  file_dragged ? 132 : 54, file_dragged ? 91 : 79, 221);
            text(small_font, "DROP A FILE OR FOLDER HERE TO PIN IT TO THE DESKTOP", 70, 706,
                 file_dragged ? white : muted);
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
    glass(0, 740, W, 60, 4, 34, 21, 238);
    glass(0, 740, W, 2, 160, 255, 192, 210);
    glass(17, 748, 150, 43, 24, 111, 61, 236);
    glass(17, 748, 150, 2, 200, 255, 218, 212);
    orbit(43, 770, 15);
    text(small_font, "START", 72, 761, white);
    for (int i = 0; i < 3; i++) {
        int x = 183 + i * 57;
        glass(x, 749, 48, 42, 14, 65, 39, 218);
        glass(x, 749, 48, 2, 141, 255, 183, 170);
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
#ifndef BIT_EDITION_CYBER
    snprintf(home_directory, sizeof home_directory, "%s", home);
    load_desktop();
#endif
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
                    if (y >= 749 && y < 791 && x >= 17 && x < 167) {
                        if (page == HOME) page = LAUNCHER;
                        else if (page == LAUNCHER) page = HOME;
                        else go_home();
                        continue;
                    }
                    if (y >= 749 && y < 791 && x >= 183 && x < 345) {
                        int shortcut = (x - 183) / 57;
                        if (page != HOME) go_home();
                        if (page == HOME) activate(shortcut == 0 ? 0 : shortcut == 1 ? 2 : 3);
                        continue;
                    }
                    if (page == HOME) {
                        int icon = desktop_hit(x, y);
                        if (icon >= 0) {
                            desktop_selected = desktop_drag = icon;
                            desktop_mouse_x = x; desktop_mouse_y = y;
                            desktop_drag_dx = x - desktop_icons[icon].x;
                            desktop_drag_dy = y - desktop_icons[icon].y;
                            desktop_mouse_clicks = event.button.clicks;
                            desktop_dragged = false;
                        }
                    } else if (page == LAUNCHER) {
                        if (x >= 44 && x < 537 && y >= 184 && y < 619) {
                            int row = (y - 184) / 145;
                            int column = x >= 301 ? 1 : 0;
                            int card = row * 2 + column;
                            int bx = column ? 301 : 44;
                            int by = 184 + row * 145;
                            if (card < CARD_COUNT && x < bx + 236 && y < by + 128) {
                                selected = card;
                                activate(card);
                            }
                        } else page = HOME;
                    } else if (page != HOME &&
                               ((x >= 50 && x <= 160 && y >= 90 && y <= 134) ||
                                (x >= 17 && x <= 167 && y >= 748 && y <= 791))) go_home();
                    else if (page == FILES) {
                        if (x >= 1080 && y >= 215 && y < 257) {
                            char *slash = strrchr(directory, '/');
                            if (slash && slash != directory) *slash = 0;
                            else if (slash) directory[1] = 0;
                            load_directory();
                        } else if (x >= 50 && x < 1230 && y >= 278 && y < 278 + 9 * 46) {
                            int index = scroll + (y - 278) / 46;
                            if (index < entry_count) {
                                file_drag = index;
                                file_mouse_x = x; file_mouse_y = y;
                                file_dragged = false;
                            }
                        }
                    }
#else
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
#endif
                } else if (event.type == SDL_MOUSEMOTION) {
#ifndef BIT_EDITION_CYBER
                    int x = event.motion.x, y = event.motion.y;
                    if (desktop_drag >= 0 && page == HOME) {
                        if (abs(x - desktop_mouse_x) > 5 || abs(y - desktop_mouse_y) > 5)
                            desktop_dragged = true;
                        if (desktop_dragged) {
                            desktop_icons[desktop_drag].x = x - desktop_drag_dx;
                            desktop_icons[desktop_drag].y = y - desktop_drag_dy;
                            if (desktop_icons[desktop_drag].x < 8) desktop_icons[desktop_drag].x = 8;
                            if (desktop_icons[desktop_drag].x > W - 96) desktop_icons[desktop_drag].x = W - 96;
                            if (desktop_icons[desktop_drag].y < 12) desktop_icons[desktop_drag].y = 12;
                            if (desktop_icons[desktop_drag].y > 650) desktop_icons[desktop_drag].y = 650;
                        }
                    }
                    if (file_drag >= 0 && page == FILES &&
                        (abs(x - file_mouse_x) > 7 || abs(y - file_mouse_y) > 7)) file_dragged = true;
#endif
                } else if (event.type == SDL_MOUSEBUTTONUP && event.button.button == SDL_BUTTON_LEFT) {
#ifndef BIT_EDITION_CYBER
                    if (desktop_drag >= 0) {
                        if (desktop_dragged) save_desktop_layout();
                        else if (desktop_mouse_clicks >= 2) desktop_open(desktop_drag);
                        desktop_drag = -1;
                        desktop_dragged = false;
                    }
                    if (file_drag >= 0) {
                        if (file_dragged) {
                            if (event.button.x >= 50 && event.button.x < 1230 &&
                                event.button.y >= 697 && event.button.y < 736)
                                desktop_pin_entry(file_drag);
                        } else open_entry(file_drag);
                        file_drag = -1;
                        file_dragged = false;
                    }
#endif
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
#ifndef BIT_EDITION_CYBER
                        if (desktop_icon_count > 0) {
                            if (key == SDLK_TAB || key == SDLK_RIGHT || key == SDLK_DOWN)
                                desktop_selected = (desktop_selected + 1) % desktop_icon_count;
                            else if (key == SDLK_LEFT || key == SDLK_UP)
                                desktop_selected = (desktop_selected + desktop_icon_count - 1) % desktop_icon_count;
                            else if (key == SDLK_RETURN) desktop_open(desktop_selected);
                        }
#else
                        if (key == SDLK_TAB || key == SDLK_RIGHT || key == SDLK_DOWN) selected=(selected+1)%CARD_COUNT;
                        else if (key == SDLK_LEFT || key == SDLK_UP) selected=(selected+CARD_COUNT-1)%CARD_COUNT;
                        else if (key == SDLK_RETURN) activate(selected);
#endif
                    } else if (page == LAUNCHER) {
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
