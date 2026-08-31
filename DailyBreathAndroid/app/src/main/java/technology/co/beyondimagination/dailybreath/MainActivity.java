package technology.co.beyondimagination.dailybreath;

import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.RectF;
import android.os.Bundle;
import android.os.Handler;
import android.view.MotionEvent;
import android.view.View;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public final class MainActivity extends Activity {
    private DailyBreathView view;

    @Override public void onCreate(Bundle state) {
        super.onCreate(state);
        view = new DailyBreathView();
        setContentView(view);
        openIntent(getIntent());
    }

    @Override protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        openIntent(intent);
    }

    private void openIntent(Intent intent) {
        if (intent == null || intent.getData() == null) return;
        String route = intent.getData().getHost();
        if (route == null) route = intent.getData().getPath();
        if (route == null) return;
        view.tab = route.toLowerCase(Locale.US).contains("breathe") ? 3 :
                route.toLowerCase(Locale.US).contains("scripture") || route.toLowerCase(Locale.US).contains("bible") ? 1 :
                route.toLowerCase(Locale.US).contains("academy") ? 2 :
                route.toLowerCase(Locale.US).contains("journal") ? 4 : 0;
        view.invalidate();
    }

    private final class DailyBreathView extends View {
        private final Paint p = new Paint(Paint.ANTI_ALIAS_FLAG);
        private final SharedPreferences prefs = getSharedPreferences("daily_breath", MODE_PRIVATE);
        private final Handler handler = new Handler();
        private final String[] tabs = {"Today", "Scripture", "Academy", "Breathe", "Journal"};
        private final String[] icons = {"✦", "▤", "★", "◌", "✎"};
        private int tab = 0;
        private int seconds = 120;
        private boolean breathing = false;
        private boolean complete = false;
        private String phase = "Ready when you are";
        private final Runnable ticker = new Runnable() { public void run() { tick(); } };

        DailyBreathView() { super(MainActivity.this); p.setTypeface(android.graphics.Typeface.create("sans", 0)); setFocusable(true); }

        private int green() { return Color.rgb(15, 45, 30); }
        private int gold() { return Color.rgb(209, 163, 74); }
        private int cream() { return Color.rgb(246, 240, 226); }
        private void text(Canvas c, String s, float x, float y, float size, int color, boolean bold) {
            p.setStyle(Paint.Style.FILL); p.setColor(color); p.setTextSize(size); p.setTypeface(android.graphics.Typeface.create("sans", bold ? 1 : 0)); c.drawText(s, x, y, p);
        }
        private void rounded(Canvas c, float l, float t, float r, float b, float radius, int color) {
            p.setStyle(Paint.Style.FILL); p.setColor(color); c.drawRoundRect(new RectF(l,t,r,b),radius,radius,p);
        }
        private void line(Canvas c, float l, float t, float r, float b, int color) { p.setColor(color); p.setStrokeWidth(1); c.drawLine(l,t,r,b,p); }

        @Override protected void onDraw(Canvas c) {
            super.onDraw(c); float w = getWidth(), h = getHeight();
            c.drawColor(cream());
            p.setShader(new android.graphics.LinearGradient(0,0,w,h,Color.rgb(246,240,226),Color.rgb(220,235,215),android.graphics.Shader.TileMode.CLAMP));
            c.drawRect(0,0,w,h,p); p.setShader(null);
            drawHeader(c,w);
            c.save(); c.translate(0, 92); drawContent(c,w,h-92); c.restore();
            drawNav(c,w,h);
        }

        private void drawHeader(Canvas c, float w) {
            text(c,"DAILYBREATH",24,34,20,green(),true);
            text(c,"Faith-centered wellness",24,57,12,Color.DKGRAY,false);
            rounded(c,w-76,18,w-24,48,16,Color.argb(35,15,45,30));
            text(c,"1.0",w-63,38,12,green(),true);
            line(c,20,76,w-20,76,Color.argb(50,15,45,30));
        }

        private void drawContent(Canvas c, float w, float h) {
            if (tab == 0) drawToday(c,w); else if (tab == 1) drawScripture(c,w); else if (tab == 2) drawAcademy(c,w); else if (tab == 3) drawBreathe(c,w); else drawJournal(c,w);
        }

        private void drawToday(Canvas c, float w) {
            text(c,"TODAY",22,35,12,gold(),true); text(c,"A steadier next step",22,70,32,green(),true);
            text(c,"A little room for truth, rest, and recovery.",22,96,15,Color.DKGRAY,false);
            rounded(c,18,124,w-18,350,24,green());
            text(c,"VERSE OF THE DAY",40,164,12,gold(),true);
            text(c,verseReference(),40,202,20,Color.WHITE,true);
            drawWrapped(c,verseText(),40,242,w-72,18,Color.WHITE);
            text(c,"Let this recovery verse guide your next faithful step.",40,322,12,Color.rgb(221,235,220),false);
            rounded(c,18,374,w-18,472,20,Color.WHITE);
            text(c,"Daily devotional",38,408,17,green(),true);
            text(c,"Practice presence before the day asks for more.",38,435,13,Color.DKGRAY,false);
            text(c,"Read today's reflection  →",38,461,13,gold(),true);
            rounded(c,18,496,w-18,574,18,Color.argb(70,255,255,255));
            text(c,"Weekly recovery challenge",38,528,16,green(),true);
            text(c,"Build one supportive connection today.",38,552,13,Color.DKGRAY,false);
        }

        private void drawBreathe(Canvas c, float w) {
            text(c,"BREATH OF THE DAY",22,35,12,gold(),true); text(c,"Peace Breath",22,70,32,green(),true);
            text(c,"Settle your pace before the day asks for more.",22,98,14,Color.DKGRAY,false);
            text(c,"Inhale 4  ·  Hold 4  ·  Exhale 6",22,126,14,green(),true);
            float cx=w/2, cy=292; p.setStyle(Paint.Style.FILL); p.setColor(Color.argb(45,15,45,30)); c.drawCircle(cx,cy,breathing?116:88,p);
            p.setStyle(Paint.Style.STROKE); p.setStrokeWidth(4); p.setColor(gold()); c.drawCircle(cx,cy,88,p); p.setStyle(Paint.Style.FILL);
            text(c, breathing ? phase : format(seconds),cx-50,cy+4,24,green(),true);
            text(c, complete ? "Complete" : "Inhale for four, hold for four, exhale for six.",cx-105,cy+34,11,Color.DKGRAY,false);
            rounded(c,24,410,w-24,468,20,green());
            text(c,breathing ? "Pause session" : (complete ? "Quick repeat" : "Begin breathing"),w/2-70,446,16,Color.WHITE,true);
            text(c,(prefs.getBoolean("completedToday",false)?"1":"0")+" days this week",24,510,13,Color.DKGRAY,true);
            text(c,"Practices",24,562,18,green(),true);
            text(c,"Guidance Prayer",24,594,15,green(),true); text(c,"Make room for wisdom before action.",24,616,13,Color.DKGRAY,false);
            text(c,"Gratitude Reset",24,656,15,green(),true); text(c,"Notice what is good and let it shape your next step.",24,678,13,Color.DKGRAY,false);
        }

        private void drawScripture(Canvas c,float w) { title(c,"SCRIPTURE","A library for returning"); card(c,18,120,w-18,230,"World English Bible","Search the full offline scripture library.","Open Bible library  →"); card(c,18,250,w-18,360,"Traditions","Bible  ·  Torah  ·  Quran","Choose a tradition to explore."); }
        private void drawAcademy(Canvas c,float w) { title(c,"ACADEMY","Learn one faithful step"); card(c,18,120,w-18,250,"Joining the Faith","A gentle four-lesson journey with Chris.","Continue lesson  →"); card(c,18,270,w-18,400,"Recovery","Practical, compassionate tools for today.","Start recovery journey  →"); }
        private void drawJournal(Canvas c,float w) { title(c,"REFLECTION JOURNAL","Private space for the next honest thought"); rounded(c,18,128,w-18,350,20,Color.WHITE); text(c,"What is present for you today?",38,172,18,green(),true); text(c,"Your reflections stay on this device.",38,202,13,Color.DKGRAY,false); line(c,38,246,w-38,246,Color.LTGRAY); line(c,38,292,w-38,292,Color.LTGRAY); rounded(c,38,310,w-38,358,16,gold()); text(c,"Save reflection",w/2-58,341,14,Color.WHITE,true); }
        private void title(Canvas c,String eyebrow,String title) { text(c,eyebrow,22,35,12,gold(),true); text(c,title,22,76,29,green(),true); }
        private void card(Canvas c,float l,float t,float r,float b,String heading,String sub,String action) { rounded(c,l,t,r,b,20,Color.WHITE); text(c,heading,l+20,t+38,17,green(),true); text(c,sub,l+20,t+66,13,Color.DKGRAY,false); if(action!=null) text(c,action,l+20,b-18,13,gold(),true); }

        private void drawNav(Canvas c,float w,float h) { float top=h-76; p.setColor(Color.WHITE); p.setStyle(Paint.Style.FILL); c.drawRect(0,top,w,h,p); for(int i=0;i<5;i++){float x=w*(i+.5f)/5; int color=i==tab?green():Color.GRAY; text(c,icons[i],x-8,top+26,20,color,true); text(c,tabs[i],x-25,top+51,11,color,i==tab);} }
        private void drawWrapped(Canvas c,String s,float x,float y,float max,float step,int color){String[] words=s.split(" ");String row="";for(String word:words){if(p.measureText(row+word)>max){text(c,row,x,y,16,color,false);y+=step+5;row="";}row+=word+" ";}text(c,row,x,y,16,color,false);}
        private String read(String name){try(InputStream in=getAssets().open(name)){return new String(in.readAllBytes(), StandardCharsets.UTF_8);}catch(Exception e){return "";}}
        private String first(String json,String key,String fallback){Matcher m=Pattern.compile("\\\""+key+"\\\"\\s*:\\s*\\\"([^\\\"]+)\\\"").matcher(json);return m.find()?m.group(1):fallback;}
        private String verseReference(){return first(read("daily-verses.json"),"reference","Psalm 34:17");}
        private String verseText(){return first(read("daily-verses.json"),"text","The righteous cry, and the LORD hears, and delivers them out of all their troubles.");}
        private String format(int value){return String.format(Locale.US,"%d:%02d",value/60,value%60);}
        private void tick(){if(!breathing)return;if(seconds<=1){seconds=0;breathing=false;complete=true;prefs.edit().putBoolean("completedToday",true).apply();phase="Complete";}else{seconds--;int cycle=(120-seconds)%14;phase=cycle<4?"Inhale":cycle<8?"Hold":"Exhale";handler.postDelayed(ticker,1000);}invalidate();}
        private void start(){if(complete){seconds=120;complete=false;}phase="Inhale";breathing=true;handler.removeCallbacks(ticker);handler.postDelayed(ticker,1000);invalidate();}
        @Override public boolean onTouchEvent(MotionEvent e){if(e.getAction()!=MotionEvent.ACTION_UP)return true;float x=e.getX(),y=e.getY();float h=getHeight(),w=getWidth();if(y>h-90){tab=Math.max(0,Math.min(4,(int)(x/(w/5))));invalidate();return true;}if(tab==3 && y>490 && y<590){if(breathing){breathing=false;handler.removeCallbacks(ticker);}else start();invalidate();}return true;}
    }
}
