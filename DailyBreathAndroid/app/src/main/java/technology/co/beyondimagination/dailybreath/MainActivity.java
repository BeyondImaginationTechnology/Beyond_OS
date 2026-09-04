package technology.co.beyondimagination.dailybreath;

import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.text.InputType;
import android.view.Gravity;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.Spinner;
import android.widget.ArrayAdapter;
import android.widget.TextView;
import android.widget.Toast;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.temporal.ChronoUnit;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;

/** Native, offline DailyBreath reader for Bible, Tanakh, and Quran content. */
public final class MainActivity extends Activity {
    private static final int INK = Color.rgb(42, 35, 61), PURPLE = Color.rgb(117, 99, 168), LILAC = Color.rgb(237, 232, 249), CREAM = Color.rgb(255, 252, 248), NIGHT = Color.rgb(31, 27, 55), GOLD = Color.rgb(205, 173, 116);
    private static final String[] TABS = {"Today", "Scripture", "Academy", "Breathe", "Journal"};
    private static final Set<String> TANAKH_CODES = new HashSet<>(Arrays.asList("GEN","EXO","LEV","NUM","DEU","JOS","JDG","RUT","1SA","2SA","1KI","2KI","1CH","2CH","EZR","NEH","EST","JOB","PSA","PRO","ECC","SOL","ISA","JER","LAM","EZE","DAN","HOS","JOE","AMO","OBA","JON","MIC","NAH","HAB","ZEP","HAG","ZEC","MAL"));
    private final Handler handler = new Handler(Looper.getMainLooper());
    private final List<DailyVerse> dailyVerses = new ArrayList<>();
    private final List<ScriptureVerse> bible = new ArrayList<>(), quran = new ArrayList<>();
    private final Runnable ticker = this::tick;
    private final Map<String,String> englishNames = englishNames(), tanakhNames = tanakhNames();
    private SharedPreferences prefs;
    private LinearLayout page, nav;
    private int tab, seconds = 120;
    private boolean breathing, complete;
    private TextView timerView, phaseView, weekView;
    private Button breathButton;
    private Faith faith;

    private enum Faith {
        BIBLE("Bible", "Bible Verse", "verse"), TANAKH("Tanakh", "Tanakh Passage", "passage"), QURAN("Quran", "Quran Ayah", "ayah");
        final String title, dailyLabel, unit;
        Faith(String title, String dailyLabel, String unit) { this.title = title; this.dailyLabel = dailyLabel; this.unit = unit; }
    }

    @Override public void onCreate(Bundle state) {
        super.onCreate(state);
        prefs = getSharedPreferences("daily_breath", MODE_PRIVATE);
        faith = readFaith();
        loadDailyVerses(); loadLibraries(); buildLayout(); openIntent(getIntent());
    }
    @Override protected void onNewIntent(Intent intent) { super.onNewIntent(intent); setIntent(intent); openIntent(intent); }
    @Override protected void onPause() { super.onPause(); if (breathing) { breathing = false; handler.removeCallbacks(ticker); refreshBreathControls(); } }
    @Override protected void onDestroy() { handler.removeCallbacks(ticker); super.onDestroy(); }

