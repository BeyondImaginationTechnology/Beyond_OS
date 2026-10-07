/* BIT OS Academy 0.1: an offline-first learning workspace. */
#define _POSIX_C_SOURCE 200809L
#define SDL_MAIN_HANDLED
#include <SDL.h>
#include <SDL_ttf.h>
#include <errno.h>
#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/stat.h>
#include <time.h>

#define W 1280
#define H 800
#define PATH_CAP 4096

enum Page { HUB, LIBRARY, FOCUS, SUPPORT };
static SDL_Renderer *renderer;
static TTF_Font *small_font, *font, *title_font, *hero_font;
static enum Page page = HUB;
static int focus_minutes = 25;
static bool focus_running;
static char status[160], config_path[PATH_CAP];
static const SDL_Color ink = {35, 48, 68, 255};
static const SDL_Color paper = {248, 246, 238, 255};
static const SDL_Color muted = {104, 116, 133, 255};
static const SDL_Color sky = {75, 155, 211, 255};
static const SDL_Color leaf = {82, 150, 112, 255};
static const SDL_Color gold = {237, 182, 81, 255};

static void box(int x, int y, int w, int h, SDL_Color color)
{
    SDL_Rect rect = {x, y, w, h};
    SDL_SetRenderDrawColor(renderer, color.r, color.g, color.b, color.a);
    SDL_RenderFillRect(renderer, &rect);
}

static void outline(int x, int y, int w, int h, SDL_Color color)
{
    SDL_Rect rect = {x, y, w, h};
    SDL_SetRenderDrawColor(renderer, color.r, color.g, color.b, color.a);
    SDL_RenderDrawRect(renderer, &rect);
}

static int text(TTF_Font *face, const char *value, int x, int y, SDL_Color color)
{
    SDL_Surface *surface;
    SDL_Texture *texture;
    int width;
    if (!value || !*value) return 0;
    surface = TTF_RenderUTF8_Blended(face, value, color);
    if (!surface) return 0;
    texture = SDL_CreateTextureFromSurface(renderer, surface);
    width = surface->w;
    if (texture) {
        SDL_Rect dest = {x, y, surface->w, surface->h};
        SDL_RenderCopy(renderer, texture, NULL, &dest);
        SDL_DestroyTexture(texture);
    }
    SDL_FreeSurface(surface);
    return width;
}

static void save_settings(void)
{
    char temporary[PATH_CAP];
    FILE *file;
    int n = snprintf(temporary, sizeof temporary, "%s.tmp", config_path);
    if (n < 0 || (size_t)n >= sizeof temporary) return;
    file = fopen(temporary, "wb");
    if (!file) return;
    fprintf(file, "focus_minutes=%d\n", focus_minutes);
    if (fclose(file) || rename(temporary, config_path)) {
        remove(temporary);
        snprintf(status, sizeof status, "Your focus choice could not be saved.");
    }
}

static void load_settings(void)
{
    FILE *file = fopen(config_path, "rb");
    char line[64];
    int value;
    if (!file) return;
    while (fgets(line, sizeof line, file)) {
        if (sscanf(line, "focus_minutes=%d", &value) == 1 && value >= 5 && value <= 90)
            focus_minutes = value;
    }
    fclose(file);
}

static void ensure_learning_home(const char *home)
{
    const char *folders[] = {"Courses", "Courses/Offline", "Assignments", "Notes", "Reading", ".config", ".config/beyond-academy"};
    size_t i;
    for (i = 0; i < sizeof folders / sizeof folders[0]; i++) {
        char path[PATH_CAP];
        int n = snprintf(path, sizeof path, "%s/%s", home, folders[i]);
        if (n > 0 && (size_t)n < sizeof path && mkdir(path, 0755) && errno != EEXIST)
            snprintf(status, sizeof status, "Some learning folders could not be created.");
    }
}

static void background(void)
{
    int y, i;
    for (y = 0; y < H; y++) {
        float p = (float)y / (float)H;
        SDL_Color color = {(Uint8)(230 - 32 * p), (Uint8)(241 - 18 * p), (Uint8)(243 - 11 * p), 255};
        box(0, y, W, 1, color);
    }
    SDL_SetRenderDrawBlendMode(renderer, SDL_BLENDMODE_BLEND);
    for (i = 0; i < 10; i++) {
        SDL_Color cloud = {255, 255, 255, (Uint8)(36 - i * 2)};
        box(600 - i * 35, 70 + i * 25, 670 + i * 70, 240 + i * 30, cloud);
    }
    SDL_SetRenderDrawBlendMode(renderer, SDL_BLENDMODE_NONE);
}

static void pill(const char *label, int x, int y, int w, bool active)
{
    SDL_Color fill = active ? sky : (SDL_Color){255, 255, 255, 235};
    box(x, y, w, 38, fill);
    outline(x, y, w, 38, active ? sky : (SDL_Color){201, 212, 221, 255});
    text(small_font, label, x + 14, y + 10, active ? paper : ink);
}

