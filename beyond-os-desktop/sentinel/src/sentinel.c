/* BIT OS Sentinel 0.1: local-first device posture console. */
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

enum Page { OVERVIEW, POLICIES, REPORTS, ABOUT };
static SDL_Renderer *renderer;
static TTF_Font *small_font, *font, *title_font, *hero_font;
static enum Page page = OVERVIEW;
static bool audit_enabled = true, enrollment_opt_in, updates_auto = true;
static char status[160], config_path[PATH_CAP];
static const SDL_Color night = {17, 27, 38, 255};
static const SDL_Color panel = {29, 45, 58, 255};
static const SDL_Color card = {39, 58, 72, 255};
static const SDL_Color ink = {225, 237, 239, 255};
static const SDL_Color muted = {158, 179, 185, 255};
static const SDL_Color teal = {65, 206, 182, 255};
static const SDL_Color cyan = {83, 178, 236, 255};
static const SDL_Color amber = {246, 185, 83, 255};
static const SDL_Color red = {227, 109, 112, 255};

static void box(int x, int y, int w, int h, SDL_Color color)
{
    SDL_Rect r = {x, y, w, h};
    SDL_SetRenderDrawColor(renderer, color.r, color.g, color.b, color.a);
    SDL_RenderFillRect(renderer, &r);
}

static void outline(int x, int y, int w, int h, SDL_Color color)
{
    SDL_Rect r = {x, y, w, h};
    SDL_SetRenderDrawColor(renderer, color.r, color.g, color.b, color.a);
    SDL_RenderDrawRect(renderer, &r);
}

static int text(TTF_Font *face, const char *value, int x, int y, SDL_Color color)
{
    SDL_Surface *surface;
    SDL_Texture *texture;
    int width = 0;
    if (!value || !*value) return 0;
    surface = TTF_RenderUTF8_Blended(face, value, color);
    if (!surface) return 0;
    texture = SDL_CreateTextureFromSurface(renderer, surface);
    if (texture) {
        SDL_Rect dest = {x, y, surface->w, surface->h};
        SDL_RenderCopy(renderer, texture, NULL, &dest);
        SDL_DestroyTexture(texture);
    }
    width = surface->w;
    SDL_FreeSurface(surface);
    return width;
}

static void card_box(int x, int y, int w, int h, SDL_Color accent)
{
    box(x, y, w, h, card);
    outline(x, y, w, h, (SDL_Color){67, 87, 99, 255});
    box(x, y, 6, h, accent);
}

static void pill(const char *label, int x, int y, int w, bool active)
{
    SDL_Color fill = active ? teal : panel;
    box(x, y, w, 36, fill);
    outline(x, y, w, 36, active ? teal : (SDL_Color){74, 98, 109, 255});
    text(small_font, label, x + 13, y + 9, active ? night : ink);
}

static void ensure_home(const char *home)
{
    const char *folders[] = {"Fleet", "Policies", "Incidents", "Reports", ".config", ".config/beyond-sentinel"};
    size_t i;
    for (i = 0; i < sizeof folders / sizeof folders[0]; i++) {
        char path[PATH_CAP];
        int n = snprintf(path, sizeof path, "%s/%s", home, folders[i]);
        if (n > 0 && (size_t)n < sizeof path && mkdir(path, 0755) && errno != EEXIST)
            snprintf(status, sizeof status, "Some Sentinel folders could not be created.");
    }
}

static void save_settings(void)
{
    char temporary[PATH_CAP];
    FILE *file;
    int n = snprintf(temporary, sizeof temporary, "%s.tmp", config_path);
    if (n < 0 || (size_t)n >= sizeof temporary) return;
    file = fopen(temporary, "wb");
    if (!file) return;
    fprintf(file, "audit=%d\nenrollment=%d\nupdates=%d\n", audit_enabled, enrollment_opt_in, updates_auto);
    if (fclose(file) || rename(temporary, config_path)) {
        remove(temporary);
        snprintf(status, sizeof status, "Your Sentinel preferences could not be saved.");
    }
}

static void load_settings(void)
{
    FILE *file = fopen(config_path, "rb");
    char line[64];
    int value;
    if (!file) return;
    while (fgets(line, sizeof line, file)) {
        if (sscanf(line, "audit=%d", &value) == 1) audit_enabled = value != 0;
        if (sscanf(line, "enrollment=%d", &value) == 1) enrollment_opt_in = value != 0;
        if (sscanf(line, "updates=%d", &value) == 1) updates_auto = value != 0;
    }
    fclose(file);
}

