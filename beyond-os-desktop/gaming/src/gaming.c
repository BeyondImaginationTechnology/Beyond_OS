/* BIT OS Gaming 0.1: a local game hub with a playable arcade game. */
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

enum Page { HOME, LIBRARY, PLAY, SETTINGS };
static SDL_Renderer *renderer;
static TTF_Font *small_font, *font, *title_font, *hero_font;
static SDL_GameController *controller;
static enum Page page = HOME;
static bool running = true, playing, performance_preference;
static int score, misses, paddle_y = 374, rival_y = 374;
static float ball_x = 630, ball_y = 380, ball_vx = -5.2f, ball_vy = 2.8f;
static char config_path[PATH_CAP], status[160];
static const SDL_Color night = {16, 20, 36, 255};
static const SDL_Color surface = {29, 35, 57, 255};
static const SDL_Color card = {40, 47, 72, 255};
static const SDL_Color ink = {241, 244, 251, 255};
static const SDL_Color muted = {170, 181, 205, 255};
static const SDL_Color lime = {188, 234, 89, 255};
static const SDL_Color violet = {171, 137, 239, 255};
static const SDL_Color warm = {251, 181, 104, 255};

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

static void text(TTF_Font *face, const char *value, int x, int y, SDL_Color color)
{
    SDL_Surface *image;
    SDL_Texture *texture;
    if (!value || !*value) return;
    image = TTF_RenderUTF8_Blended(face, value, color);
    if (!image) return;
    texture = SDL_CreateTextureFromSurface(renderer, image);
    if (texture) {
        SDL_Rect dest = {x, y, image->w, image->h};
        SDL_RenderCopy(renderer, texture, NULL, &dest);
        SDL_DestroyTexture(texture);
    }
    SDL_FreeSurface(image);
}

static void card_box(int x, int y, int w, int h, SDL_Color accent)
{
    box(x, y, w, h, card);
    outline(x, y, w, h, (SDL_Color){80, 89, 119, 255});
    box(x, y, 6, h, accent);
}

static void button(const char *label, int x, int y, int w, bool active)
{
    box(x, y, w, 40, active ? lime : surface);
    outline(x, y, w, 40, active ? lime : (SDL_Color){88, 99, 129, 255});
    text(small_font, label, x + 14, y + 10, active ? night : ink);
}