    private void buildLayout() {
        LinearLayout root = new LinearLayout(this); root.setOrientation(LinearLayout.VERTICAL); root.setBackgroundColor(CREAM);
        LinearLayout header = new LinearLayout(this); header.setOrientation(LinearLayout.VERTICAL); header.setPadding(dp(20),dp(16),dp(20),dp(12));
        header.addView(label("DAILY BREATH",20,INK,true)); header.addView(label("Sacred reading, breath, and reflection",13,Color.DKGRAY,false)); root.addView(header);
        ScrollView scroll = new ScrollView(this); scroll.setFillViewport(true); page = new LinearLayout(this); page.setOrientation(LinearLayout.VERTICAL); page.setPadding(dp(20),dp(16),dp(20),dp(28)); scroll.addView(page); root.addView(scroll,new LinearLayout.LayoutParams(-1,0,1));
        nav = new LinearLayout(this); nav.setOrientation(LinearLayout.HORIZONTAL); nav.setBackgroundColor(Color.WHITE); root.addView(nav,new LinearLayout.LayoutParams(-1,dp(68)));
        setContentView(root); showTab(0);
    }
    private void showTab(int value) { tab=Math.max(0,Math.min(4,value)); page.removeAllViews(); renderNav(); if(tab==0)showToday();else if(tab==1)showScripture();else if(tab==2)showAcademy();else if(tab==3)showBreathe();else showJournal(); }
    private void renderNav() {
        nav.removeAllViews(); for(int index=0;index<TABS.length;index++){ final int destination=index; Button button=new Button(this); button.setText(TABS[index]); button.setAllCaps(false); button.setTextSize(11); button.setTextColor(index==tab?INK:Color.DKGRAY); button.setTypeface(Typeface.DEFAULT,index==tab?Typeface.BOLD:Typeface.NORMAL); button.setBackgroundColor(index==tab?LILAC:Color.TRANSPARENT); button.setContentDescription("Open "+TABS[index]); button.setOnClickListener(v->showTab(destination)); nav.addView(button,new LinearLayout.LayoutParams(0,-1,1)); }
    }
    private void addFaithPicker() {
        LinearLayout picker=new LinearLayout(this); picker.setOrientation(LinearLayout.HORIZONTAL); picker.setPadding(dp(4),dp(4),dp(4),dp(4)); picker.setBackground(round(Color.rgb(239,235,246),16));
        for(Faith candidate:Faith.values()){ Button button=new Button(this); button.setText(candidate.title); button.setTextSize(13); button.setAllCaps(false); button.setTypeface(Typeface.DEFAULT,Typeface.BOLD); boolean selected=candidate==faith; button.setTextColor(selected?Color.WHITE:INK); button.setBackground(round(selected?PURPLE:Color.TRANSPARENT,12)); button.setContentDescription("Choose "+candidate.title); button.setOnClickListener(v->{faith=candidate;prefs.edit().putString("selected_faith",faith.name()).apply();showTab(tab);}); picker.addView(button,new LinearLayout.LayoutParams(0,dp(46),1)); }
        page.addView(picker,spaced());
    }

