package technology.co.beyondimagination.beyondfrench;

import android.app.Activity;
import android.content.SharedPreferences;
import android.graphics.*;
import android.os.Bundle;
import android.view.MotionEvent;
import android.view.View;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.util.regex.*;

/** Offline French-learning v1 with the shared dictionary and Academy bundles. */
public final class MainActivity extends Activity {
    @Override public void onCreate(Bundle state){super.onCreate(state);setContentView(new FrenchView());}
    private final class FrenchView extends View {
        final Paint p=new Paint(Paint.ANTI_ALIAS_FLAG);final SharedPreferences prefs=getSharedPreferences("french",MODE_PRIVATE);int tab=0,correct=0;String feedback="Tap an answer to practice.";
        FrenchView(){super(MainActivity.this);}
        void t(Canvas c,String s,float x,float y,float z,int color,boolean b){p.setColor(color);p.setTextSize(z);p.setTypeface(Typeface.create("sans",b?1:0));p.setStyle(Paint.Style.FILL);c.drawText(s,x,y,p);}
        void b(Canvas c,float l,float top,float r,float bottom,float rad,int color){p.setColor(color);p.setStyle(Paint.Style.FILL);c.drawRoundRect(new RectF(l,top,r,bottom),rad,rad,p);}
        @Override protected void onDraw(Canvas c){float w=getWidth(),h=getHeight();c.drawColor(Color.rgb(6,20,42));t(c,"BEYOND FRENCH",22,42,22,Color.WHITE,true);t(c,"Speak, listen, remember",22,66,13,Color.rgb(173,199,240),false);if(tab==0)today(c,w);else if(tab==1)academy(c,w);else if(tab==2)dictionary(c,w);else if(tab==3)practice(c,w);else progress(c,w);nav(c,w,h);}
        void today(Canvas c,float w){t(c,"TODAY'S PHRASE",22,110,12,Color.rgb(92,164,255),true);b(c,18,132,w-18,344,24,Color.rgb(18,49,98));t(c,"Keep going.",40,184,18,Color.rgb(193,216,255),false);t(c,"Continue.",40,241,39,Color.WHITE,true);t(c,"Kohn-tee-new",40,273,16,Color.rgb(177,208,255),false);t(c,"A little encouragement can go a long way.",40,311,14,Color.WHITE,false);t(c,"DAILY CHALLENGE",22,388,12,Color.rgb(92,164,255),true);t(c,"How would you say “Keep going.” in French?",22,420,16,Color.WHITE,true);b(c,22,448,w-22,498,16,Color.rgb(57,121,239));t(c,"Continue.",42,480,16,Color.WHITE,true);t(c,"Free guest lesson · Academy unlocks more journeys",22,544,14,Color.rgb(173,199,240),false);}
        void academy(Canvas c,float w){t(c,"ACADEMY",22,110,12,Color.rgb(92,164,255),true);b(c,18,132,w-18,246,20,Color.rgb(18,49,98));t(c,"Greetings",38,172,23,Color.WHITE,true);t(c,"Learn to meet people with confidence.",38,201,14,Color.rgb(190,212,249),false);t(c,"LESSON 1 AVAILABLE  →",38,229,13,Color.rgb(105,174,255),true);b(c,18,266,w-18,380,20,Color.rgb(14,38,78));t(c,"Travel Basics",38,306,22,Color.WHITE,true);t(c,"Beyond ID unlocks the full lesson path.",38,335,14,Color.rgb(190,212,249),false);}
        void dictionary(Canvas c,float w){t(c,"DICTIONARY",22,110,12,Color.rgb(92,164,255),true);String[][] rows={{"hello","bonjour","bon-zhoor"},{"thank you","merci","mehr-see"},{"please","s'il vous plaît","seel voo pleh"},{"where?","où?","oo"}};int y=136;for(String[] row:rows){b(c,18,y,w-18,y+70,16,Color.rgb(17,43,85));t(c,row[0],38,y+28,18,Color.WHITE,true);t(c,row[1]+"  ·  "+row[2],38,y+52,14,Color.rgb(185,211,251),false);y+=82;}t(c,"Bundled offline: "+wordCount()+" dictionary entries",22,500,14,Color.rgb(173,199,240),false);}
        void practice(Canvas c,float w){t(c,"SPEAKING PRACTICE",22,110,12,Color.rgb(92,164,255),true);b(c,18,136,w-18,290,24,Color.rgb(18,49,98));t(c,"Say this aloud",40,174,14,Color.rgb(173,204,255),true);t(c,"Bonjour!",40,225,38,Color.WHITE,true);t(c,"Hello!",40,256,16,Color.rgb(196,218,252),false);b(c,22,330,w-22,384,17,Color.rgb(57,121,239));t(c,"I SAID IT",w/2-38,364,15,Color.WHITE,true);t(c,feedback,22,432,15,Color.rgb(204,218,242),false);}
        void progress(Canvas c,float w){t(c,"YOUR PROGRESS",22,110,12,Color.rgb(92,164,255),true);t(c,correct+"",22,169,42,Color.WHITE,true);t(c,"phrases practiced",78,159,16,Color.rgb(187,207,240),false);b(c,22,202,w-22,222,10,Color.rgb(28,52,91));b(c,22,202,22+Math.min(w-44,correct*28),222,10,Color.rgb(57,121,239));t(c,"Your learning stays on this device until you choose to sign in.",22,270,14,Color.rgb(187,207,240),false);}
        void nav(Canvas c,float w,float h){String[] l={"Today","Academy","Dictionary","Practice","Progress"};for(int i=0;i<5;i++){float x=w*(i+.5f)/5;int col=i==tab?Color.rgb(105,174,255):Color.rgb(150,166,194);t(c,i==0?"☀":i==1?"★":i==2?"▤":i==3?"◌":"▥",x-7,h-48,18,col,true);t(c,l[i],x-25,h-25,10,col,i==tab);}}
        int wordCount(){try(InputStream in=getAssets().open("dictionary.json")){String s=new String(in.readAllBytes(),StandardCharsets.UTF_8);Matcher m=Pattern.compile("\\\"english\\\"").matcher(s);int n=0;while(m.find())n++;return n;}catch(Exception e){return 0;}}
        @Override public boolean onTouchEvent(MotionEvent e){if(e.getAction()!=MotionEvent.ACTION_UP)return true;float x=e.getX(),y=e.getY(),w=getWidth(),h=getHeight();if(y>h-85){tab=Math.min(4,(int)(x/(w/5)));invalidate();return true;}if(tab==0&&y>448&&y<510){correct++;feedback="Correct — great work!";}if(tab==3&&y>330&&y<395){correct++;feedback="Nice speaking practice. Keep going!";}prefs.edit().putInt("correct",correct).apply();invalidate();return true;}
    }
}