static void topbar(void)
{
    time_t now = time(NULL);
    struct tm *local = localtime(&now);
    char clock[40] = "";
    if (local) strftime(clock, sizeof clock, "%a %d %b   %H:%M", local);
    box(0, 0, W, 68, (SDL_Color){255, 255, 255, 246});
    box(24, 17, 34, 34, sky);
    text(font, "A", 32, 18, paper);
    text(font, "Academy", 71, 18, ink);
    text(small_font, "LEARNING SPACE", 180, 24, sky);
    text(small_font, "Student workspace", 1015, 23, leaf);
    text(small_font, clock, 1044, 42, muted);
}

static void taskbar(void)
{
    const char *labels[] = {"Home", "Library", "Focus", "Support"};
    int i;
    box(0, 744, W, 56, (SDL_Color){255, 255, 255, 249});
    for (i = 0; i < 4; i++) pill(labels[i], 30 + i * 142, 753, 128, page == (enum Page)i);
    text(small_font, "ACADEMY 0.1  •  OFFLINE-READY LEARNING", 846, 764, muted);
}

static void card(int x, int y, int w, int h, SDL_Color stripe)
{
    box(x, y, w, h, (SDL_Color){255, 255, 255, 237});
    outline(x, y, w, h, (SDL_Color){213, 222, 227, 255});
    box(x, y, 7, h, stripe);
}

static void draw_hub(void)
{
    text(small_font, "A CALM PLACE TO LEARN", 52, 105, sky);
    text(hero_font, "Ready when you are.", 50, 136, ink);
    text(font, "Open a course, capture a thought, or begin a quiet focus session.", 53, 207, muted);
    card(50, 270, 550, 210, sky);
    text(font, "Today's learning plan", 76, 292, ink);
    text(small_font, "1", 82, 344, sky); text(font, "Review offline course material", 112, 338, ink);
    text(small_font, "2", 82, 386, leaf); text(font, "Write one assignment note", 112, 380, ink);
    text(small_font, "3", 82, 428, gold); text(font, "Take a focused learning break", 112, 422, ink);
    card(50, 510, 550, 150, leaf);
    text(font, "Your learning folders", 76, 532, ink);
    text(small_font, "Courses", 77, 578, leaf); text(small_font, "Assignments", 192, 578, leaf);
    text(small_font, "Notes", 344, 578, leaf); text(small_font, "Reading", 432, 578, leaf);
    text(small_font, "They are ready in your Home folder.", 76, 620, muted);
    card(650, 105, 580, 555, sky);
    text(font, "Continue learning", 680, 132, ink);
    text(small_font, "Your first Academy session stays simple and private.", 680, 167, muted);
    box(680, 215, 520, 122, (SDL_Color){231, 243, 251, 255});
    text(small_font, "OFFLINE COURSE LIBRARY", 707, 235, sky);
    text(title_font, "Learn at your pace", 706, 260, ink);
    text(small_font, "Open library", 706, 309, muted);
    box(680, 365, 520, 122, (SDL_Color){234, 246, 238, 255});
    text(small_font, "FOCUS SESSION", 707, 385, leaf);
    { char duration[64]; snprintf(duration, sizeof duration, "%d minutes of space to concentrate", focus_minutes); text(font, duration, 706, 415, ink); }
    text(small_font, "Start when ready", 706, 455, muted);
    pill("Open library", 680, 533, 150, false);
    pill("Start focus", 850, 533, 150, false);
    text(small_font, "Student space · teacher tools stay separate", 680, 620, muted);
}

static void draw_library(void)
{
    const char *courses[][3] = {
        {"Explore & Read", "Offline course packs and articles", "READ"},
        {"Make & Solve", "Python, writing and project notes", "MAKE"},
        {"Study Skills", "Planning, research and reflection", "GROW"},
        {"Teacher hand-off", "Share work when you choose", "SHARE"}
    };
    int i;
    text(title_font, "Learning Library", 50, 94, ink);
    text(font, "Keep course materials nearby, even when the network is not.", 52, 150, muted);
    for (i = 0; i < 4; i++) {
        int x = 50 + (i % 2) * 610, y = 210 + (i / 2) * 196;
        SDL_Color color = i == 1 ? leaf : (i == 2 ? gold : sky);
        card(x, y, 580, 164, color);
        text(small_font, courses[i][2], x + 28, y + 22, color);
        text(font, courses[i][0], x + 28, y + 52, ink);
        text(small_font, courses[i][1], x + 28, y + 88, muted);
        pill("Open", x + 28, y + 112, 92, false);
    }
    text(small_font, "Course delivery and educator management are planned for a later Academy milestone.", 52, 680, muted);
}