    private void showToday() {
        title("TODAY","A steadier next step"); addFaithPicker(); addBody(todayIntro()); Reading reading=readingOfTheDay();
        LinearLayout card=card(faith==Faith.QURAN?NIGHT:INK); card.addView(label(faith.dailyLabel.toUpperCase(Locale.US)+" OF THE DAY",12,GOLD,true)); TextView quote=label("“"+reading.text+"”",27,Color.WHITE,true); quote.setPadding(0,dp(14),0,dp(12)); card.addView(quote); card.addView(label(reading.reference,18,GOLD,true)); page.addView(card,spaced());
        LinearLayout reflection=card(Color.WHITE); reflection.addView(label(faith==Faith.TANAKH?"Jewish daily reflection":faith==Faith.QURAN?"Quran reflection":"Daily devotional",19,INK,true)); reflection.addView(label(reflectionCopy(reading),14,Color.DKGRAY,false)); Button read=action("Read today’s "+faith.unit+" again"); read.setOnClickListener(v->showDetail(faith.dailyLabel,reflectionCopy(reading))); reflection.addView(read); page.addView(reflection,spaced());
        Button challenge=action("Begin a breathing practice"); challenge.setOnClickListener(v->showTab(3)); page.addView(challenge,spaced());
        Button sources=action("Scripture sources and translations"); sources.setOnClickListener(v->showSources()); page.addView(sources,spaced());
    }
    private void showScripture() {
        title(faith.title.toUpperCase(Locale.US),faith==Faith.TANAKH?"Complete local Tanakh":faith==Faith.QURAN?"Complete local Quran":"Complete local Bible"); addFaithPicker();
        addBody(faith==Faith.TANAKH?"Search the complete Tanakh offline. Jewish book names are used throughout.":faith==Faith.QURAN?"Search the complete Quran English meaning offline.":"Search the complete Bible offline.");
        EditText query=new EditText(this); query.setHint("Search "+faith.title+" "+plural(faith.unit)); query.setSingleLine(true); query.setContentDescription("Search "+faith.title); page.addView(query,spaced()); LinearLayout results=new LinearLayout(this); results.setOrientation(LinearLayout.VERTICAL); Button search=action("Search offline "+faith.title); search.setOnClickListener(v->renderResults(query.getText().toString(),results)); page.addView(search,spaced()); page.addView(results); renderResults("",results);
    }
    private void renderResults(String raw,LinearLayout results) {
        results.removeAllViews(); String query=raw.trim().toLowerCase(Locale.US); int shown=0;
        for(ScriptureVerse verse:libraryFor(faith)){if(!query.isEmpty()&&!(verse.reference+" "+verse.text).toLowerCase(Locale.US).contains(query))continue; LinearLayout item=card(Color.WHITE); item.addView(label(verse.reference,17,PURPLE,true)); item.addView(label(verse.text,15,Color.DKGRAY,false)); results.addView(item,spaced()); if(++shown==40)break;}
        if(shown==0)results.addView(label("No matching "+plural(faith.unit)+" were found.",15,Color.DKGRAY,false));
    }
    private void showAcademy() {
        title("ACADEMY","Learn one faithful step"); addFaithPicker();
        if(faith==Faith.TANAKH){addLesson("Learning with care","A Jewish pathway for reflection and practice.","Begin with Shema: listen before reacting. Jewish life is lived in community; a rabbi and a welcoming congregation are the right guides for deeper study or conversion.");addLesson("Teshuvah and return","Recovery can include honest repair.","Teshuvah is a movement of return. Name what happened truthfully, repair what you safely can, and reconnect with trusted support.");}
        else if(faith==Faith.QURAN){addLesson("Intention and guidance","A Muslim pathway for reflection and practice.","Begin with sincere intention, remember Allah, and seek guidance through steady, practical action and trusted community.");addLesson("Mercy and patience","A recovery practice grounded in sabr.","Pause before reacting, ask Allah for help, and take the next right step with a trusted person or professional support when needed.");}
        else{addLesson("Joining the Faith","A gentle starter journey.","Stillness is not a delay. Pause, listen, and let one faithful action follow.");addLesson("Recovery","Practical, compassionate tools.","Name one safe person to contact and one immediate pathway away from harm.");}
    }
    private void addLesson(String heading,String summary,String lesson){LinearLayout item=card(Color.WHITE);item.addView(label(heading,20,INK,true));item.addView(label(summary,14,Color.DKGRAY,false));Button open=action("Open lesson");open.setOnClickListener(v->showDetail(heading,lesson));item.addView(open);page.addView(item,spaced());}

