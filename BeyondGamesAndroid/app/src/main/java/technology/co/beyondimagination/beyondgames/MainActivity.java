package technology.co.beyondimagination.beyondgames;

import android.app.Activity;
import android.graphics.*;
import android.os.Bundle;
import android.os.Handler;
import android.view.MotionEvent;
import android.view.View;
import java.util.Locale;
import java.util.Random;

/** Offline Android v1 arcade collection: five titles designated playable in the shared catalog. */
public final class MainActivity extends Activity {
    private GameView gameView;
    @Override public void onCreate(Bundle state) { super.onCreate(state); gameView = new GameView(); setContentView(gameView); }
    @Override public void onBackPressed() { if (gameView.game != -1) { gameView.game = -1; gameView.invalidate(); } else super.onBackPressed(); }

    private final class GameView extends View {
        private final Paint p = new Paint(Paint.ANTI_ALIAS_FLAG);
        private final Random random = new Random(); private final Handler handler = new Handler();
        private final String[] names = {"Bit Runner", "Beyond Hoop", "Zak’s Kitchen Rush", "Tattoo Master", "French Quest"};
        private final String[] labels = {"Dash through corrupted data", "60-second rooftop shooting rush", "Build your fastest kitchen", "Trace with a steady hand", "Dialogue adventure across the world"};
        private final int[] colors = {Color.rgb(142,92,255),Color.rgb(255,122,50),Color.rgb(255,153,72),Color.rgb(230,79,162),Color.rgb(57,121,239)};
        private int game = -1, score = 0, round = 1, lane = 1, target = 1; private boolean active = false;
        private String message = "Choose a game";
        private final Runnable timer = new Runnable() { public void run() { if (!active) return; round++; if (game == 0) { target = random.nextInt(3); if (target == lane) { active = false; message = "Security virus caught you — run again."; } else { score += 10; message = "Clean route! Tap a lane to dodge."; } } else if (round > 12) { active = false; message = "Round complete — your score is " + score + "."; } invalidate(); if (active) handler.postDelayed(this, 900); } };
        GameView() { super(MainActivity.this); setFocusable(true); }
        private void fill(Canvas c, int color) { c.drawColor(color); }
        private void text(Canvas c,String value,float x,float y,float size,int color,boolean bold){p.setStyle(Paint.Style.FILL);p.setColor(color);p.setTextSize(size);p.setTypeface(Typeface.create("sans",bold?1:0));c.drawText(value,x,y,p);}
        private void box(Canvas c,float l,float t,float r,float b,float radius,int color){p.setStyle(Paint.Style.FILL);p.setColor(color);c.drawRoundRect(new RectF(l,t,r,b),radius,radius,p);}
        private void outline(Canvas c,float l,float t,float r,float b,float radius,int color){p.setStyle(Paint.Style.STROKE);p.setStrokeWidth(2);p.setColor(color);c.drawRoundRect(new RectF(l,t,r,b),radius,radius,p);p.setStyle(Paint.Style.FILL);}
        @Override protected void onDraw(Canvas c) { super.onDraw(c); if (game == -1) catalog(c); else play(c); }
        private void catalog(Canvas c) {
            fill(c,Color.rgb(9,7,24)); float w=getWidth();
            text(c,"BEYOND GAMES",22,42,22,Color.WHITE,true); text(c,"Five playable Android v1 worlds",22,66,13,Color.rgb(168,171,205),false);
            text(c,"Press play. Go beyond.",22,112,29,Color.WHITE,true); text(c,"Original worlds made for quick touch sessions.",22,139,14,Color.rgb(198,201,222),false);
            for(int i=0;i<5;i++){float top=165+i*100;box(c,18,top,w-18,top+84,20,Color.rgb(20,20,49));outline(c,18,top,w-18,top+84,20,colors[i]);box(c,30,top+14,82,top+66,15,colors[i]);text(c,i==0?"⚡":i==1?"🏀":i==2?"🍲":i==3?"✒":"FR",42,top+48,18,Color.WHITE,true);text(c,names[i],98,top+36,18,Color.WHITE,true);text(c,labels[i],98,top+59,12,Color.rgb(176,180,207),false);text(c,"PLAY ›",w-76,top+76,11,colors[i],true);}
        }
        private void play(Canvas c) {
            int accent=colors[game]; fill(c,Color.rgb(8,10,27)); float w=getWidth();
            text(c,"‹  GAMES",20,38,14,Color.rgb(185,188,214),true); text(c,names[game].toUpperCase(Locale.US),20,74,24,Color.WHITE,true);
            text(c,"SCORE  " + score,20,100,12,accent,true); text(c,active?"LIVE":"READY",w-80,100,12,active?Color.rgb(99,244,216):Color.LTGRAY,true);
            if(game==0) runner(c,w,accent); else if(game==1) hoop(c,w,accent); else if(game==2) kitchen(c,w,accent); else if(game==3) tattoo(c,w,accent); else french(c,w,accent);
            text(c,message,22,getHeight()-96,14,Color.rgb(211,213,231),false); box(c,22,getHeight()-76,w-22,getHeight()-26,16,accent); text(c,active?"PAUSE ROUND":"START / PLAY AGAIN",w/2-74,getHeight()-45,15,Color.WHITE,true);
        }
        private void runner(Canvas c,float w,int accent) {
            text(c,"Tap a lane before the virus arrives.",22,136,14,Color.rgb(190,192,214),false);
            float top=170; for(int i=0;i<3;i++){float x=24+i*(w-64)/3;box(c,x,top,x+(w-80)/3,390,18,Color.rgb(27,25,59));if(i==target&&active){p.setColor(Color.rgb(255,80,165));c.drawCircle(x+(w-80)/6,230,26,p);}if(i==lane){p.setColor(accent);c.drawCircle(x+(w-80)/6,350,20,p);}}
            text(c,"VIRUS",w/2-22,425,11,Color.rgb(255,112,185),true);text(c,"RUNNER",w/2-27,452,11,accent,true);
        }
        private void hoop(Canvas c,float w,int accent) {
            text(c,"Tap the gold zone for a clean bucket.",22,136,14,Color.rgb(190,192,214),false);
            p.setStyle(Paint.Style.STROKE);p.setStrokeWidth(5);p.setColor(Color.WHITE);c.drawCircle(w/2,288,88,p);p.setStyle(Paint.Style.FILL);p.setColor(accent);c.drawRect(w/2-48,282,w/2+48,289,p);p.setColor(Color.rgb(255,143,53));c.drawCircle(w/2,330,20,p);
            float meter=130+(round%7)*32;box(c,34,430,w-34,458,14,Color.rgb(38,38,69));box(c,w/2-20,430,w/2+20,458,8,Color.rgb(238,206,79));box(c,meter,424,meter+10,464,5,accent);text(c,"Release in the gold zone",w/2-78,490,14,Color.WHITE,true);
        }
        private void kitchen(Canvas c,float w,int accent) {
            text(c,"Serve the recipe before the timer runs out.",22,136,14,Color.rgb(190,192,214),false);
            box(c,22,168,w-22,280,20,Color.rgb(80,35,30));text(c,"ORDER #"+round,42,202,12,Color.rgb(255,205,106),true);text(c,round%2==0?"Haitian griot plate":"Spiced veggie bowl",42,237,23,Color.WHITE,true);
            String[] steps={"PREP","COOK","SERVE"};for(int i=0;i<3;i++){float x=24+i*(w-64)/3;box(c,x,322,x+(w-80)/3,408,16,i==target?accent:Color.rgb(32,32,62));text(c,steps[i],x+16,369,16,Color.WHITE,true);}text(c,"Tap the next recipe step",w/2-77,450,14,Color.WHITE,true);
        }
        private void tattoo(Canvas c,float w,int accent) {
            text(c,"Tap inside the stencil to build a smooth line.",22,136,14,Color.rgb(190,192,214),false);box(c,36,170,w-36,426,22,Color.rgb(243,235,224));p.setStyle(Paint.Style.STROKE);p.setStrokeWidth(5);p.setColor(Color.rgb(28,27,37));c.drawCircle(w/2,280,66,p);c.drawLine(w/2,214,w/2,346,p);c.drawLine(w/2-58,280,w/2+58,280,p);p.setStyle(Paint.Style.FILL);p.setColor(accent);c.drawCircle(w/2,280,Math.min(50,score*2),p);text(c,"INK QUALITY  "+Math.min(100,score)+"%",w/2-83,474,14,Color.WHITE,true);
        }
        private void french(Canvas c,float w,int accent) {
            text(c,"Choose the phrase that completes the scene.",22,136,14,Color.rgb(190,192,214),false);box(c,22,164,w-22,258,20,Color.rgb(25,48,95));text(c,"MONTRÉAL · MISSION "+round,42,198,12,Color.rgb(151,193,255),true);text(c,"Bonjour — comment allez-vous?",42,232,20,Color.WHITE,true);
            String[] answers={"Très bien, merci!","Au revoir","Je suis un livre"};for(int i=0;i<3;i++){float top=286+i*62;box(c,22,top,w-22,top+48,15,Color.rgb(29,31,61));text(c,answers[i],42,top+31,15,Color.WHITE,i==target);}
        }
        private void reset(){score=0;round=1;target=random.nextInt(3);lane=1;message="Go!";}
        private void start(){handler.removeCallbacks(timer);reset();active=true;handler.postDelayed(timer,900);invalidate();}
        @Override public boolean onTouchEvent(MotionEvent e) { if(e.getAction()!=MotionEvent.ACTION_UP)return true;float x=e.getX(),y=e.getY(),w=getWidth(),h=getHeight();if(game==-1){if(y>=165&&y<665){game=Math.min(4,(int)((y-165)/100));reset();invalidate();}return true;}if(y<58){game=-1;active=false;handler.removeCallbacks(timer);invalidate();return true;}if(y>h-90){if(active){active=false;handler.removeCallbacks(timer);message="Round paused.";}else start();return true;}if(!active)return true;if(game==0&&y>170&&y<390){lane=Math.min(2,(int)(x/(w/3)));message="Runner moved. Keep going!";}else if(game==1&&y>400&&y<500){boolean made=random.nextInt(3)!=0;score+=made?20:0;message=made?"Bucket! +20":"Just outside the gold zone.";}else if(game==2&&y>322&&y<408){int selected=Math.min(2,(int)(x/(w/3)));if(selected==target){score+=15;target=(target+1)%3;message="Perfect prep! +15";}else message="That step is not ready yet.";}else if(game==3&&y>170&&y<426){score=Math.min(100,score+8);message=score>=100?"Clean stencil complete!":"Steady hand — keep tracing.";}else if(game==4&&y>286&&y<472){int selected=Math.min(2,(int)((y-286)/62));if(selected==0){score+=20;message="Excellent French! +20";}else message="Try the greeting response.";round++;}invalidate();return true;}
    }
}