static void topbar(void)
{
    time_t now = time(NULL);
    struct tm *local = localtime(&now);
    char clock[40] = "";
    if (local) strftime(clock, sizeof clock, "%a %d %b   %H:%M", local);
    box(0, 0, W, 72, panel);
    box(24, 18, 36, 36, teal);
    text(font, "S", 33, 18, night);
    text(font, "Sentinel", 73, 20, ink);
    text(small_font, "DEVICE TRUST CONSOLE", 185, 26, teal);
    text(small_font, "LOCAL-FIRST", 1034, 23, teal);
    text(small_font, clock, 1026, 43, muted);
}

static void taskbar(void)
{
    const char *labels[] = {"Overview", "Policies", "Reports", "About"};
    int i;
    box(0, 744, W, 56, panel);
    for (i = 0; i < 4; i++) pill(labels[i], 28 + i * 148, 754, 134, page == (enum Page)i);
    text(small_font, "SENTINEL 0.1  •  NO REMOTE LOGIN BY DEFAULT", 846, 765, muted);
}

static void draw_overview(void)
{
    text(small_font, "THIS DEVICE", 50, 104, teal);
    text(hero_font, "Quietly protected.", 50, 135, ink);
    text(font, "A clear view of local safeguards, without a cloud account requirement.", 52, 207, muted);
    card_box(50, 270, 550, 170, teal);
    text(small_font, "LOCAL POSTURE", 78, 295, teal);
    text(title_font, "Protected baseline", 77, 325, ink);
    text(small_font, "Inbound access is denied and remote login remains off.", 78, 375, muted);
    text(small_font, "Signed updates and local audit records are enabled.", 78, 405, muted);
    card_box(50, 470, 550, 165, cyan);
    text(font, "Your Sentinel folders", 77, 495, ink);
    text(small_font, "Fleet", 78, 540, cyan); text(small_font, "Policies", 188, 540, cyan);
    text(small_font, "Incidents", 324, 540, cyan); text(small_font, "Reports", 462, 540, cyan);
    text(small_font, "They stay in your Home folder.", 78, 587, muted);
    card_box(650, 104, 580, 531, teal);
    text(font, "At a glance", 680, 132, ink);
    box(680, 180, 520, 82, (SDL_Color){28, 80, 75, 255});
    text(small_font, "STATUS", 706, 198, teal);
    text(font, "Ready for local work", 706, 224, ink);
    text(small_font, "No fleet connection", 1040, 225, muted);
    text(small_font, "• Remote login", 707, 300, muted); text(small_font, "OFF", 1080, 300, teal);
    text(small_font, "• Audit records", 707, 343, muted); text(small_font, audit_enabled ? "ON" : "PAUSED", 1080, 343, audit_enabled ? teal : amber);
    text(small_font, "• Automatic updates", 707, 386, muted); text(small_font, updates_auto ? "ON" : "MANUAL", 1080, 386, updates_auto ? teal : amber);
    text(small_font, "• Fleet enrollment", 707, 429, muted); text(small_font, enrollment_opt_in ? "REQUESTED" : "NOT ENROLLED", 1010, 429, enrollment_opt_in ? amber : teal);
    pill("Review policies", 680, 515, 156, false);
    text(small_font, "Fleet enrollment is opt-in and is not connected in this preview.", 680, 592, muted);
}

static void toggle_row(const char *title, const char *detail, int y, bool value, SDL_Color accent)
{
    card_box(50, y, 1180, 116, accent);
    text(font, title, 79, y + 22, ink);
    text(small_font, detail, 79, y + 58, muted);
    pill(value ? "Enabled" : "Off", 1070, y + 40, 126, value);
}

static void draw_policies(void)
{
    text(title_font, "Local policies", 50, 98, ink);
    text(font, "Every setting is visible here. Sentinel does not enroll this device automatically.", 52, 152, muted);
    toggle_row("Local audit records", "Keep a local activity trail for this device. No report is transmitted.", 210, audit_enabled, teal);
    toggle_row("Signed updates", "Accept updates only when they have been verified by their release process.", 350, updates_auto, cyan);
    toggle_row("Fleet enrollment", "Prepare a local enrollment preference. A hosted fleet service is not included in 0.1.", 490, enrollment_opt_in, amber);
    text(small_font, "Click a policy card to change its local preference.", 52, 658, muted);
}

static void draw_reports(void)
{
    text(title_font, "Reports & records", 50, 98, ink);
    text(font, "Keep incident notes and reports on this device until you choose to share them.", 52, 152, muted);
    card_box(50, 220, 560, 226, cyan);
    text(small_font, "LOCAL REPORTS", 79, 247, cyan);
    text(font, "Start with a clean record", 78, 282, ink);
    text(small_font, "Reports folder: ~/Reports", 78, 325, muted);
    text(small_font, "Incidents folder: ~/Incidents", 78, 356, muted);
    text(small_font, "No activity has been sent from this preview.", 78, 398, teal);
    card_box(670, 220, 560, 226, amber);
    text(small_font, "FLEET VISIBILITY", 699, 247, amber);
    text(font, "Opt in when you are ready", 698, 282, ink);
    text(small_font, enrollment_opt_in ? "A local enrollment request is saved." : "This device is not enrolled.", 698, 325, muted);
    text(small_font, "Sentinel 0.1 does not provide a hosted fleet service.", 698, 356, muted);
    pill("Review policies", 698, 388, 156, false);
}