    private void showBreathe(){title("BREATH OF THE DAY","Peace Breath");addBody("Inhale 4 · Hold 4 · Exhale 6");timerView=label(format(seconds),46,INK,true);timerView.setGravity(Gravity.CENTER);timerView.setPadding(0,dp(36),0,dp(8));page.addView(timerView);phaseView=label(complete?"Complete":breathing?phase():"Ready when you are",19,Color.DKGRAY,true);phaseView.setGravity(Gravity.CENTER);page.addView(phaseView);breathButton=action(breathing?"Pause session":complete?"Quick repeat":"Begin breathing");breathButton.setOnClickListener(v->toggleBreathing());page.addView(breathButton,spaced());weekView=label(weeklyCount()+" days in the last 7 days",14,Color.DKGRAY,true);weekView.setPadding(0,dp(20),0,dp(8));page.addView(weekView);addBody("Leaving the app pauses the session; it never completes in the background.");}
    private void showJournal(){title("REFLECTION JOURNAL","Private space for the next honest thought");Spinner mood=new Spinner(this);String[] moods={"Peaceful","Grateful","Hopeful","Heavy"};mood.setAdapter(new ArrayAdapter<>(this,android.R.layout.simple_spinner_dropdown_item,moods));mood.setContentDescription("Reflection mood");page.addView(mood,spaced());EditText input=new EditText(this);input.setHint("What is present for you today?");input.setGravity(Gravity.TOP);input.setMinLines(5);input.setInputType(InputType.TYPE_CLASS_TEXT|InputType.TYPE_TEXT_FLAG_MULTI_LINE|InputType.TYPE_TEXT_FLAG_CAP_SENTENCES);input.setContentDescription("Journal reflection");page.addView(input,spaced());Button save=action("Save reflection on this device");save.setOnClickListener(v->saveJournal(input,mood.getSelectedItem().toString()));page.addView(save,spaced());Button export=action("Share saved reflections");export.setOnClickListener(v->shareJournal());page.addView(export,spaced());Button privacy=action("Privacy and local-data details");privacy.setOnClickListener(v->showPrivacy());page.addView(privacy,spaced());TextView heading=label("Saved reflections",20,INK,true);heading.setPadding(0,dp(26),0,dp(8));page.addView(heading);renderJournal();}
    private void saveJournal(EditText input,String mood){String text=input.getText().toString().trim();if(text.isEmpty()){input.setError("Write a reflection before saving.");return;}try{JSONArray old=new JSONArray(prefs.getString("journal_entries","[]")),next=new JSONArray();next.put(new JSONObject().put("date",LocalDate.now().toString()).put("mood",mood).put("text",text));for(int i=0;i<Math.min(49,old.length());i++)next.put(old.getJSONObject(i));prefs.edit().putString("journal_entries",next.toString()).apply();Toast.makeText(this,"Reflection saved.",Toast.LENGTH_SHORT).show();showTab(4);}catch(Exception error){Toast.makeText(this,"Reflection could not be saved.",Toast.LENGTH_LONG).show();}}
    private void shareJournal(){try{JSONArray entries=new JSONArray(prefs.getString("journal_entries","[]"));StringBuilder text=new StringBuilder("DailyBreath reflections\n\n");for(int i=0;i<entries.length();i++){JSONObject entry=entries.getJSONObject(i);text.append(entry.optString("date")).append(" · ").append(entry.optString("mood","Peaceful")).append("\n").append(entry.optString("text")).append("\n\n");}Intent share=new Intent(Intent.ACTION_SEND);share.setType("text/plain");share.putExtra(Intent.EXTRA_TEXT,text.toString());startActivity(Intent.createChooser(share,"Share reflections"));}catch(Exception error){Toast.makeText(this,"Reflections could not be shared.",Toast.LENGTH_LONG).show();}}
    private void renderJournal(){try{JSONArray entries=new JSONArray(prefs.getString("journal_entries","[]"));if(entries.length()==0){addBody("No saved reflections yet.");return;}for(int i=0;i<entries.length();i++){JSONObject entry=entries.getJSONObject(i);LinearLayout item=card(Color.WHITE);item.addView(label(entry.optString("date")+" · "+entry.optString("mood","Peaceful"),12,PURPLE,true));item.addView(label(entry.optString("text"),15,Color.DKGRAY,false));page.addView(item,spaced());}}catch(Exception error){addBody("Saved reflections could not be opened.");}}
    private void toggleBreathing(){if(breathing){breathing=false;handler.removeCallbacks(ticker);}else{if(complete||seconds<=0){seconds=120;complete=false;}breathing=true;handler.removeCallbacks(ticker);handler.postDelayed(ticker,1000);}refreshBreathControls();}
    private void tick(){if(!breathing)return;seconds--;if(seconds<=0){seconds=0;breathing=false;complete=true;recordCompletion();}else handler.postDelayed(ticker,1000);refreshBreathControls();}
    private void refreshBreathControls(){if(timerView==null)return;timerView.setText(format(seconds));phaseView.setText(complete?"Complete":breathing?phase():seconds<120?"Paused":"Ready when you are");breathButton.setText(breathing?"Pause session":complete?"Quick repeat":seconds<120?"Resume session":"Begin breathing");breathButton.setContentDescription(breathButton.getText());if(weekView!=null)weekView.setText(weeklyCount()+" days in the last 7 days");}
    private String phase(){int elapsed=(120-seconds)%14;return elapsed<4?"Inhale":elapsed<8?"Hold":"Exhale";}
    private void recordCompletion(){List<String> days=completionDays();String today=LocalDate.now().toString();if(!days.contains(today))days.add(today);prefs.edit().putString("breath_completion_days",String.join(",",days)).apply();}
    private int weeklyCount(){int count=0;for(String day:completionDays())try{long age=ChronoUnit.DAYS.between(LocalDate.parse(day),LocalDate.now());if(age>=0&&age<7)count++;}catch(Exception ignored){}return count;}
    private List<String> completionDays(){List<String> result=new ArrayList<>();String stored=prefs.getString("breath_completion_days","");if(stored!=null)for(String day:stored.split(","))if(!day.isEmpty())result.add(day);return result;}

