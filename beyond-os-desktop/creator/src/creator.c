/* BIT OS Creator 0.1: personal creative workspace and first-run hub. */
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
#include <signal.h>
#include <unistd.h>

#define W 1280
#define H 800
#define APP_COUNT 12
#define WORKSPACE_COUNT 4
#define PLAYLIST_COUNT 6
#define PATH_CAP 4096

enum Page { HUB, APPS, WALLPAPERS, MUSIC };
typedef struct { const char *name, *detail, *command; int group; } App;
typedef struct { const char *name, *craft, *tagline; SDL_Color accent, deep, glow; } Workspace;

static SDL_Renderer *renderer;
static TTF_Font *small_font, *font, *title_font, *hero_font;
static enum Page page = HUB;
static int workspace, playlist;
static unsigned selected_apps = 7u, startup_apps;
static bool music_playing;
static char status[256], config_path[PATH_CAP];
static const SDL_Color white = {250, 246, 239, 255};
static const SDL_Color muted = {189, 183, 180, 255};

static const Workspace workspaces[WORKSPACE_COUNT] = {
    {"Golden Hour Studio", "VIDEO", "Warm light for stories in motion",
     {255,180,92,255}, {38,25,26,255}, {118,66,36,255}},
    {"Ink & Canvas", "VISUAL DESIGN", "Paper, graphite and a clear canvas",
     {240,125,112,255}, {31,28,32,255}, {101,56,59,255}},
    {"Electric Atelier", "CODE", "A focused studio with electric detail",
     {242,107,194,255}, {24,20,37,255}, {90,42,92,255}},
    {"Blue Note Studio", "SOUND & MUSIC", "A midnight room for rhythm and sound",
     {66,214,199,255}, {15,30,38,255}, {26,91,92,255}}
};

static const App apps[APP_COUNT] = {
    {"Beyond Tattoo", "Sketches, references and stencils", "beyond-tattoo", 1},
    {"Beyond Music", "Playlists for the creative flow", "", 3},
    {"Beyond Wallpapers", "Shape the mood of your studio", "", 1},
    {"Kdenlive", "Video editing and timelines", "kdenlive", 0},
    {"OBS Studio", "Capture and live production", "obs", 0},
    {"Krita", "Digital painting and illustration", "krita", 1},
    {"Inkscape", "Vector design and typography", "inkscape", 1},
    {"GIMP", "Photo editing and composition", "gimp", 1},
    {"VSCodium", "Code editing without distractions", "codium", 2},
    {"Git", "Version creative work", "xterm", 2},
    {"Audacity", "Record and edit sound", "audacity", 3},
    {"LMMS", "Compose beats and arrangements", "lmms", 3}
};

static const char *playlists[PLAYLIST_COUNT] = {
    "Golden Hour Beats", "Focus Flow", "Lo-Fi Canvas",
    "Coding After Dark", "Studio Instrumentals", "Rainy Creative Session"
};

static void box(int x, int y, int w, int h, SDL_Color color)
{
    SDL_Rect rect = {x,y,w,h};
    SDL_SetRenderDrawColor(renderer,color.r,color.g,color.b,color.a);
    SDL_RenderFillRect(renderer,&rect);
}

static void outline(int x, int y, int w, int h, SDL_Color color)
{
    SDL_Rect rect = {x,y,w,h};
    SDL_SetRenderDrawColor(renderer,color.r,color.g,color.b,color.a);
    SDL_RenderDrawRect(renderer,&rect);
}

static int text(TTF_Font *face, const char *value, int x, int y, SDL_Color color)
{
    if (!value || !*value) return 0;
    SDL_Surface *surface = TTF_RenderUTF8_Blended(face,value,color);
    if (!surface) return 0;
    SDL_Texture *texture = SDL_CreateTextureFromSurface(renderer,surface);
    int width = surface->w;
    if (texture) {
        SDL_Rect dest = {x,y,surface->w,surface->h};
        SDL_RenderCopy(renderer,texture,NULL,&dest);
        SDL_DestroyTexture(texture);
    }
    SDL_FreeSurface(surface);
    return width;
}

static void save_settings(void)
{
    char temporary[PATH_CAP];
    int n = snprintf(temporary,sizeof temporary,"%s.tmp",config_path);
    if (n < 0 || (size_t)n >= sizeof temporary) return;
    FILE *file = fopen(temporary,"wb");
    if (!file) { snprintf(status,sizeof status,"Could not save your studio: %s",strerror(errno)); return; }
    bool okay = fprintf(file,"workspace=%d\napps=%u\nstartup=%u\nplaylist=%d\n",
                        workspace,selected_apps,startup_apps,playlist) > 0;
    if (fclose(file)) okay = false;
    if (!okay || rename(temporary,config_path)) {
        remove(temporary);
        snprintf(status,sizeof status,"Your choice is active, but could not be saved.");
    }
}