static void draw_focus(void)
{
    char duration[64];
    snprintf(duration, sizeof duration, "%d minutes", focus_minutes);
    text(title_font, "Focus Session", 50, 94, ink);
    text(font, "Make room for one thing at a time.", 52, 150, muted);
    card(50, 210, 1180, 360, leaf);
    text(small_font, focus_running ? "FOCUS IS ACTIVE" : "READY WHEN YOU ARE", 80, 244, leaf);
    text(hero_font, duration, 78, 286, ink);
    text(font, "Choose a gentle session length. Your choice is remembered here.", 80, 365, muted);
    pill("25 min", 80, 430, 110, focus_minutes == 25);
    pill("45 min", 212, 430, 110, focus_minutes == 45);
    pill("60 min", 344, 430, 110, focus_minutes == 60);
    pill(focus_running ? "Pause" : "Start", 80, 500, 120, focus_running);
    text(small_font, "No account, tracker or distraction feed is required for this space.", 80, 625, muted);
}

static void draw_support(void)
{
    text(title_font, "Support & boundaries", 50, 94, ink);
    text(font, "Academy keeps student work separate from teacher administration.", 52, 150, muted);
    card(50, 210, 560, 310, sky);
    text(font, "Student space", 78, 240, ink);
    text(small_font, "• Keep courses, notes and assignments in your Home folder", 78, 294, muted);
    text(small_font, "• Safe-search and course settings are visible", 78, 336, muted);
    text(small_font, "• Share work intentionally", 78, 378, muted);
    card(670, 210, 560, 310, leaf);
    text(font, "Teacher administration", 698, 240, ink);
    text(small_font, "• Separate managed account and permissions", 698, 294, muted);
    text(small_font, "• Course publishing arrives in a future release", 698, 336, muted);
    text(small_font, "• This preview does not apply school management policies", 698, 378, muted);
}

static void draw(void)
{
    background(); topbar();
    if (page == HUB) draw_hub(); else if (page == LIBRARY) draw_library();
    else if (page == FOCUS) draw_focus(); else draw_support();
    if (*status) text(small_font, status, 52, 715, leaf);
    taskbar(); SDL_RenderPresent(renderer);
}

static void click_page(int x, int y)
{
    if (y >= 744) {
        if (x >= 30 && x < 158) page = HUB;
        else if (x >= 172 && x < 300) page = LIBRARY;
        else if (x >= 314 && x < 442) page = FOCUS;
        else if (x >= 456 && x < 584) page = SUPPORT;
    } else if (page == HUB) {
        if (x >= 680 && x < 830 && y >= 533 && y < 571) page = LIBRARY;
        else if (x >= 850 && x < 1000 && y >= 533 && y < 571) page = FOCUS;
    } else if (page == FOCUS) {
        if (y >= 430 && y < 468) {
            if (x >= 80 && x < 190) focus_minutes = 25;
            else if (x >= 212 && x < 322) focus_minutes = 45;
            else if (x >= 344 && x < 454) focus_minutes = 60;
            save_settings();
        } else if (x >= 80 && x < 200 && y >= 500 && y < 538) {
            focus_running = !focus_running;
            snprintf(status, sizeof status, "%s focus session.", focus_running ? "Started" : "Paused");
        }
    }
}

int main(void)
{
    const char *home = getenv("BEYOND_DATA_HOME");
    SDL_Window *window;
    bool running = true;
    if (!home) home = getenv("HOME");
    if (!home || !*home) return 1;
    ensure_learning_home(home);
    if (snprintf(config_path, sizeof config_path, "%s/.config/beyond-academy/settings", home) < 0) return 1;
    load_settings();
    SDL_SetMainReady();
    if (SDL_Init(SDL_INIT_VIDEO) || TTF_Init()) return 1;
    small_font = TTF_OpenFont("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 15);
    font = TTF_OpenFont("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 21);
    title_font = TTF_OpenFont("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 40);
    hero_font = TTF_OpenFont("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 52);
    if (!small_font || !font || !title_font || !hero_font) return 1;
    window = SDL_CreateWindow("BIT OS Academy 0.1", 0, 0, W, H, SDL_WINDOW_BORDERLESS);
    if (!window) return 1;
    renderer = SDL_CreateRenderer(window, -1, SDL_RENDERER_SOFTWARE);
    if (!renderer) return 1;
    SDL_RenderSetLogicalSize(renderer, W, H);
    while (running) {
        SDL_Event event;
        while (SDL_PollEvent(&event)) {
            if (event.type == SDL_QUIT) running = false;
            else if (event.type == SDL_MOUSEBUTTONDOWN && event.button.button == SDL_BUTTON_LEFT)
                click_page(event.button.x, event.button.y);
            else if (event.type == SDL_KEYDOWN) {
                if (event.key.keysym.sym == SDLK_ESCAPE || event.key.keysym.sym == SDLK_1) page = HUB;
                else if (event.key.keysym.sym == SDLK_2) page = LIBRARY;
                else if (event.key.keysym.sym == SDLK_3) page = FOCUS;
                else if (event.key.keysym.sym == SDLK_4) page = SUPPORT;
            }
        }
        draw(); SDL_Delay(33);
    }
    SDL_DestroyRenderer(renderer); SDL_DestroyWindow(window);
    TTF_CloseFont(small_font); TTF_CloseFont(font); TTF_CloseFont(title_font); TTF_CloseFont(hero_font);
    TTF_Quit(); SDL_Quit(); return 0;
}