    private void loadDailyVerses(){try{JSONObject root=new JSONObject(readAsset("daily-verses.json"));JSONArray entries=root.getJSONArray("entries");for(int i=0;i<entries.length();i++){JSONObject item=entries.getJSONObject(i);dailyVerses.add(new DailyVerse(item.optString("text"),item.optString("reference"),item.isNull("schedule_date")?"":item.optString("schedule_date")));}}catch(Exception ignored){}}
    private void loadLibraries(){try(BufferedReader reader=assetReader("engwebp_vpl.txt")){String line;while((line=reader.readLine())!=null){String[] parts=line.split(" ",3);if(parts.length!=3||!parts[1].contains(":"))continue;String[] location=parts[1].split(":",2);bible.add(new ScriptureVerse(parts[0],number(location[0]),number(location[1]),parts[2],bibleReference(parts[0],location[0],location[1],false)));}}catch(Exception ignored){}try(BufferedReader reader=assetReader("quran-pickthall-vpl.txt")){String line;while((line=reader.readLine())!=null){String[] parts=line.split("\\|",4);if(parts.length!=4)continue;int surah=number(parts[0]),ayah=number(parts[1]);quran.add(new ScriptureVerse(String.valueOf(surah),surah,ayah,parts[3],quranReference(parts[2],surah,ayah)));}}catch(Exception ignored){}}
    private Reading readingOfTheDay(){if(faith==Faith.BIBLE){DailyVerse verse=dailyVerseOfTheDay();return new Reading(verse.text,verse.reference);}if(faith==Faith.TANAKH){String[][] pool={{"EXO","23","32"},{"PSA","46","10"},{"DEU","31","6"},{"ISA","41","10"},{"PRO","3","5"}};String[] selected=pool[Math.floorMod((int)LocalDate.now().toEpochDay(),pool.length)];ScriptureVerse verse=findBible(selected[0],number(selected[1]),number(selected[2]));return verse==null?new Reading("Be still, and know that I am God.","Tehillim 46:10"):new Reading(verse.text,bibleReference(verse.code,String.valueOf(verse.chapter),String.valueOf(verse.number),true));}int[][] pool={{13,28},{2,153},{39,53},{94,5},{3,200}};int[] selected=pool[Math.floorMod((int)LocalDate.now().toEpochDay(),pool.length)];ScriptureVerse verse=findQuran(selected[0],selected[1]);return verse==null?new Reading("Who have believed and whose hearts have rest in the remembrance of Allah. Verily in the remembrance of Allah do hearts find rest!","Ar-Ra'd 13:28"):new Reading(verse.text,verse.reference);}
    private DailyVerse dailyVerseOfTheDay(){String today=LocalDate.now().toString();for(DailyVerse verse:dailyVerses)if(today.equals(verse.date))return verse;if(!dailyVerses.isEmpty())return dailyVerses.get((int)Math.floorMod(LocalDate.now().toEpochDay(),dailyVerses.size()));return new DailyVerse("Be still, and know that I am God.","Psalm 46:10","");}
    private List<ScriptureVerse> libraryFor(Faith requested){if(requested==Faith.QURAN)return quran;if(requested==Faith.BIBLE)return bible;List<ScriptureVerse> result=new ArrayList<>();for(ScriptureVerse verse:bible)if(TANAKH_CODES.contains(verse.code))result.add(new ScriptureVerse(verse.code,verse.chapter,verse.number,verse.text,bibleReference(verse.code,String.valueOf(verse.chapter),String.valueOf(verse.number),true)));return result;}
    private ScriptureVerse findBible(String code,int chapter,int verse){for(ScriptureVerse item:bible)if(item.code.equals(code)&&item.chapter==chapter&&item.number==verse)return item;return null;}
    private ScriptureVerse findQuran(int surah,int ayah){for(ScriptureVerse item:quran)if(item.chapter==surah&&item.number==ayah)return item;return null;}
    private BufferedReader assetReader(String name)throws Exception{InputStream input=getAssets().open(name);return new BufferedReader(new InputStreamReader(input,StandardCharsets.UTF_8));}
    private String readAsset(String name)throws Exception{StringBuilder result=new StringBuilder();try(BufferedReader reader=assetReader(name)){String line;while((line=reader.readLine())!=null)result.append(line).append('\n');}return result.toString();}
    private Faith readFaith(){try{return Faith.valueOf(prefs.getString("selected_faith",Faith.BIBLE.name()));}catch(Exception ignored){return Faith.BIBLE;}}
    private String todayIntro(){return faith==Faith.TANAKH?"A quiet place for Tanakh, breath, and one honest next step.":faith==Faith.QURAN?"A quiet place for Quran, remembrance, and one sincere next step.":"A little room for truth, rest, and recovery.";}
    private String reflectionCopy(Reading reading){return faith==Faith.TANAKH?"Read "+reading.reference+" slowly. Notice what stays with you, and carry it into one faithful action today.":faith==Faith.QURAN?"Read "+reading.reference+" with attention. Reflect on its guidance and carry one sincere response into the day.":"Take three slow breaths, read "+reading.reference+" again, and name one faithful next step.";}
    private String plural(String unit){return unit.equals("ayah")?"ayahs":unit+"s";}