static void load_settings(void)
{
    FILE *file = fopen(config_path,"rb");
    if (!file) return;
    char line[128];
    while (fgets(line,sizeof line,file)) {
        unsigned value;
        if (sscanf(line,"workspace=%u",&value)==1 && value<WORKSPACE_COUNT) workspace=(int)value;
        else if (sscanf(line,"apps=%u",&value)==1) selected_apps=value & ((1u<<APP_COUNT)-1u);
        else if (sscanf(line,"startup=%u",&value)==1) startup_apps=value & ((1u<<APP_COUNT)-1u);
        else if (sscanf(line,"playlist=%u",&value)==1 && value<PLAYLIST_COUNT) playlist=(int)value;
    }
    fclose(file);
    startup_apps &= selected_apps;
}

static void ensure_studio(const char *home)
{
    const char *folders[] = {"Projects","Assets","Recordings","Templates","Exports","Fonts",".config",".config/beyond-creator"};
    for (size_t i=0;i<sizeof folders/sizeof folders[0];i++) {
        char path[PATH_CAP];
        int n=snprintf(path,sizeof path,"%s/%s",home,folders[i]);
        if (n>0 && (size_t)n<sizeof path && mkdir(path,0755) && errno!=EEXIST)
            snprintf(status,sizeof status,"Some studio folders could not be created.");
    }
}

static void background(void)
{
    const Workspace *ws=&workspaces[workspace];
    for (int y=0;y<H;y++) {
        float p=(float)y/(float)H;
        SDL_Color c={(Uint8)(ws->deep.r+(12*p)),(Uint8)(ws->deep.g+(9*p)),(Uint8)(ws->deep.b+(11*p)),255};
        box(0,y,W,1,c);
    }
    SDL_SetRenderDrawBlendMode(renderer,SDL_BLENDMODE_BLEND);
    for (int i=0;i<7;i++) {
        SDL_Color haze={ws->glow.r,ws->glow.g,ws->glow.b,(Uint8)(28-i*3)};
        box(760-i*30,75+i*24,560+i*60,360+i*34,haze);
    }
    for (int i=0;i<16;i++) {
        SDL_Color line={ws->accent.r,ws->accent.g,ws->accent.b,22};
        box(i*96-60,0,1,H,line);
    }
    SDL_SetRenderDrawBlendMode(renderer,SDL_BLENDMODE_NONE);
}

static void topbar(void)
{
    const Workspace *ws=&workspaces[workspace];
    SDL_Color bar={13,15,20,235}; box(0,0,W,66,bar);
    box(22,16,34,34,ws->accent);
    text(font,"C",31,18,ws->deep);
    text(font,"Creator Hub",70,18,white);
    text(small_font,workspaces[workspace].craft,228,23,ws->accent);
    time_t now=time(NULL); struct tm *local=localtime(&now); char clock[64]="";
    if (local) strftime(clock,sizeof clock,"%a %d %b   %H:%M",local);
    text(small_font,clock,1060,23,muted);
}

static void pill(const char *label,int x,int y,int w,bool active)
{
    const Workspace *ws=&workspaces[workspace];
    SDL_Color fill=active?ws->accent:(SDL_Color){42,41,47,245};
    box(x,y,w,38,fill); outline(x,y,w,38,active?ws->accent:(SDL_Color){75,72,78,255});
    text(small_font,label,x+16,y+10,active?ws->deep:white);
}

static void taskbar(void)
{
    const char *labels[]={"Hub","Apps","Wallpapers","Music"};
    box(0,744,W,56,(SDL_Color){12,14,18,246});
    for (int i=0;i<4;i++) pill(labels[i],30+i*142,753,128,page==(enum Page)i);
    const char *song=music_playing?playlists[playlist]:"Choose a background playlist";
    text(small_font,music_playing?"PLAYING":"BEYOND MUSIC",780,763,workspaces[workspace].accent);
    text(small_font,song,900,763,white);
}

static int app_total(void)
{
    int total=0; for (int i=0;i<APP_COUNT;i++) if (selected_apps&(1u<<i)) total++;
    return total;
}