static void ensure_home(const char *home)
{
    const char *folders[] = {"Games", "Saves", "Screenshots", ".config", ".config/beyond-gaming"};
    size_t i;
    for (i = 0; i < sizeof folders / sizeof folders[0]; i++) {
        char path[PATH_CAP];
        int n = snprintf(path, sizeof path, "%s/%s", home, folders[i]);
        if (n > 0 && (size_t)n < sizeof path && mkdir(path, 0755) && errno != EEXIST)
            snprintf(status, sizeof status, "Some game folders could not be created.");
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
    fprintf(file, "performance_preference=%d\n", performance_preference);
    if (fclose(file) || rename(temporary, config_path)) {
        remove(temporary);
        snprintf(status, sizeof status, "Your play preference could not be saved.");
    }
}

static void load_settings(void)
{
    FILE *file = fopen(config_path, "rb");
    char line[64];
    int value;
    if (!file) return;
    while (fgets(line, sizeof line, file))
        if (sscanf(line, "performance_preference=%d", &value) == 1)
            performance_preference = value != 0;
    fclose(file);
}

static bool controller_ready(void)
{
    return controller && SDL_GameControllerGetAttached(controller);
}

static void open_controller(void)
{
    int i;
    if (controller_ready()) return;
    if (controller) { SDL_GameControllerClose(controller); controller = NULL; }
    for (i = 0; i < SDL_NumJoysticks(); i++) {
        if (SDL_IsGameController(i)) {
            controller = SDL_GameControllerOpen(i);
            if (controller) return;
        }
    }
}

static void header(void)
{
    time_t now = time(NULL);
    struct tm *local = localtime(&now);
    char clock[32] = "";
    if (local) strftime(clock, sizeof clock, "%a %d %b  %H:%M", local);
    box(0, 0, W, 70, surface);
    box(25, 17, 37, 37, lime);
    text(font, "G", 34, 19, night);
    text(font, "Gaming", 76, 20, ink);
    text(small_font, "PLAY SPACE", 191, 26, lime);
    text(small_font, controller_ready() ? "CONTROLLER READY" : "KEYBOARD READY", 1005, 20, lime);
    text(small_font, clock, 1036, 43, muted);
}

static void taskbar(void)
{
    const char *tabs[] = {"Home", "Library", "Play Rally", "Settings"};
    int i;
    box(0, 744, W, 56, surface);
    for (i = 0; i < 4; i++) button(tabs[i], 28 + 153 * i, 752, 138, page == (enum Page)i);
    text(small_font, "GAMING 0.1  •  LOCAL PLAY", 1008, 765, muted);
}

static void home_page(void)
{
    text(small_font, "YOUR SPACE TO PLAY", 52, 107, lime);
    text(hero_font, "Press play and settle in.", 50, 137, ink);
    text(font, "A quiet game space for your first session. No account required.", 52, 207, muted);
    card_box(50, 270, 570, 340, lime);
    text(small_font, "READY TO PLAY", 82, 303, lime);
    text(title_font, "Rally", 80, 344, ink);
    text(font, "A small arcade game, included with Gaming 0.1.", 82, 404, muted);
    text(small_font, "Keyboard: W / S or arrows. Controller: left stick or D-pad.", 82, 454, muted);
    button("Play Rally", 82, 526, 150, true);
    card_box(650, 270, 580, 160, violet);
    text(font, "Controller check", 680, 297, ink);
    text(small_font, controller_ready() ? "A game controller is connected and ready." : "Connect a controller or use the keyboard.", 680, 343, muted);
    card_box(650, 450, 580, 160, warm);
    text(font, "Your game folders", 680, 477, ink);
    text(small_font, "Games  •  Saves  •  Screenshots", 680, 526, warm);
    text(small_font, "Located in your Home folder.", 680, 562, muted);
}

static void library_page(void)
{
    text(title_font, "Game Library", 50, 102, ink);
    text(font, "Play locally and keep your files together.", 52, 159, muted);
    card_box(50, 225, 570, 350, lime);
    text(small_font, "INCLUDED GAME", 82, 254, lime);
    text(hero_font, "Rally", 80, 304, ink);
    text(small_font, "Arcade paddle game  •  keyboard or controller", 82, 376, muted);
    button("Play now", 82, 483, 134, true);
    card_box(650, 225, 580, 350, violet);
    text(font, "More games, your way", 680, 254, ink);
    text(small_font, "Your Games folder is ready for future local titles.", 680, 307, muted);
    text(small_font, "Store integrations and GPU drivers arrive in later releases.", 680, 348, muted);
    text(small_font, "Steam, Lutris and Gamescope are not bundled here.", 680, 389, violet);
    text(small_font, "No download or sign-in is required for Rally.", 680, 447, muted);
}

static void reset_ball(int direction)
{
    ball_x = 627; ball_y = 372;
    ball_vx = direction > 0 ? 5.2f : -5.2f;
    ball_vy = score % 2 ? -2.8f : 2.8f;
}

static void play_page(void)
{
    char count[64];
    text(title_font, "Rally", 50, 96, ink);
    text(small_font, "W / S, arrows or left stick to move. Space / A to pause or resume.", 52, 151, muted);
    box(50, 203, 1180, 473, (SDL_Color){21, 28, 47, 255});
    outline(50, 203, 1180, 473, (SDL_Color){82, 96, 128, 255});
    for (int y = 217; y < 656; y += 31) box(636, y, 4, 17, surface);
    box(83, paddle_y, 15, 92, lime);
    box(1182, rival_y, 15, 92, violet);
    box((int)ball_x, (int)ball_y, 16, 16, warm);
    snprintf(count, sizeof count, "Rallies %d    Misses %d", score, misses);
    text(small_font, count, 82, 226, ink);
    if (!playing) {
        box(413, 352, 450, 130, surface);
        text(font, "Press Space or controller A to play", 444, 380, ink);
        text(small_font, "Click Play below to start", 508, 426, muted);
    }
    button(playing ? "Pause" : "Play", 51, 688, 108, playing);
    button("Reset", 177, 688, 108, false);
}

static void settings_page(void)
{
    text(title_font, "Play Settings", 50, 102, ink);
    text(font, "Keep the setup simple, then choose what fits your hardware.", 52, 159, muted);
    card_box(50, 220, 1180, 143, lime);
    text(font, "Input", 80, 246, ink);
    text(small_font, controller_ready() ? "Connected controller detected by SDL2." : "Keyboard ready. Connect a controller to detect it here.", 80, 296, muted);
    button("Refresh", 1060, 272, 134, false);
    card_box(50, 390, 1180, 143, violet);
    text(font, "Performance preference", 80, 416, ink);
    text(small_font, "Saved for future hardware profiles. Does not change CPU or GPU settings in 0.1.", 80, 466, muted);
    button(performance_preference ? "Preferred" : "Standard", 1034, 442, 160, performance_preference);
    text(small_font, "GPU driver selection, low-latency audio and game stores are planned for later milestones.", 52, 590, warm);
}

static void draw(void)
{
    box(0, 0, W, H, night);
    header();
    if (page == HOME) home_page();
    else if (page == LIBRARY) library_page();
    else if (page == PLAY) play_page();
    else settings_page();
    if (*status) text(small_font, status, 52, 720, warm);
    taskbar();
    SDL_RenderPresent(renderer);
}

static void update_game(void)
{
    const Uint8 *keys = SDL_GetKeyboardState(NULL);
    int move = 0, rival_center;
    if (keys[SDL_SCANCODE_W] || keys[SDL_SCANCODE_UP]) move -= 8;
    if (keys[SDL_SCANCODE_S] || keys[SDL_SCANCODE_DOWN]) move += 8;
    if (controller_ready()) {
        Sint16 axis = SDL_GameControllerGetAxis(controller, SDL_CONTROLLER_AXIS_LEFTY);
        if (axis < -8000 || SDL_GameControllerGetButton(controller, SDL_CONTROLLER_BUTTON_DPAD_UP)) move -= 8;
        if (axis > 8000 || SDL_GameControllerGetButton(controller, SDL_CONTROLLER_BUTTON_DPAD_DOWN)) move += 8;
    }
    paddle_y += move;
    if (paddle_y < 216) paddle_y = 216;
    if (paddle_y > 570) paddle_y = 570;
    if (!playing || page != PLAY) return;
    ball_x += ball_vx; ball_y += ball_vy;
    if (ball_y < 216 || ball_y > 645) ball_vy = -ball_vy;
    rival_center = rival_y + 46;
    if (ball_y + 8 > rival_center + 7) rival_y += 5;
    else if (ball_y + 8 < rival_center - 7) rival_y -= 5;
    if (rival_y < 216) rival_y = 216;
    if (rival_y > 570) rival_y = 570;
    if (ball_x <= 99 && ball_x >= 82 && ball_y + 16 >= paddle_y && ball_y <= paddle_y + 92) {
        ball_vx = -ball_vx; score++;
    }
    if (ball_x >= 1167 && ball_x <= 1197 && ball_y + 16 >= rival_y && ball_y <= rival_y + 92)
        ball_vx = -ball_vx;
    if (ball_x < 50) { misses++; reset_ball(1); }
    if (ball_x > 1230) reset_ball(-1);
}

static void click(int x, int y)
{
    if (y >= 752) {
        if (x >= 28 && x < 166) page = HOME;
        else if (x >= 181 && x < 319) page = LIBRARY;
        else if (x >= 334 && x < 472) page = PLAY;
        else if (x >= 487 && x < 625) page = SETTINGS;
        return;
    }
    if (page == HOME && x >= 82 && x < 232 && y >= 526 && y < 566) { page = PLAY; playing = true; }
    else if (page == LIBRARY && x >= 82 && x < 216 && y >= 483 && y < 523) { page = PLAY; playing = true; }
    else if (page == PLAY && y >= 688 && y < 728) {
        if (x >= 51 && x < 159) playing = !playing;
        else if (x >= 177 && x < 285) { score = misses = 0; reset_ball(-1); playing = false; }
    } else if (page == SETTINGS) {
        if (x >= 1060 && x < 1194 && y >= 272 && y < 312) open_controller();
        else if (x >= 1034 && x < 1194 && y >= 442 && y < 482) {
            performance_preference = !performance_preference;
            save_settings();
            snprintf(status, sizeof status, "Preference saved for a future hardware profile.");
        }
    }
}

int main(void)
{
    SDL_Window *window;
    SDL_Event event;
    const char *home = getenv("HOME");
    Uint32 last_frame = 0;
    if (!home || !*home) home = "/home/home";
    ensure_home(home);
    if (snprintf(config_path, sizeof config_path, "%s/.config/beyond-gaming/settings", home) >= (int)sizeof config_path)
        return 1;
    load_settings();
    if (SDL_Init(SDL_INIT_VIDEO | SDL_INIT_GAMECONTROLLER) || TTF_Init()) return 1;
    window = SDL_CreateWindow("BIT OS Gaming", SDL_WINDOWPOS_CENTERED, SDL_WINDOWPOS_CENTERED, W, H, 0);
    if (!window) return 1;
    renderer = SDL_CreateRenderer(window, -1, SDL_RENDERER_ACCELERATED | SDL_RENDERER_PRESENTVSYNC);
    if (!renderer) renderer = SDL_CreateRenderer(window, -1, SDL_RENDERER_SOFTWARE);
    if (!renderer) return 1;
    small_font = TTF_OpenFont("/usr/share/fonts/dejavu/DejaVuSans.ttf", 17);
    font = TTF_OpenFont("/usr/share/fonts/dejavu/DejaVuSans.ttf", 22);
    title_font = TTF_OpenFont("/usr/share/fonts/dejavu/DejaVuSans.ttf", 32);
    hero_font = TTF_OpenFont("/usr/share/fonts/dejavu/DejaVuSans.ttf", 45);
    if (!small_font || !font || !title_font || !hero_font) return 1;
    open_controller();
    while (running) {
        while (SDL_PollEvent(&event)) {
            if (event.type == SDL_QUIT) running = false;
            else if (event.type == SDL_CONTROLLERDEVICEADDED || event.type == SDL_CONTROLLERDEVICEREMOVED) open_controller();
            else if (event.type == SDL_MOUSEBUTTONUP && event.button.button == SDL_BUTTON_LEFT) click(event.button.x, event.button.y);
            else if (event.type == SDL_KEYUP) {
                if (event.key.keysym.sym == SDLK_ESCAPE) { if (page == PLAY) { page = HOME; playing = false; } else running = false; }
                else if (event.key.keysym.sym == SDLK_SPACE && page == PLAY) playing = !playing;
            } else if (event.type == SDL_CONTROLLERBUTTONUP && page == PLAY && event.cbutton.button == SDL_CONTROLLER_BUTTON_A)
                playing = !playing;
        }
        if (SDL_GetTicks() - last_frame >= 16) {
            update_game(); draw(); last_frame = SDL_GetTicks();
        } else SDL_Delay(1);
    }
    if (controller) SDL_GameControllerClose(controller);
    TTF_CloseFont(hero_font); TTF_CloseFont(title_font); TTF_CloseFont(font); TTF_CloseFont(small_font);
    SDL_DestroyRenderer(renderer); SDL_DestroyWindow(window); TTF_Quit(); SDL_Quit();
    return 0;
}