    private void showSources(){page.removeAllViews();title("SOURCES","Scripture translations");addBody("Bible and Tanakh passages use the World English Bible (WEB), a public-domain translation. Quran passages present the English meaning by Mohammed Marmaduke Pickthall, from Project Gutenberg eBook 16955. Daily readings identify their translation in the bundled library. These translations are presented as English meanings and translations, not as replacements for the original sacred texts.");addBody("Full source notes: ebible.org/details.php?id=engwebp and gutenberg.org/ebooks/16955.");Button back=action("Back");back.setOnClickListener(v->showTab(tab));page.addView(back,spaced());}
    private void showPrivacy(){page.removeAllViews();title("PRIVACY","Your local journal");addBody("Daily Breath keeps your reflections, selected faith, and breathing progress in this app’s private on-device storage. The app does not upload this data or include analytics. Device backup is disabled for this app. Choosing Share opens Android’s system share sheet and sends only the reflections you choose to the app or person you select.");Button policy=action("Open full privacy policy");policy.setOnClickListener(v->openUrl("https://beyond-os.com/legal/privacy.php"));page.addView(policy,spaced());Button back=action("Back");back.setOnClickListener(v->showTab(tab));page.addView(back,spaced());}
    private void openUrl(String value){try{startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(value)));}catch(Exception error){Toast.makeText(this,"Privacy policy could not be opened.",Toast.LENGTH_LONG).show();}}
    private void showDetail(String heading,String body){page.removeAllViews();title("DAILY BREATH",heading);addBody(body);Button back=action("Back");back.setOnClickListener(v->showTab(tab));page.addView(back,spaced());}
    private void openIntent(Intent intent){if(intent==null||intent.getData()==null)return;String route=intent.getData().getHost();if(route==null)route=intent.getData().getLastPathSegment();if(route==null)return;route=route.toLowerCase(Locale.US);if(route.contains("torah")||route.contains("tanakh"))faith=Faith.TANAKH;else if(route.contains("quran"))faith=Faith.QURAN;prefs.edit().putString("selected_faith",faith.name()).apply();showTab(route.contains("breathe")?3:route.contains("scripture")||route.contains("bible")||route.contains("torah")||route.contains("tanakh")||route.contains("quran")?1:route.contains("academy")?2:route.contains("journal")?4:0);}
    private void title(String eyebrow,String heading){TextView top=label(eyebrow,12,PURPLE,true);top.setLetterSpacing(.12f);page.addView(top);TextView title=label(heading,30,INK,true);title.setPadding(0,dp(8),0,dp(4));page.addView(title);}
    private void addBody(String text){TextView body=label(text,15,Color.DKGRAY,false);body.setPadding(0,dp(8),0,dp(10));page.addView(body);}
    private TextView label(String text,float sp,int color,boolean bold){TextView view=new TextView(this);view.setText(text);view.setTextSize(sp);view.setTextColor(color);view.setLineSpacing(0,1.15f);view.setTypeface(Typeface.DEFAULT,bold?Typeface.BOLD:Typeface.NORMAL);return view;}
    private Button action(String text){Button button=new Button(this);button.setText(text);button.setAllCaps(false);button.setTextSize(15);button.setTextColor(Color.WHITE);button.setTypeface(Typeface.DEFAULT,Typeface.BOLD);button.setBackground(round(PURPLE,16));button.setPadding(dp(14),dp(11),dp(14),dp(11));button.setContentDescription(text);return button;}
    private LinearLayout card(int color){LinearLayout card=new LinearLayout(this);card.setOrientation(LinearLayout.VERTICAL);card.setPadding(dp(20),dp(20),dp(20),dp(20));card.setBackground(round(color,22));return card;}
    private GradientDrawable round(int color,int radius){GradientDrawable value=new GradientDrawable();value.setColor(color);value.setCornerRadius(dp(radius));return value;}
    private LinearLayout.LayoutParams spaced(){LinearLayout.LayoutParams params=new LinearLayout.LayoutParams(-1,-2);params.topMargin=dp(14);return params;}
    private int dp(int value){return Math.round(value*getResources().getDisplayMetrics().density);} private String format(int value){return String.format(Locale.US,"%d:%02d",value/60,value%60);} private int number(String value){try{return Integer.parseInt(value);}catch(Exception ignored){return 0;}}
    private String bibleReference(String code,String chapter,String verse,boolean jewish){String english=englishNames.get(code),name=jewish?tanakhNames.getOrDefault(code,english):english;return(name==null?code:name)+" "+chapter+":"+verse;}
    private String quranReference(String title,int surah,int ayah){String clean=title.replaceAll("\\s*\\(.*","").trim();return(clean.isEmpty()?"Surah":titleCase(clean))+" "+surah+":"+ayah;}
    private String titleCase(String value){StringBuilder result=new StringBuilder();for(String word:value.toLowerCase(Locale.US).split("\\s+"))if(!word.isEmpty()){if(result.length()>0)result.append(' ');result.append(Character.toUpperCase(word.charAt(0))).append(word.substring(1));}return result.toString();}
    private static Map<String,String> englishNames(){Map<String,String> names=new HashMap<>();String[][] data={{"GEN","Genesis"},{"EXO","Exodus"},{"LEV","Leviticus"},{"NUM","Numbers"},{"DEU","Deuteronomy"},{"JOS","Joshua"},{"JDG","Judges"},{"RUT","Ruth"},{"1SA","1 Samuel"},{"2SA","2 Samuel"},{"1KI","1 Kings"},{"2KI","2 Kings"},{"1CH","1 Chronicles"},{"2CH","2 Chronicles"},{"EZR","Ezra"},{"NEH","Nehemiah"},{"EST","Esther"},{"JOB","Job"},{"PSA","Psalms"},{"PRO","Proverbs"},{"ECC","Ecclesiastes"},{"SOL","Song of Solomon"},{"ISA","Isaiah"},{"JER","Jeremiah"},{"LAM","Lamentations"},{"EZE","Ezekiel"},{"DAN","Daniel"},{"HOS","Hosea"},{"JOE","Joel"},{"AMO","Amos"},{"OBA","Obadiah"},{"JON","Jonah"},{"MIC","Micah"},{"NAH","Nahum"},{"HAB","Habakkuk"},{"ZEP","Zephaniah"},{"HAG","Haggai"},{"ZEC","Zechariah"},{"MAL","Malachi"},{"MAT","Matthew"},{"MAR","Mark"},{"LUK","Luke"},{"JOH","John"},{"ACT","Acts"},{"ROM","Romans"},{"1CO","1 Corinthians"},{"2CO","2 Corinthians"},{"GAL","Galatians"},{"EPH","Ephesians"},{"PHI","Philippians"},{"COL","Colossians"},{"1TH","1 Thessalonians"},{"2TH","2 Thessalonians"},{"1TI","1 Timothy"},{"2TI","2 Timothy"},{"TIT","Titus"},{"PHM","Philemon"},{"HEB","Hebrews"},{"JAM","James"},{"1PE","1 Peter"},{"2PE","2 Peter"},{"1JO","1 John"},{"2JO","2 John"},{"3JO","3 John"},{"JUD","Jude"},{"REV","Revelation"}};for(String[] pair:data)names.put(pair[0],pair[1]);return names;}
    private static Map<String,String> tanakhNames(){Map<String,String> names=englishNames();String[][] data={{"GEN","Bereshit"},{"EXO","Shemot"},{"LEV","Vayikra"},{"NUM","Bamidbar"},{"DEU","Devarim"},{"JOS","Yehoshua"},{"JDG","Shoftim"},{"RUT","Ruth"},{"1SA","Shmuel I"},{"2SA","Shmuel II"},{"1KI","Melakhim I"},{"2KI","Melakhim II"},{"1CH","Divrei Hayamim I"},{"2CH","Divrei Hayamim II"},{"NEH","Nechemyah"},{"JOB","Iyov"},{"PSA","Tehillim"},{"PRO","Mishlei"},{"ECC","Kohelet"},{"SOL","Shir HaShirim"},{"ISA","Yeshayahu"},{"JER","Yirmeyahu"},{"LAM","Eikhah"},{"EZE","Yechezkel"},{"HOS","Hoshea"},{"JOE","Yoel"},{"OBA","Ovadiah"},{"JON","Yonah"},{"MIC","Mikhah"},{"ZEP","Tzefaniah"},{"ZEC","Zekhariah"},{"MAL","Malakhi"}};for(String[] pair:data)names.put(pair[0],pair[1]);return names;}
    private static final class DailyVerse{final String text,reference,date;DailyVerse(String text,String reference,String date){this.text=text;this.reference=reference;this.date=date;}}
    private static final class ScriptureVerse{final String code,text,reference;final int chapter,number;ScriptureVerse(String code,int chapter,int number,String text,String reference){this.code=code;this.chapter=chapter;this.number=number;this.text=text;this.reference=reference;}}
    private static final class Reading{final String text,reference;Reading(String text,String reference){this.text=text;this.reference=reference;}}
}