static bool command_available(const char *command)
{
    if (!command || !*command) return true;
    const char *path=getenv("PATH"); if (!path) return false;
    char copy[PATH_CAP];
    if (strlen(path)>=sizeof copy) return false;
    snprintf(copy,sizeof copy,"%s",path);
    for (char *part=strtok(copy,":");part;part=strtok(NULL,":")) {
        char candidate[PATH_CAP];
        int n=snprintf(candidate,sizeof candidate,"%s/%s",part,command);
        if (n>0&&(size_t)n<sizeof candidate&&access(candidate,X_OK)==0) return true;
    }
    return false;
}

static void launch_startup_apps(void)
{
    int opened=0;
    for (int i=0;i<APP_COUNT;i++) {
        unsigned bit=1u<<i;
        if (!(startup_apps&bit)||!apps[i].command||!*apps[i].command||!command_available(apps[i].command)) continue;
        pid_t child=fork();
        if (child==0) { execlp(apps[i].command,apps[i].command,(char *)NULL); _exit(127); }
        if (child>0) opened++;
    }
    if (opened) snprintf(status,sizeof status,"Opened %d startup app%s.",opened,opened==1?"":"s");
}

static void draw_hub(void)
{
    const Workspace *ws=&workspaces[workspace];
    text(small_font,"MAKE IT YOURS",54,106,ws->accent);
    text(hero_font,"Your studio,",50,132,white);
    text(hero_font,"your way.",50,190,white);
    text(font,ws->tagline,54,267,muted);
    box(50,315,560,185,(SDL_Color){22,23,28,238});
    text(font,"Setup checklist",76,338,white);
    char count[64]; snprintf(count,sizeof count,"%d creative apps chosen",app_total());
    const char *steps[]={"Workspace profile selected",count,"Background playlist ready","Project folders created"};
    for (int i=0;i<4;i++) {
        box(78,385+i*27,14,14,ws->accent); text(small_font,"✓",80,381+i*27,ws->deep);
        text(small_font,steps[i],105,383+i*27,i==1&&app_total()==0?muted:white);
    }
    box(50,524,560,150,(SDL_Color){22,23,28,238});
    text(font,"Continue creating",76,546,white);
    text(small_font,"Projects",76,590,ws->accent); text(small_font,"Assets",205,590,ws->accent);
    text(small_font,"Recordings",316,590,ws->accent); text(small_font,"Exports",468,590,ws->accent);
    text(small_font,"Your folders are ready in Home.",76,628,muted);

    box(650,105,580,569,(SDL_Color){18,19,24,235});
    text(font,"Choose what belongs here",680,132,white);
    text(small_font,"Nothing is locked in. Change any choice whenever inspiration moves.",680,169,muted);
    for (int i=0;i<WORKSPACE_COUNT;i++) {
        int y=210+i*78; bool active=i==workspace;
        box(680,y,520,62,active?workspaces[i].glow:(SDL_Color){35,35,41,255});
        box(680,y,7,62,workspaces[i].accent);
        text(font,workspaces[i].name,704,y+8,white);
        text(small_font,workspaces[i].craft,704,y+37,workspaces[i].accent);
    }
    pill("Choose apps",680,552,154,false); pill("Play music",850,552,154,false);
    pill("Wallpapers",1020,552,154,false);
    text(small_font,"CREATOR 0.1  •  PERSONAL WORKSPACE PREVIEW",680,630,muted);
}

static void draw_apps(void)
{
    text(title_font,"Choose your apps",50,94,white);
    text(font,"Add the tools you want. AUTO controls what opens with your studio.",52,150,muted);
    for (int i=0;i<APP_COUNT;i++) {
        int col=i%3,row=i/3,x=50+col*405,y=205+row*123;
        bool chosen=(selected_apps&(1u<<i))!=0, automatic=(startup_apps&(1u<<i))!=0;
        SDL_Color fill=chosen?workspaces[workspace].glow:(SDL_Color){25,26,32,245};
        box(x,y,380,102,fill); outline(x,y,380,102,chosen?workspaces[workspace].accent:(SDL_Color){64,63,70,255});
        box(x+18,y+19,44,44,chosen?workspaces[workspace].accent:(SDL_Color){55,54,61,255});
        char initial[2]={apps[i].name[0],0}; text(font,initial,x+32,y+27,chosen?workspaces[workspace].deep:white);
        text(font,apps[i].name,x+78,y+13,white); text(small_font,apps[i].detail,x+78,y+45,muted);
        text(small_font,command_available(apps[i].command)?"READY":"PLANNED",x+18,y+75,
             command_available(apps[i].command)?workspaces[workspace].accent:muted);
        pill(chosen?"ADDED":"ADD",x+250,y+68,62,chosen);
        pill(automatic?"AUTO ✓":"AUTO",x+317,y+68,55,automatic);
    }
}