static void draw_about(void)
{
    text(title_font, "About Sentinel", 50, 98, ink);
    text(font, "A stable local foundation for devices that need visible boundaries.", 52, 152, muted);
    card_box(50, 220, 1180, 290, teal);
    text(font, "What this preview provides", 80, 251, ink);
    text(small_font, "• Local device posture and policy preferences", 80, 302, muted);
    text(small_font, "• Dedicated Fleet, Policies, Incidents and Reports folders", 80, 340, muted);
    text(small_font, "• Deny-inbound and no-remote-login defaults represented clearly", 80, 378, muted);
    text(small_font, "• A future fleet connection only after a deliberate opt-in", 80, 416, muted);
    text(small_font, "Sentinel v0.1 is a development preview. It does not claim managed security monitoring.", 80, 468, amber);
}

static void draw(void)
{
    box(0, 0, W, H, night);
    topbar();
    if (page == OVERVIEW) draw_overview();
    else if (page == POLICIES) draw_policies();
    else if (page == REPORTS) draw_reports();
    else draw_about();
    if (*status) text(small_font, status, 52, 710, red);
    taskbar();
    SDL_RenderPresent(renderer);
}

static void handle_click(int x, int y)
{
    if (y >= 744) {
        if (x >= 28 && x < 162) page = OVERVIEW;
        else if (x >= 176 && x < 310) page = POLICIES;
        else if (x >= 324 && x < 458) page = REPORTS;
        else if (x >= 472 && x < 606) page = ABOUT;
        return;
    }
    if (page == OVERVIEW && y >= 510 && y <= 560) page = POLICIES;
    else if (page == POLICIES) {
        if (y >= 210 && y < 326) audit_enabled = !audit_enabled;
        else if (y >= 350 && y < 466) updates_auto = !updates_auto;
        else if (y >= 490 && y < 606) enrollment_opt_in = !enrollment_opt_in;
        else return;
        save_settings();
        snprintf(status, sizeof status, "Local preference saved. It does not contact a fleet service.");
    } else if (page == REPORTS && y >= 380 && y <= 438) page = POLICIES;
}

int main(void)
{
    SDL_Window *window;
    SDL_Event event;
    const char *home = getenv("HOME");
    bool running = true;
    if (!home || !*home) home = "/home/home";
    ensure_home(home);
    if (snprintf(config_path, sizeof config_path, "%s/.config/beyond-sentinel/settings", home) >= (int)sizeof config_path)
        return 1;
    load_settings();
    if (SDL_Init(SDL_INIT_VIDEO) || TTF_Init()) return 1;
    window = SDL_CreateWindow("BIT OS Sentinel", SDL_WINDOWPOS_CENTERED, SDL_WINDOWPOS_CENTERED, W, H, 0);
    if (!window) return 1;
    renderer = SDL_CreateRenderer(window, -1, SDL_RENDERER_ACCELERATED | SDL_RENDERER_PRESENTVSYNC);
    if (!renderer) return 1;
    small_font = TTF_OpenFont("/usr/share/fonts/dejavu/DejaVuSans.ttf", 17);
    font = TTF_OpenFont("/usr/share/fonts/dejavu/DejaVuSans.ttf", 22);
    title_font = TTF_OpenFont("/usr/share/fonts/dejavu/DejaVuSans.ttf", 31);
    hero_font = TTF_OpenFont("/usr/share/fonts/dejavu/DejaVuSans.ttf", 47);
    if (!small_font || !font || !title_font || !hero_font) return 1;
    while (running) {
        while (SDL_PollEvent(&event)) {
            if (event.type == SDL_QUIT) running = false;
            else if (event.type == SDL_MOUSEBUTTONUP && event.button.button == SDL_BUTTON_LEFT)
                handle_click(event.button.x, event.button.y);
            else if (event.type == SDL_KEYUP && event.key.keysym.sym == SDLK_ESCAPE) running = false;
        }
        draw();
    }
    TTF_CloseFont(hero_font); TTF_CloseFont(title_font); TTF_CloseFont(font); TTF_CloseFont(small_font);
    SDL_DestroyRenderer(renderer); SDL_DestroyWindow(window); TTF_Quit(); SDL_Quit();
    return 0;
}
