package technology.co.beyondimagination.beyondbabynames;

import android.app.Activity;
import android.content.SharedPreferences;
import android.graphics.*;
import android.os.Bundle;
import android.view.MotionEvent;
import android.view.View;
import java.util.*;

/** Private, offline discovery and swipe v1 based on the shared iOS name library. */
public final class MainActivity extends Activity {
    @Override public void onCreate(Bundle state) { super.onCreate(state); setContentView(new NamesView()); }
    private final class NamesView extends View {
        final Paint p=new Paint(Paint.ANTI_ALIAS_FLAG); final SharedPreferences prefs=getSharedPreferences("names",MODE_PRIVATE);
        final String[][] names={{"Aaliyah","Arabic","Exalted and noble","Graceful · Modern"},{"Amara","Igbo","Grace","Global · Gentle"},{"Ari","Hebrew","Lion","Short · Bold"},{"Aurora","Latin","Dawn","Celestial · Romantic"},{"Ezra","Hebrew","Helper","Biblical · Gentle"},{"Imani","Swahili","Faith","Spiritual · Global"},{"Isla","Scottish","Island","Nature · Soft"},{"Kai","Hawaiian","Sea","Nature · Short"},{"Luca","Italian","Bringer of light","Bright · Modern"},{"Luna","Latin","Moon","Celestial · Dreamy"},{"Maeve","Irish","She who intoxicates","Mythic · Strong"},{"Noah","Hebrew","Rest and comfort","Biblical · Peaceful"},{"Nova","Latin","New","Celestial · Bold"},{"Sage","Latin","Wise","Nature · Calm"},{"Zuri","Swahili","Beautiful","Bright · Global"}};
        int tab=0,index=0; final Set<String> favorites=new HashSet<>();
        NamesView(){super(MainActivity.this); Collections.addAll(favorites,prefs.getString("favorites","").split(","));}
        void text(Canvas c,String s,float x,float y,float size,int color,boolean b){p.setColor(color);p.setTextSize(size);p.setTypeface(Typeface.create("sans",b?1:0));p.setStyle(Paint.Style.FILL);c.drawText(s,x,y,p);}
        void box(Canvas c,float l,float t,float r,float b,float rad,int color){p.setColor(color);p.setStyle(Paint.Style.FILL);c.drawRoundRect(new RectF(l,t,r,b),rad,rad,p);}
        @Override protected void onDraw(Canvas c){float w=getWidth(),h=getHeight();c.drawColor(Color.rgb(18,10,36));text(c,"BEYOND BABY",22,42,22,Color.WHITE,true);text(c,"Find the one",22,66,13,Color.rgb(206,194,226),false);if(tab==0)discover(c,w);else if(tab==1)swipe(c,w);else if(tab==2)favs(c,w);else together(c,w);nav(c,w,h);}
        void discover(Canvas c,float w){String[] n=names[(index+9)%names.length];box(c,18,92,w-18,256,28,Color.rgb(108,45,220));text(c,"YOUR DAILY DISCOVERY",40,126,12,Color.rgb(255,211,246),true);text(c,n[0],40,184,43,Color.WHITE,true);text(c,n[2],40,215,17,Color.WHITE,false);text(c,"A thoughtful name, surfaced just for you.",40,240,13,Color.rgb(240,225,255),false);text(c,"EXPLORE NAMES",22,302,12,Color.rgb(255,78,200),true);int y=326;for(int i=0;i<4;i++){String[] item=names[(index+i)%names.length];box(c,18,y,w-18,y+76,18,Color.rgb(35,25,60));text(c,item[0],38,y+31,20,Color.WHITE,true);text(c,item[1]+" · "+item[2],38,y+55,13,Color.rgb(202,193,220),false);text(c,favorites.contains(item[0])?"♥":"♡",w-56,y+45,25,Color.rgb(255,61,197),true);y+=88;}}
        void swipe(Canvas c,float w){String[] n=names[index];text(c,"SWIPE TO DECIDE",22,108,12,Color.rgb(255,78,200),true);box(c,28,138,w-28,442,34,Color.rgb(47,27,78));text(c,n[0],w/2-60,240,42,Color.WHITE,true);text(c,n[1],w/2-40,273,16,Color.rgb(230,215,250),false);text(c,n[2],w/2-92,309,18,Color.WHITE,false);text(c,n[3],w/2-95,341,14,Color.rgb(224,194,250),false);box(c,30,474,126,534,30,Color.rgb(70,62,92));box(c,w/2-48,474,w/2+48,534,30,Color.rgb(108,45,220));box(c,w-126,474,w-30,534,30,Color.rgb(255,61,197));text(c,"PASS",48,511,13,Color.WHITE,true);text(c,"MAYBE",w/2-27,511,13,Color.WHITE,true);text(c,"LOVE",w-102,511,13,Color.WHITE,true);text(c,"Your loves stay privately on this device.",22,578,14,Color.rgb(203,196,221),false);}
        void favs(Canvas c,float w){text(c,"YOUR SHORTLIST",22,108,12,Color.rgb(255,78,200),true);text(c,favorites.size()+" names loved",22,143,28,Color.WHITE,true);int y=178;for(String[] n:names)if(favorites.contains(n[0])){box(c,18,y,w-18,y+70,18,Color.rgb(35,25,60));text(c,n[0],38,y+30,20,Color.WHITE,true);text(c,n[1]+" · "+n[2],38,y+54,13,Color.rgb(202,193,220),false);y+=82;}if(favorites.isEmpty)text(c,"Love names in Swipe to build your list.",22,198,15,Color.rgb(202,193,220),false);}
        void together(Canvas c,float w){text(c,"COUPLE MODE",22,108,12,Color.rgb(255,78,200),true);box(c,18,138,w-18,300,24,Color.rgb(35,25,60));text(c,"Discover together",38,180,25,Color.WHITE,true);text(c,"Create a private invite and compare shared loves.",38,210,14,Color.rgb(202,193,220),false);box(c,38,236,w-38,284,16,Color.rgb(108,45,220));text(c,"CREATE DEMO INVITE",w/2-78,266,14,Color.WHITE,true);text(c,"Twin-name ideas unlock after you save two names.",22,344,15,Color.rgb(202,193,220),false);}
        void nav(Canvas c,float w,float h){String[] labels={"Discover","Swipe","Favorites","Together"};for(int i=0;i<4;i++){float x=w*(i+.5f)/4;int col=i==tab?Color.rgb(255,61,197):Color.rgb(173,163,192);text(c,i==0?"✦":i==1?"↔":i==2?"♥":"♧",x-8,h-49,20,col,true);text(c,labels[i],x-28,h-25,11,col,i==tab);}}
        void persist(){prefs.edit().putString("favorites",String.join(",",favorites)).apply();}
        @Override public boolean onTouchEvent(MotionEvent e){if(e.getAction()!=MotionEvent.ACTION_UP)return true;float x=e.getX(),y=e.getY(),w=getWidth(),h=getHeight();if(y>h-85){tab=Math.min(3,(int)(x/(w/4)));invalidate();return true;}if(tab==0&&y>326&&y<680){int row=(int)((y-326)/88);if(row<4){String name=names[(index+row)%names.length][0];if(!favorites.add(name))favorites.remove(name);persist();}}else if(tab==1&&y>474&&y<550){if(x>w*.66f){favorites.add(names[index][0]);persist();}index=(index+1)%names.length;}invalidate();return true;}
    }
}