static void draw_wallpapers(void)
{
    text(title_font,"Beyond Wallpapers",50,94,white);
    text(font,"Pick a starting mood. Your apps and folders stay where you placed them.",52,150,muted);
    for (int i=0;i<WORKSPACE_COUNT;i++) {
        int x=50+(i%2)*610,y=210+(i/2)*222;
        box(x,y,580,194,workspaces[i].deep);
        for (int j=0;j<5;j++) box(x+330-j*20,y+20+j*18,250+j*35,110,workspaces[i].glow);
        box(x,y,8,194,workspaces[i].accent);
        text(font,workspaces[i].name,x+28,y+28,white); text(small_font,workspaces[i].craft,x+28,y+66,workspaces[i].accent);
        text(small_font,workspaces[i].tagline,x+28,y+107,muted);
        pill(i==workspace?"CURRENT":"USE THIS",x+28,y+142,110,i==workspace);
    }
}

static void draw_music(void)
{
    text(title_font,"Beyond Music",50,94,white);
    text(font,"Choose the sound behind your workspace.",52,150,muted);
    box(50,205,1180,116,(SDL_Color){20,23,29,242});
    box(78,230,64,64,workspaces[workspace].accent);
    text(title_font,music_playing?"Ⅱ":"▶",94,236,workspaces[workspace].deep);
    text(small_font,music_playing?"NOW PLAYING":"READY WHEN YOU ARE",170,230,workspaces[workspace].accent);
    text(title_font,playlists[playlist],168,252,white);
    for (int i=0;i<PLAYLIST_COUNT;i++) {
        int col=i%2,row=i/2,x=50+col*610,y=350+row*106;
        bool active=i==playlist;
        box(x,y,580,84,active?workspaces[workspace].glow:(SDL_Color){26,27,33,245});
        text(font,playlists[i],x+25,y+16,white);
        text(small_font,i==0?"warm beats":i==1?"deep focus":i==2?"soft textures":i==3?"night energy":i==4?"instrumental": "rain ambience",x+25,y+50,active?workspaces[workspace].accent:muted);
        pill(active?"SELECTED":"PLAY",x+452,y+23,102,active);
    }
    text(small_font,"Creator 0.1 saves your playlist choice. Streaming and local-library playback are the next Beyond Music milestone.",52,685,muted);
}

static void draw(void)
{
    background(); topbar();
    if (page==HUB) draw_hub(); else if (page==APPS) draw_apps();
    else if (page==WALLPAPERS) draw_wallpapers(); else draw_music();
    if (*status) text(small_font,status,52,716,workspaces[workspace].accent);
    taskbar(); SDL_RenderPresent(renderer);
}

static void hub_click(int x,int y)
{
    if (x>=680&&x<1200&&y>=210&&y<522) {
        int choice=(y-210)/78;
        if (choice>=0&&choice<WORKSPACE_COUNT) { workspace=choice; save_settings(); snprintf(status,sizeof status,"%s is now your workspace.",workspaces[workspace].name); }
    } else if (x>=680&&x<834&&y>=552&&y<590) page=APPS;
    else if (x>=850&&x<1004&&y>=552&&y<590) page=MUSIC;
    else if (x>=1020&&x<1174&&y>=552&&y<590) page=WALLPAPERS;
}

static void apps_click(int x,int y)
{
    if (x<50||x>=1265||y<205||y>=697) return;
    int col=(x-50)/405,row=(y-205)/123;
    if (col>2||row>3) return;
    int i=row*3+col,local_x=x-(50+col*405),local_y=y-(205+row*123);
    if (local_x<0||local_x>=380||local_y<0||local_y>=102) return;
    unsigned bit=1u<<i;
    if (local_y>=66&&local_x>=313) {
        if (!(selected_apps&bit)) { selected_apps|=bit; startup_apps|=bit; }
        else startup_apps^=bit;
    } else {
        selected_apps^=bit; if (!(selected_apps&bit)) startup_apps&=~bit;
    }
    save_settings(); snprintf(status,sizeof status,"%s %s your studio.",apps[i].name,(selected_apps&bit)?"added to":"removed from");
}

static void wallpaper_click(int x,int y)
{
    if (x<50||y<210) return;
    int col=(x-50)/610,row=(y-210)/222;
    if (col>1||row>1) return;
    int i=row*2+col; workspace=i; save_settings();
    snprintf(status,sizeof status,"%s selected.",workspaces[i].name);
}

static void music_click(int x,int y)
{
    if (x>=50&&x<1230&&y>=205&&y<321) { music_playing=!music_playing; return; }
    if (x<50||y<350) return;
    int col=(x-50)/610,row=(y-350)/106;
    if (col>1||row>2) return;
    playlist=row*2+col; music_playing=true; save_settings();
    snprintf(status,sizeof status,"%s selected for your studio.",playlists[playlist]);
}

int main(int argc,char **argv)
{
    const char *home=getenv("BEYOND_DATA_HOME"); if (!home) home=getenv("HOME");
    if (!home||!*home) { fprintf(stderr,"HOME must be set.\n"); return 1; }
    ensure_studio(home);
    int n=snprintf(config_path,sizeof config_path,"%s/.config/beyond-creator/settings",home);
    if (n<0||(size_t)n>=sizeof config_path) return 1;
    load_settings();
    signal(SIGCHLD,SIG_IGN);
    SDL_SetMainReady();
    if (SDL_Init(SDL_INIT_VIDEO)||TTF_Init()) { fprintf(stderr,"Display initialization failed: %s\n",SDL_GetError()); return 1; }
    const char *font_path=getenv("BEYOND_FONT"); if (!font_path) font_path="/usr/share/fonts/dejavu/DejaVuSans.ttf";
    small_font=TTF_OpenFont(font_path,15); font=TTF_OpenFont(font_path,21); title_font=TTF_OpenFont(font_path,40); hero_font=TTF_OpenFont(font_path,52);
    if (!small_font||!font||!title_font||!hero_font) { fprintf(stderr,"Font initialization failed: %s\n",TTF_GetError()); return 1; }
    bool preview=argc==3&&!strcmp(argv[1],"--screenshot");
    SDL_SetHint(SDL_HINT_X11_WINDOW_TYPE,"desktop");
    SDL_Window *window=SDL_CreateWindow("BIT OS Creator 0.1",0,0,W,H,SDL_WINDOW_BORDERLESS|(preview?SDL_WINDOW_HIDDEN:0));
    if (!window) return 1;
    renderer=SDL_CreateRenderer(window,-1,SDL_RENDERER_SOFTWARE); if (!renderer) return 1;
    SDL_RenderSetLogicalSize(renderer,W,H);
    if (!preview) launch_startup_apps();
    if (preview) {
        draw(); SDL_Surface *shot=SDL_CreateRGBSurfaceWithFormat(0,W,H,32,SDL_PIXELFORMAT_ARGB8888);
        if (!shot||SDL_RenderReadPixels(renderer,NULL,SDL_PIXELFORMAT_ARGB8888,shot->pixels,shot->pitch)||SDL_SaveBMP(shot,argv[2])) return 1;
        SDL_FreeSurface(shot);
    } else {
        bool running=true;
        while (running) {
            SDL_Event event;
            while (SDL_PollEvent(&event)) {
                if (event.type==SDL_QUIT) running=false;
                else if (event.type==SDL_MOUSEBUTTONDOWN&&event.button.button==SDL_BUTTON_LEFT) {
                    int x=event.button.x,y=event.button.y;
                    if (y>=744) {
                        if (x>=30&&x<158) page=HUB; else if (x>=172&&x<300) page=APPS;
                        else if (x>=314&&x<442) page=WALLPAPERS; else if (x>=456&&x<584) page=MUSIC;
                    } else if (page==HUB) hub_click(x,y); else if (page==APPS) apps_click(x,y);
                    else if (page==WALLPAPERS) wallpaper_click(x,y); else music_click(x,y);
                } else if (event.type==SDL_KEYDOWN) {
                    if (event.key.keysym.sym==SDLK_ESCAPE) page=HUB;
                    else if (event.key.keysym.sym==SDLK_1) page=HUB;
                    else if (event.key.keysym.sym==SDLK_2) page=APPS;
                    else if (event.key.keysym.sym==SDLK_3) page=WALLPAPERS;
                    else if (event.key.keysym.sym==SDLK_4) page=MUSIC;
                }
            }
            draw(); SDL_Delay(33);
        }
    }
    SDL_DestroyRenderer(renderer); SDL_DestroyWindow(window);
    TTF_CloseFont(small_font); TTF_CloseFont(font); TTF_CloseFont(title_font); TTF_CloseFont(hero_font);
    TTF_Quit(); SDL_Quit(); return 0;
}
