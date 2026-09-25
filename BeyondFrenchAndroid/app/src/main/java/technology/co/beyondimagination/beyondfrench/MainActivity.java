package technology.co.beyondimagination.beyondfrench;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Intent;
import android.content.Context;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.net.Uri;
import android.os.Bundle;
import android.os.Build;
import android.speech.tts.TextToSpeech;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.Gravity;
import android.view.KeyEvent;
import android.view.View;
import android.view.ViewGroup;
import android.view.WindowInsets;
import android.view.inputmethod.InputMethodManager;
import android.widget.EditText;
import android.widget.FrameLayout;
import android.widget.HorizontalScrollView;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;

import android.window.OnBackInvokedCallback;
import android.window.OnBackInvokedDispatcher;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.text.Normalizer;
import java.util.Locale;
import java.util.HashSet;
import java.util.Set;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/** Native Beyond French lessons and tools, with the same five destinations as iOS. */
public final class MainActivity extends Activity {
    private static final int BG = Color.rgb(6, 20, 42);
    private static final int CARD = Color.rgb(20, 47, 87);
    private static final int CARD_DARK = Color.rgb(14, 37, 72);
    private static final int BLUE = Color.rgb(59, 125, 239);
    private static final int GOLD = Color.rgb(246, 193, 71);
    private static final int MUTED = Color.rgb(180, 201, 232);
    private static final int GREEN = Color.rgb(67, 205, 133);
    private static final String BASE = "https://beyondimagination.co.technology/beyond-french/";
    private static final String[] TABS = {"Today", "Academy", "Translate", "Dictionary", "More"};
    private static final String[] ICONS = {"☀", "◆", "⇄", "▤", "•••"};
    private static final String[] GUIDE_NAMES = {"Louis", "Irie", "Jazzy", "Pablo"};
    private static final String[] GUIDE_LANGUAGES = {"French", "Patois", "Kreyòl", "Spanish"};
    private static final int[] GUIDE_COLORS = {BLUE, GREEN, Color.rgb(236, 84, 100), GOLD};
    private static final int[] GUIDE_IMAGES = {R.drawable.guide_louis, R.drawable.guide_irie, R.drawable.guide_jazzy, R.drawable.guide_pablo};

    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private SharedPreferences prefs;
    private BeyondAuth auth;
    private final Set<String> completedLessons = new HashSet<>();
    private final Set<String> completedDaily = new HashSet<>();
    private int practiceCount;
    private int progressOwner;
    private String syncStatus = "";
    private TextToSpeech speaker;
    private JSONArray words = new JSONArray();
    private JSONArray modules = new JSONArray();
    private JSONObject daily;
    private FrameLayout host;
    private ScrollView pageScroll;
    private LinearLayout navigation;
    private int tab = 0;
    private String route = "";
    private int moduleIndex = 0;
    private int lessonIndex = 0;
    private int guideIndex = 0;
    private int difficulty = 0;
    private int sourceLanguage = 0;
    private int translateMode = 0;
    private int practiceMode = 0;
    private int practiceIndex = 0;
    private String translationInput = "";
    private String translationResult = "";
    private String translationPronunciation = "";
    private String practiceFeedback = "";

    @Override public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            getOnBackInvokedDispatcher().registerOnBackInvokedCallback(
                OnBackInvokedDispatcher.PRIORITY_DEFAULT, (OnBackInvokedCallback) this::handleBack);
        }
        getWindow().setStatusBarColor(BG);
        getWindow().setNavigationBarColor(BG);
        getWindow().setSoftInputMode(android.view.WindowManager.LayoutParams.SOFT_INPUT_ADJUST_RESIZE);
        prefs = getSharedPreferences("beyond_french", MODE_PRIVATE);
        auth = new BeyondAuth(this, this::authChanged);
        difficulty = prefs.getInt("difficulty", 0);
        try { words = new JSONArray(readAsset("dictionary.json")); } catch (Exception ignored) { }
        try { modules = new JSONObject(readAsset("academy.json")).getJSONArray("modules"); } catch (Exception ignored) { }
        loadProgress(0);
        daily = fallbackDaily();
        speaker = new TextToSpeech(this, status -> {
            if (status == TextToSpeech.SUCCESS) speaker.setLanguage(Locale.FRANCE);
        });

        LinearLayout root = column();
        root.setBackgroundColor(BG);
        root.setOnApplyWindowInsetsListener((view, insets) -> {
            if (android.os.Build.VERSION.SDK_INT >= 30) {
                android.graphics.Insets bars = insets.getInsets(WindowInsets.Type.systemBars());
                view.setPadding(bars.left, bars.top, bars.right, bars.bottom);
            } else {
                view.setPadding(insets.getSystemWindowInsetLeft(), insets.getSystemWindowInsetTop(),
                    insets.getSystemWindowInsetRight(), insets.getSystemWindowInsetBottom());
            }
            return insets;
        });
        LinearLayout header = row();
        header.setGravity(Gravity.CENTER_VERTICAL);
        header.setPadding(dp(20), dp(12), dp(20), dp(8));
        LinearLayout brand = column();
        brand.addView(text("BEYOND FRENCH", 20, Color.WHITE, true));
        brand.addView(text("Speak, listen, remember", 12, MUTED, false));
        header.addView(brand, new LinearLayout.LayoutParams(0, -2, 1));
        ImageView mark = new ImageView(this);
        mark.setImageResource(R.drawable.beyond_french_logo);
        mark.setScaleType(ImageView.ScaleType.CENTER_CROP);
        mark.setBackground(round(CARD, dp(22), 0));
        mark.setClipToOutline(true);
        header.addView(mark, new LinearLayout.LayoutParams(dp(42), dp(42)));
        root.addView(header);
        host = new FrameLayout(this);
        root.addView(host, new LinearLayout.LayoutParams(-1, 0, 1));
        navigation = row();
        navigation.setPadding(dp(6), dp(5), dp(6), dp(5));
        navigation.setBackgroundColor(CARD_DARK);
        root.addView(navigation, new LinearLayout.LayoutParams(-1,
            dp(getResources().getConfiguration().fontScale >= 1.3f ? 72 : 64)));
        setContentView(root);
        render();
        fetchDaily();
        auth.restore();
        auth.complete(getIntent().getData());
    }

    @Override protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        auth.complete(intent.getData());
    }

    private void render() {
        host.removeAllViews();
        ScrollView scroll = new ScrollView(this);
        pageScroll = scroll;
        scroll.setFillViewport(true);
        scroll.setClipToPadding(false);
        LinearLayout body = column();
        body.setPadding(dp(18), dp(14), dp(18), dp(24));
        scroll.addView(body);
        host.addView(scroll);
        if (route.equals("lesson")) lessonScreen(body);
        else if (route.equals("practice")) practiceScreen(body);
        else if (route.equals("progress")) progressScreen(body);
        else if (tab == 0) todayScreen(body);
        else if (tab == 1) academyScreen(body);
        else if (tab == 2) translateScreen(body);
        else if (tab == 3) dictionaryScreen(body);
        else moreScreen(body);
        renderNavigation();
    }

    private void renderNavigation() {
        navigation.removeAllViews();
        for (int i = 0; i < TABS.length; i++) {
            final int selected = i;
            LinearLayout item = column();
            item.setGravity(Gravity.CENTER);
            int tint = tab == i ? BLUE : MUTED;
            TextView symbol = text(ICONS[i], 22, tint, true);
            symbol.setGravity(Gravity.CENTER);
            item.addView(symbol);
            boolean largeText = getResources().getConfiguration().fontScale >= 1.3f;
            String[] compact = {"Today", "Learn", "Translate", "Words", "More"};
            TextView label = text(largeText ? compact[i] : TABS[i], largeText ? 9 : 10, tint, tab == i);
            label.setSingleLine(true);
            label.setGravity(Gravity.CENTER);
            item.addView(label);
            item.setContentDescription(TABS[i]);
            item.setOnClickListener(v -> { tab = selected; route = ""; render(); });
            navigation.addView(item, new LinearLayout.LayoutParams(0, -1, 1));
        }
    }

    private void todayScreen(LinearLayout body) {
        label(body, "Lesson of the day.", GREEN);
        title(body, "Today");
        add(body, "Your French is clocking in. Coffee optional.", 15, MUTED, false, 12);
        LinearLayout phrase = card(body, CARD);
        label(phrase, "TODAY'S PHRASE", GOLD);
        add(phrase, field(daily, "english", "Keep going."), 26, Color.WHITE, true, 10);
        add(phrase, field(daily, "french", "Continue."), 30, GOLD, true, 8);
        add(phrase, field(daily, "french_pronunciation", "Kohn-tee-new"), 15, MUTED, false, 12);
        LinearLayout actions = row();
        phrase.addView(actions);
        action(actions, "▶ Listen", BLUE, () -> speak(field(daily, "french", "Continue."), "fr-FR"), true);
        action(actions, "Practice", CARD_DARK, () -> { route = "practice"; render(); }, false);

        label(body, "FOUR GUIDES · ONE FRENCH LESSON", GOLD);
        String[] values = {field(daily, "french", "Continue."), field(daily, "patois", "Keep on gwaan."),
            field(daily, "kreyol", "Kontinye."), field(daily, "spanish", "Sigue adelante.")};
        String[] locales = {"fr-FR", "en-JM", "ht-HT", "es-ES"};
        for (int start = 0; start < 4; start += 2) {
            LinearLayout pair = row();
            body.addView(pair);
            for (int i = start; i < start + 2; i++) {
                final int index = i;
                LinearLayout tile = cardDetached(GUIDE_COLORS[i]);
                LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(0, -2, 1);
                params.setMargins(dp(3), 0, dp(3), dp(8));
                pair.addView(tile, params);
                ImageView portrait = new ImageView(this);
                portrait.setImageResource(GUIDE_IMAGES[i]);
                portrait.setScaleType(ImageView.ScaleType.FIT_CENTER);
                portrait.setBackground(round(Color.WHITE, dp(12), 0));
                portrait.setClipToOutline(true);
                LinearLayout.LayoutParams portraitParams = new LinearLayout.LayoutParams(dp(66), dp(66));
                portraitParams.setMargins(0, 0, 0, dp(9));
                tile.addView(portrait, portraitParams);
                add(tile, GUIDE_NAMES[i] + " · " + GUIDE_LANGUAGES[i], 12, BG, true, 7);
                add(tile, values[i], 16, BG, true, 6);
                TextView listen = text("Listen ↗", 12, BG, true);
                tile.addView(listen);
                tile.setOnClickListener(v -> speak(values[index], locales[index]));
            }
        }
        LinearLayout culture = card(body, CARD_DARK);
        label(culture, "CULTURE NOTE", GOLD);
        add(culture, field(daily, "culture_note", "A little encouragement can go a long way."), 15, MUTED, false, 0);
        action(body, "Start daily challenge", BLUE, () -> { route = "practice"; practiceMode = 0; render(); }, false);
    }

    private void academyScreen(LinearLayout body) {
        label(body, "ALL LESSONS FREE · 1.2 BETA", GREEN);
        title(body, "Academy");
        LinearLayout intro = card(body, CARD);
        add(intro, "Choose your level. No beret required.", 26, Color.WHITE, true, 8);
        add(intro, "Short lessons for real conversations, with zero homework pile.", 15, MUTED, false, 16);
        label(intro, "DIFFICULTY", GOLD);
        HorizontalScrollView difficultyScroller = new HorizontalScrollView(this);
        difficultyScroller.setHorizontalScrollBarEnabled(false);
        LinearLayout levels = row();
        difficultyScroller.addView(levels);
        intro.addView(difficultyScroller);
        String[] names = {"Beginner", "Intermediate", "Advanced"};
        for (int i = 0; i < names.length; i++) {
            final int choice = i;
            TextView level = chip(names[i], i == difficulty ? BLUE : CARD_DARK);
            level.setSingleLine(true);
            LinearLayout.LayoutParams levelParams = new LinearLayout.LayoutParams(dp(112), dp(44));
            levelParams.setMargins(0, 0, dp(7), dp(10));
            levels.addView(level, levelParams);
            level.setOnClickListener(v -> {
                difficulty = choice;
                prefs.edit().putInt("difficulty", choice).apply();
                render();
            });
        }
        add(intro, new String[]{"Short phrases and a steady start.", "Longer everyday conversations.", "More independent speaking practice."}[difficulty], 13, MUTED, false, 0);
        label(intro, "YOUR FOUR GUIDES", GOLD);
        HorizontalScrollView guideScroller = new HorizontalScrollView(this);
        guideScroller.setHorizontalScrollBarEnabled(false);
        LinearLayout guideRow = row();
        guideScroller.addView(guideRow);
        intro.addView(guideScroller);
        for (int i = 0; i < GUIDE_NAMES.length; i++) {
            LinearLayout guide = column();
            guide.setGravity(Gravity.CENTER);
            LinearLayout.LayoutParams guideParams = new LinearLayout.LayoutParams(dp(82), -2);
            guideParams.setMargins(0, 0, dp(7), 0);
            guideRow.addView(guide, guideParams);
            ImageView image = new ImageView(this);
            image.setImageResource(GUIDE_IMAGES[i]);
            image.setScaleType(ImageView.ScaleType.FIT_CENTER);
            image.setBackground(round(Color.WHITE, dp(12), 0));
            image.setClipToOutline(true);
            guide.addView(image, new LinearLayout.LayoutParams(dp(78), dp(78)));
            TextView name = text(GUIDE_NAMES[i], 11, GUIDE_COLORS[i], true);
            name.setGravity(Gravity.CENTER);
            guide.addView(name);
        }
        int complete = selectedCompletedCount();
        label(body, complete + " LESSONS DONE · " + modules.length() + " MODULES OPEN", GOLD);
        int[] nextLesson = nextIncomplete();
        if (nextLesson != null) {
            JSONObject nextModule = modules.optJSONObject(nextLesson[0]);
            LinearLayout next = card(body, CARD_DARK);
            add(next, "Continue Academy", 13, MUTED, true, 6);
            add(next, field(nextModule, "title", "Academy") + " · Lesson " + (nextLesson[1] + 1), 18, Color.WHITE, true, 4);
            next.setOnClickListener(v -> openLesson(nextLesson[0], nextLesson[1]));
        }
        for (int m = 0; m < modules.length(); m++) {
            JSONObject module = modules.optJSONObject(m);
            if (module == null) continue;
            LinearLayout moduleCard = card(body, CARD);
            add(moduleCard, field(module, "icon", "✦") + "  " + field(module, "title", "Module"), 21, Color.WHITE, true, 5);
            add(moduleCard, field(module, "description", ""), 14, MUTED, false, 9);
            label(moduleCard, "FREE · " + module.optJSONArray("lessons").length() + " LESSONS", GREEN);
            JSONArray lessons = module.optJSONArray("lessons");
            if (lessons == null) continue;
            for (int l = 0; l < lessons.length(); l++) {
                final int mm = m, ll = l;
                JSONObject lesson = lessons.optJSONObject(l);
                LinearLayout row = row();
                row.setGravity(Gravity.CENTER_VERTICAL);
                row.setPadding(dp(3), dp(9), dp(3), dp(9));
                TextView number = text(String.valueOf(l + 1), 17, BLUE, true);
                number.setGravity(Gravity.CENTER);
                row.addView(number, new LinearLayout.LayoutParams(dp(35), dp(35)));
                LinearLayout copy = column();
                copy.addView(text(field(lesson, "title", "Lesson"), 15, Color.WHITE, true));
                copy.addView(text(supportLine(), 12, MUTED, false));
                row.addView(copy, new LinearLayout.LayoutParams(0, -2, 1));
                row.addView(text(done(m, l) ? "✓" : "›", 23, done(m, l) ? GREEN : MUTED, true));
                row.setOnClickListener(v -> openLesson(mm, ll));
                moduleCard.addView(row);
            }
        }
    }

    private void lessonScreen(LinearLayout body) {
        JSONObject module = modules.optJSONObject(moduleIndex);
        JSONArray lessons = module == null ? null : module.optJSONArray("lessons");
        JSONObject lesson = lessons == null ? null : lessons.optJSONObject(lessonIndex);
        if (lesson == null) { route = ""; render(); return; }
        action(body, "‹  Academy", CARD_DARK, () -> { route = ""; render(); }, false);
        label(body, new String[]{"BEGINNER", "INTERMEDIATE", "ADVANCED"}[difficulty] + " · "
            + field(module, "title", "Academy").toUpperCase(Locale.ROOT) + " · LESSON " + (lessonIndex + 1), GOLD);
        title(body, field(lesson, "title", "Lesson"));
        LinearLayout phrase = card(body, CARD);
        add(phrase, field(lesson, "english", ""), 18, MUTED, false, 8);
        add(phrase, field(lesson, "french", ""), 30, GOLD, true, 8);
        add(phrase, field(lesson, "pronunciation", ""), 16, MUTED, false, 10);
        action(phrase, "▶ Listen in French", BLUE, () -> speak(field(lesson, "french", ""), "fr-FR"), false);
        LinearLayout teaching = card(body, CARD_DARK);
        label(teaching, "LEARN", GOLD);
        add(teaching, teachingFor(lesson), 15, Color.WHITE, false, 0);
        LinearLayout guides = card(body, CARD);
        label(guides, "LEARN WITH A GUIDE", GOLD);
        guideSelector(guides, lesson);
        LinearLayout practice = card(body, CARD_DARK);
        label(practice, "SAY IT IN FRENCH", GOLD);
        add(practice, practiceFor(lesson), 14, MUTED, false, 10);
        EditText answer = input("Type the French phrase");
        practice.addView(answer, new LinearLayout.LayoutParams(-1, dp(52)));
        TextView feedback = text("", 14, GREEN, true);
        practice.addView(feedback);
        action(practice, "Check answer", BLUE, () -> {
            InputMethodManager keyboard = (InputMethodManager) getSystemService(Context.INPUT_METHOD_SERVICE);
            if (keyboard != null) keyboard.hideSoftInputFromWindow(answer.getWindowToken(), 0);
            answer.clearFocus();
            if (samePhrase(answer.getText().toString(), field(lesson, "french", ""))) {
                completedLessons.add(lessonId(moduleIndex, lessonIndex));
                saveProgress();
                syncProgress();
                feedback.setText("Très bien! The imaginary waiter is impressed.");
            } else feedback.setText("Nearly! The croissant is still waiting.");
        }, false);
        LinearLayout culture = card(body, CARD_DARK);
        label(culture, "CULTURE NOTE", GOLD);
        add(culture, field(lesson, "culture", ""), 14, MUTED, false, 0);
        if (lessonIndex + 1 < lessons.length()) {
            action(body, "Next lesson  →", CARD, () -> openLesson(moduleIndex, lessonIndex + 1), false);
        }
    }

    private void guideSelector(LinearLayout parent, JSONObject lesson) {
        HorizontalScrollView scroller = new HorizontalScrollView(this);
        scroller.setHorizontalScrollBarEnabled(false);
        LinearLayout row = row();
        scroller.addView(row);
        parent.addView(scroller);
        TextView guideText = text("", 14, MUTED, false);
        String[] prompts = {
            "Louis: I’ll say it slowly. Your tongue can catch up.",
            "Irie: Compare the Patois, then let French take the wheel.",
            "Jazzy: Hear that familiar sound? Sneaky, right?",
            "Pablo: Spanish gives you a head start. French has plot twists."
        };
        for (int i = 0; i < GUIDE_NAMES.length; i++) {
            final int selected = i;
            LinearLayout tile = column();
            tile.setGravity(Gravity.CENTER);
            tile.setPadding(dp(5), dp(5), dp(5), dp(6));
            tile.setBackground(round(i == guideIndex ? CARD_DARK : CARD, dp(14), i == guideIndex ? GOLD : 0));
            LinearLayout.LayoutParams tileParams = new LinearLayout.LayoutParams(dp(82), -2);
            tileParams.setMargins(0, 0, dp(7), 0);
            row.addView(tile, tileParams);
            ImageView image = new ImageView(this);
            image.setImageResource(GUIDE_IMAGES[i]);
            image.setScaleType(ImageView.ScaleType.FIT_CENTER);
            image.setBackground(round(Color.WHITE, dp(12), 0));
            tile.addView(image, new LinearLayout.LayoutParams(dp(70), dp(70)));
            TextView name = text(GUIDE_NAMES[i], 12, GUIDE_COLORS[i], true);
            name.setGravity(Gravity.CENTER);
            tile.addView(name);
            TextView language = text(GUIDE_LANGUAGES[i], 10, MUTED, false);
            language.setGravity(Gravity.CENTER);
            tile.addView(language);
            tile.setOnClickListener(v -> { guideIndex = selected; guideSelectorRefresh(parent, lesson); });
        }
        parent.addView(guideText);
        String bridge = bridgeFor(lesson, guideIndex);
        guideText.setText(prompts[guideIndex] + "\n" + bridge);
        if (!bridge.startsWith("Listen to the French phrase")) {
            String[] locales = {"fr-FR", "en-JM", "ht-HT", "es-ES"};
            action(parent, "▶ Listen with " + GUIDE_NAMES[guideIndex], CARD_DARK,
                () -> speak(bridge, locales[guideIndex]), false);
        }
    }

    private void guideSelectorRefresh(LinearLayout parent, JSONObject lesson) {
        parent.removeViews(1, parent.getChildCount() - 1);
        guideSelector(parent, lesson);
    }

    private String bridgeFor(JSONObject lesson, int guide) {
        if (guide == 0) return field(lesson, "french", "");
        String key = new String[]{"", "patois", "kreyol", "spanish"}[guide];
        String direct = field(lesson, key, "");
        if (!direct.isEmpty()) return direct;
        String english = field(lesson, "english", "");
        for (int i = 0; i < words.length(); i++) {
            JSONObject word = words.optJSONObject(i);
            if (word != null && samePhrase(english, field(word, "english", ""))) return field(word, key, "");
        }
        return "Listen to the French phrase, then try it yourself.";
    }

    private String supportLine() {
        return new String[]{"Give it a go. The café is imaginary.", "Aim for a conversation, not a perfect accent.",
            "One useful phrase today beats a fluent plan for someday."}[difficulty];
    }

    private String teachingFor(JSONObject lesson) {
        String basic = field(lesson, "teaching", "");
        if (difficulty == 0) return basic + " Listen once, then compare it with the guides' Spanish, Kreyòl, and Patois bridges.";
        if (difficulty == 1) return basic + " Practice in French and consider its tone in a real conversation.";
        return basic + " Notice the level of formality and how the guides express the same idea in their cultures.";
    }

    private String practiceFor(JSONObject lesson) {
        String basic = field(lesson, "practice", "");
        if (difficulty == 0) return basic + " Then say the French phrase aloud.";
        if (difficulty == 1) return basic + " Add a French follow-up sentence you might actually use.";
        return basic + " Say it slowly, at normal speed, and in a real-life scenario.";
    }

    private void translateScreen(LinearLayout body) {
        title(body, "Translate");
        add(body, "Tell Jaguar what you mean. It speaks French; you take the credit.", 15, MUTED, false, 15);
        LinearLayout modes = row();
        body.addView(modes);
        action(modes, "Translate", translateMode == 0 ? BLUE : CARD, () -> { translateMode = 0; translationResult = ""; translationPronunciation = ""; render(); }, true);
        action(modes, "Ask", translateMode == 1 ? BLUE : CARD, () -> { translateMode = 1; translationResult = ""; translationPronunciation = ""; render(); }, false);
        if (translateMode == 0) {
            label(body, "FROM", GOLD);
            HorizontalScrollView scroller = new HorizontalScrollView(this);
            scroller.setHorizontalScrollBarEnabled(false);
            LinearLayout languages = row();
            scroller.addView(languages);
            body.addView(scroller);
            String[] choices = {"English", "Spanish", "Haitian Kreyòl", "Jamaican Patois", "French"};
            for (int i = 0; i < choices.length; i++) {
                final int selected = i;
                TextView chip = chip(choices[i], i == sourceLanguage ? BLUE : CARD);
                LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(-2, dp(42));
                params.setMargins(0, 0, dp(7), dp(10));
                languages.addView(chip, params);
                chip.setOnClickListener(v -> { sourceLanguage = selected; translationResult = ""; translationPronunciation = ""; render(); });
            }
            label(body, "→ FRENCH", GOLD);
        }
        label(body, translateMode == 0 ? "YOUR PHRASE" : "ASK ABOUT FRENCH", GOLD);
        EditText entry = input(translateMode == 0 ? "Type a phrase to translate" : "Ask a French language question");
        entry.setMinLines(4);
        entry.setGravity(Gravity.TOP);
        entry.setText(translationInput);
        entry.addTextChangedListener(watcher(text -> translationInput = text));
        body.addView(entry);
        action(body, translateMode == 0 ? "Translate into French" : "Ask", BLUE,
            () -> submitTranslation(entry.getText().toString()), false);
        if (!translationResult.isEmpty()) {
            LinearLayout result = card(body, CARD);
            label(result, translateMode == 0 ? "FRENCH" : "ANSWER", GOLD);
            add(result, translationResult, 21, Color.WHITE, true, 7);
            if (!translationPronunciation.isEmpty()) add(result, translationPronunciation, 14, MUTED, false, 8);
            if (translateMode == 0) action(result, "▶ Listen in French", CARD_DARK,
                () -> speak(translationResult, "fr-FR"), false);
        }
    }

    private void submitTranslation(String input) {
        String trimmed = input.trim();
        if (trimmed.isEmpty()) { translationResult = "Enter a phrase first."; render(); return; }
        String[] sources = {"english", "spanish", "kreyol", "patois", "french"};
        int mode = translateMode;
        int source = sourceLanguage;
        translationResult = "Working…";
        translationPronunciation = "";
        render();
        showResultInView();
        io.execute(() -> {
            String result;
            String pronunciation = "";
            try {
                HttpURLConnection connection = (HttpURLConnection) new URL(BASE + "api/jaguar.php").openConnection();
                connection.setConnectTimeout(8000);
                connection.setReadTimeout(12000);
                connection.setRequestMethod("POST");
                connection.setRequestProperty("Content-Type", "application/json");
                connection.setDoOutput(true);
                JSONObject request = new JSONObject();
                request.put("input", trimmed);
                request.put("from", mode == 1 ? "french" : sources[source]);
                request.put("to", "french");
                request.put("mode", mode == 1 ? "question" : "translate");
                connection.getOutputStream().write(request.toString().getBytes(StandardCharsets.UTF_8));
                InputStream stream = connection.getResponseCode() < 400 ? connection.getInputStream() : connection.getErrorStream();
                JSONObject reply = new JSONObject(readStream(stream));
                JSONObject nested = reply.optJSONObject("response");
                result = mode == 1
                    ? field(reply, "message", field(nested, "message", "No answer returned."))
                    : field(reply, "translation", field(nested, "translation", field(nested, "message", field(reply, "message", "No translation returned."))));
                pronunciation = field(reply, "pronunciation", field(nested, "pronunciation", ""));
                connection.disconnect();
            } catch (Exception error) {
                JSONObject match = lookup(trimmed, sources[source]);
                result = mode == 0 && match != null ? field(match, "french", "")
                    : mode == 1 ? "Ask needs an internet connection. Dictionary lessons still work offline."
                    : "Translation is unavailable offline. Try a word from the Dictionary.";
                if (match != null) pronunciation = field(match, "pronunciation", "");
            }
            String finalResult = result, finalPronunciation = pronunciation;
            runOnUiThread(() -> { translationResult = finalResult; translationPronunciation = finalPronunciation; if (tab == 2) { render(); showResultInView(); } });
        });
    }

    private void showResultInView() {
        ScrollView current = pageScroll;
        if (current != null) current.post(() -> { if (pageScroll == current) current.fullScroll(View.FOCUS_DOWN); });
    }

    private void dictionaryScreen(LinearLayout body) {
        title(body, "Dictionary");
        add(body, words.length() + " useful words in five languages. Find the word before charades gets weird.", 15, MUTED, false, 12);
        EditText search = input("Search every language");
        body.addView(search);
        LinearLayout results = column();
        body.addView(results);
        search.addTextChangedListener(watcher(value -> fillDictionary(results, value)));
        fillDictionary(results, "");
    }

    private void fillDictionary(LinearLayout parent, String query) {
        parent.removeAllViews();
        String q = normalize(query);
        int matches = 0;
        for (int i = 0; i < words.length(); i++) {
            JSONObject word = words.optJSONObject(i);
            if (word == null) continue;
            String combined = field(word, "english", "") + " " + field(word, "french", "") + " " + field(word, "spanish", "")
                + " " + field(word, "kreyol", "") + " " + field(word, "patois", "") + " " + field(word, "type", "");
            if (!normalize(combined).contains(q)) continue;
            matches++;
            if (matches > 24) continue;
            LinearLayout card = card(parent, CARD);
            add(card, field(word, "english", ""), 18, Color.WHITE, true, 4);
            add(card, field(word, "french", ""), 22, GOLD, true, 3);
            add(card, field(word, "pronunciation", ""), 13, MUTED, false, 7);
            add(card, "Pablo: " + field(word, "spanish", "") + "   ·   Jazzy: " + field(word, "kreyol", ""), 12, MUTED, false, 3);
            add(card, "Irie: " + field(word, "patois", ""), 12, MUTED, false, 7);
            action(card, "▶ Listen", CARD_DARK, () -> speak(field(word, "french", ""), "fr-FR"), false);
        }
        if (matches == 0) add(parent, "No written translation found. Try a shorter phrase.", 15, MUTED, false, 0);
        else add(parent, matches + (matches > 24 ? " results · showing first 24" : " results"), 12, MUTED, false, 0);
    }

    private void practiceScreen(LinearLayout body) {
        action(body, "‹  Today", CARD_DARK, () -> { route = ""; tab = 0; render(); }, false);
        title(body, "Practice");
        LinearLayout modes = row();
        body.addView(modes);
        action(modes, "Daily", practiceMode == 0 ? BLUE : CARD, () -> { practiceMode = 0; practiceFeedback = ""; render(); }, true);
        action(modes, "Dictionary", practiceMode == 1 ? BLUE : CARD, () -> { practiceMode = 1; practiceFeedback = ""; render(); }, false);
        JSONObject prompt = practiceMode == 0 ? daily : words.optJSONObject(practiceIndex % Math.max(1, words.length()));
        String english = field(prompt, "english", "Hello!");
        String french = field(prompt, practiceMode == 0 ? "answer" : "french", "Bonjour !");
        LinearLayout card = card(body, CARD);
        add(card, practiceMode == 0 ? field(prompt, "challenge", "How would you say “" + english + "” in French?")
            : "Translate “" + english + "” into French.", 22, Color.WHITE, true, 14);
        EditText answer = input("Type the French answer");
        card.addView(answer);
        LinearLayout controls = row();
        card.addView(controls);
        action(controls, "Check", BLUE, () -> {
            if (samePhrase(answer.getText().toString(), french)) {
                practiceCount++;
                if (practiceMode == 0 && daily.optInt("id", 0) > 0) completedDaily.add(String.valueOf(daily.optInt("id")));
                saveProgress();
                syncProgress();
                practiceFeedback = "Très bien! Your imaginary teacher is delighted.";
                if (practiceMode == 1) practiceIndex++;
            } else practiceFeedback = "Not quite. The croissant is willing to wait.";
            render();
        }, true);
        action(controls, "▶ Listen", CARD_DARK, () -> speak(french, "fr-FR"), false);
        if (!practiceFeedback.isEmpty()) add(card, practiceFeedback, 15, GREEN, true, 0);
        action(body, "Reveal answer", CARD_DARK, () -> { practiceFeedback = "Answer: " + french; render(); }, false);
        action(body, "Next word", CARD_DARK, () -> { practiceMode = 1; practiceIndex++; practiceFeedback = ""; render(); }, false);
        label(body, "HINT", GOLD);
        add(body, field(prompt, practiceMode == 0 ? "french_pronunciation" : "pronunciation", ""), 14, MUTED, false, 0);
    }

    private void moreScreen(LinearLayout body) {
        title(body, "More");
        LinearLayout progress = card(body, CARD);
        add(progress, "Your progress", 21, Color.WHITE, true, 8);
        add(progress, completedCount() + " Academy lessons · " + practiceCount + " practice answers", 15, MUTED, false, 8);
        add(progress, auth.signedIn() ? "Progress syncs with Beyond ID." : "Guest progress is saved on this device.", 13, MUTED, false, 8);
        action(progress, "View progress", BLUE, () -> { route = "progress"; render(); }, false);
        LinearLayout account = card(body, CARD_DARK);
        add(account, "Beyond ID", 21, Color.WHITE, true, 8);
        add(account, auth.message(), 14, MUTED, false, 8);
        if (!syncStatus.isEmpty()) add(account, syncStatus, 13, GOLD, false, 8);
        if (auth.signedIn()) {
            action(account, "Sync now", BLUE, this::syncProgress, false);
            action(account, "Sign out", CARD, auth::signOut, false);
            action(account, "Request account deletion", CARD, () -> new AlertDialog.Builder(this)
                .setTitle("Request Beyond ID account deletion?")
                .setMessage("This affects your Beyond ID across connected apps. The request is processed by the Beyond ID team.")
                .setNegativeButton("Cancel", null)
                .setPositiveButton("Request deletion", (dialog, which) -> auth.requestDeletion())
                .show(), false);
        } else action(account, "Sign in to sync", BLUE, auth::signIn, false);
    }

    private void progressScreen(LinearLayout body) {
        action(body, "‹  More", CARD_DARK, () -> { route = ""; tab = 4; render(); }, false);
        title(body, "Your progress");
        LinearLayout stats = card(body, CARD);
        add(stats, completedCount() + " lessons completed", 26, Color.WHITE, true, 5);
        add(stats, "Across all difficulty levels", 14, MUTED, false, 12);
        add(stats, practiceCount + " correct practice answers", 18, GOLD, true, 0);
        String[] groups = {"kids", "teen", "adult"};
        String[] names = {"Beginner", "Intermediate", "Advanced"};
        for (int i = 0; i < groups.length; i++) {
            int count = 0;
            for (String id : completedLessons) if (id.startsWith(groups[i] + "-")) count++;
            LinearLayout level = card(body, CARD_DARK);
            add(level, names[i], 17, Color.WHITE, true, 5);
            add(level, count + " / " + totalLessons() + " lessons", 15, GOLD, true, 0);
        }
        add(body, auth.signedIn() ? "Progress is saved here and synced with Beyond ID." : "Guest progress is saved locally. Sign in from More to restore it on another device.", 14, MUTED, false, 0);
    }

    private void fetchDaily() {
        io.execute(() -> {
            try {
                HttpURLConnection connection = (HttpURLConnection) new URL(BASE + "api/today.php").openConnection();
                connection.setConnectTimeout(8000);
                connection.setReadTimeout(10000);
                JSONObject response = new JSONObject(readStream(connection.getInputStream()));
                JSONObject lesson = response.optJSONObject("lesson");
                connection.disconnect();
                if (lesson != null) runOnUiThread(() -> { daily = lesson; if (tab == 0 && route.isEmpty()) render(); });
            } catch (Exception ignored) { }
        });
    }

    private JSONObject fallbackDaily() {
        JSONObject result = new JSONObject();
        try {
            result.put("english", "Keep going."); result.put("french", "Continue.");
            result.put("french_pronunciation", "Kohn-tee-new"); result.put("patois", "Keep on gwaan.");
            result.put("kreyol", "Kontinye."); result.put("spanish", "Sigue adelante.");
            result.put("culture_note", "A little encouragement can go a long way.");
            result.put("challenge", "How would you say “Keep going.” in French?"); result.put("answer", "Continue.");
        } catch (Exception ignored) { }
        return result;
    }

    private String ownerKey(int owner, String field) { return "progress_" + owner + "_" + field; }

    private void loadProgress(int owner) {
        progressOwner = owner;
        completedLessons.clear();
        completedLessons.addAll(prefs.getStringSet(ownerKey(owner, "lessons"), new HashSet<>()));
        completedDaily.clear();
        completedDaily.addAll(prefs.getStringSet(ownerKey(owner, "daily"), new HashSet<>()));
        practiceCount = prefs.getInt(ownerKey(owner, "practice"), 0);
        if (owner == 0 && !prefs.getBoolean("progress_migrated", false)) {
            for (int m = 0; m < modules.length(); m++) {
                JSONArray lessons = modules.optJSONObject(m).optJSONArray("lessons");
                if (lessons == null) continue;
                for (int l = 0; l < lessons.length(); l++) {
                    if (prefs.getBoolean(lessonKey(m, l), false)) completedLessons.add(lessonId(m, l));
                }
            }
            practiceCount = Math.max(practiceCount, prefs.getInt("practice_correct", 0));
            prefs.edit().putBoolean("progress_migrated", true).apply();
            saveProgress();
        }
    }

    private void saveProgress() {
        prefs.edit().putStringSet(ownerKey(progressOwner, "lessons"), new HashSet<>(completedLessons))
            .putStringSet(ownerKey(progressOwner, "daily"), new HashSet<>(completedDaily))
            .putInt(ownerKey(progressOwner, "practice"), practiceCount).apply();
    }

    private void authChanged() {
        int owner = auth.signedIn() ? auth.userId() : 0;
        if (owner != progressOwner) {
            if (owner > 0) {
                Set<String> guestLessons = new HashSet<>(completedLessons);
                Set<String> guestDaily = new HashSet<>(completedDaily);
                int guestPractice = practiceCount;
                loadProgress(owner);
                completedLessons.addAll(guestLessons);
                completedDaily.addAll(guestDaily);
                practiceCount = Math.max(practiceCount, guestPractice);
                saveProgress();
                syncProgress();
            } else loadProgress(0);
        }
        if (tab == 4) render();
    }

    private void syncProgress() {
        if (!auth.signedIn()) return;
        final int owner = auth.userId();
        final Set<String> localLessons = new HashSet<>(completedLessons);
        final Set<String> localDaily = new HashSet<>(completedDaily);
        final int localPractice = practiceCount;
        syncStatus = "Syncing…";
        if (tab == 4) render();
        io.execute(() -> {
            try {
                JSONObject remote = auth.progress("GET", null);
                JSONArray remoteLessons = remote.optJSONArray("completed_lesson_ids");
                if (remoteLessons != null) for (int i = 0; i < remoteLessons.length(); i++) localLessons.add(remoteLessons.optString(i));
                JSONArray remoteDaily = remote.optJSONArray("completed_daily_lesson_ids");
                if (remoteDaily != null) for (int i = 0; i < remoteDaily.length(); i++) localDaily.add(remoteDaily.optString(i));
                JSONObject snapshot = new JSONObject();
                snapshot.put("completed_lesson_ids", new JSONArray(localLessons));
                JSONArray dailyIds = new JSONArray();
                for (String id : localDaily) try { dailyIds.put(Integer.parseInt(id)); } catch (NumberFormatException ignored) { }
                snapshot.put("completed_daily_lesson_ids", dailyIds);
                snapshot.put("correct_practice_count", Math.max(localPractice, remote.optInt("correct_practice_count", 0)));
                JSONObject merged = auth.progress("PUT", snapshot);
                runOnUiThread(() -> {
                    if (!auth.signedIn() || auth.userId() != owner) return;
                    JSONArray lessons = merged.optJSONArray("completed_lesson_ids");
                    if (lessons != null) for (int i = 0; i < lessons.length(); i++) completedLessons.add(lessons.optString(i));
                    JSONArray daily = merged.optJSONArray("completed_daily_lesson_ids");
                    if (daily != null) for (int i = 0; i < daily.length(); i++) completedDaily.add(daily.optString(i));
                    practiceCount = Math.max(practiceCount, merged.optInt("correct_practice_count", 0));
                    saveProgress();
                    syncStatus = "Progress is up to date.";
                    render();
                });
            } catch (Exception error) {
                runOnUiThread(() -> { syncStatus = "Sync is unavailable. Your progress is safe on this device."; if (tab == 4) render(); });
            }
        });
    }

    private void openLesson(int module, int lesson) { tab = 1; route = "lesson"; moduleIndex = module; lessonIndex = lesson; guideIndex = 0; render(); }
    private int[] nextIncomplete() {
        for (int m = 0; m < modules.length(); m++) {
            JSONObject item = modules.optJSONObject(m);
            JSONArray lessons = item == null ? null : item.optJSONArray("lessons");
            if (lessons == null) continue;
            for (int l = 0; l < lessons.length(); l++) if (!done(m, l)) return new int[]{m, l};
        }
        return null;
    }
    private int completedCount() { return completedLessons.size(); }
    private int selectedCompletedCount() { int count = 0; for (int m = 0; m < modules.length(); m++) { JSONArray ls = modules.optJSONObject(m).optJSONArray("lessons"); if (ls != null) for (int l = 0; l < ls.length(); l++) if (done(m, l)) count++; } return count; }
    private int totalLessons() { int count = 0; for (int m = 0; m < modules.length(); m++) { JSONArray ls = modules.optJSONObject(m).optJSONArray("lessons"); if (ls != null) count += ls.length(); } return count; }
    private boolean done(int module, int lesson) { return completedLessons.contains(lessonId(module, lesson)); }
    private String lessonKey(int module, int lesson) { return "lesson_" + module + "_" + lesson; }
    private String lessonId(int module, int lesson) {
        return new String[]{"kids", "teen", "adult"}[difficulty] + "-"
            + field(modules.optJSONObject(module), "slug", "module") + "-" + (lesson + 1);
    }
    private JSONObject lookup(String phrase, String source) { for (int i = 0; i < words.length(); i++) { JSONObject word = words.optJSONObject(i); if (word != null && samePhrase(phrase, field(word, source, ""))) return word; } return null; }
    private void speak(String value, String locale) { if (speaker == null || value.isEmpty()) return; speaker.setLanguage(Locale.forLanguageTag(locale)); speaker.speak(value, TextToSpeech.QUEUE_FLUSH, null, "beyond-french"); }
    private void openLink(String url) { startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url))); }
    private String readAsset(String name) throws Exception { try (InputStream stream = getAssets().open(name)) { return readStream(stream); } }
    private static String readStream(InputStream stream) throws Exception { try (InputStream in = stream; ByteArrayOutputStream bytes = new ByteArrayOutputStream()) { byte[] buffer = new byte[4096]; int count; while ((count = in.read(buffer)) != -1) bytes.write(buffer, 0, count); return bytes.toString("UTF-8"); } }
    private static String field(JSONObject object, String key, String fallback) { return object == null ? fallback : object.optString(key, fallback); }
    private static String normalize(String value) { return Normalizer.normalize(value == null ? "" : value.toLowerCase(Locale.ROOT), Normalizer.Form.NFD).replaceAll("\\p{M}", "").replaceAll("[^a-z0-9]+", " ").trim(); }
    private static boolean samePhrase(String one, String two) { return !normalize(one).isEmpty() && normalize(one).equals(normalize(two)); }

    private LinearLayout column() { LinearLayout view = new LinearLayout(this); view.setOrientation(LinearLayout.VERTICAL); return view; }
    private LinearLayout row() { LinearLayout view = new LinearLayout(this); view.setOrientation(LinearLayout.HORIZONTAL); return view; }
    private int dp(float value) { return Math.round(value * getResources().getDisplayMetrics().density); }
    private GradientDrawable round(int fill, int radius, int border) { GradientDrawable drawable = new GradientDrawable(); drawable.setColor(fill); drawable.setCornerRadius(radius); if (border != 0) drawable.setStroke(dp(1), border); return drawable; }
    private TextView text(String value, float size, int color, boolean bold) { TextView view = new TextView(this); view.setText(value); view.setTextColor(color); view.setTextSize(size); view.setTypeface(Typeface.create(bold ? "sans-serif-rounded" : "sans-serif", bold ? Typeface.BOLD : Typeface.NORMAL)); view.setIncludeFontPadding(false); return view; }
    private void add(LinearLayout parent, String value, float size, int color, boolean bold, int bottom) { TextView view = text(value, size, color, bold); LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(-1, -2); params.setMargins(0, 0, 0, dp(bottom)); parent.addView(view, params); }
    private void title(LinearLayout parent, String value) { TextView view = text(value, 34, Color.WHITE, true); view.setTypeface(Typeface.create("casual", Typeface.BOLD)); view.setLetterSpacing(0.01f); LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(-1, -2); params.setMargins(0, 0, 0, dp(18)); parent.addView(view, params); }
    private void label(LinearLayout parent, String value, int color) { add(parent, value, 11, color, true, 9); }
    private LinearLayout card(LinearLayout parent, int background) { LinearLayout card = cardDetached(background); LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(-1, -2); params.setMargins(0, 0, 0, dp(14)); parent.addView(card, params); return card; }
    private LinearLayout cardDetached(int background) { LinearLayout view = column(); view.setPadding(dp(18), dp(18), dp(18), dp(18)); view.setBackground(round(background, dp(20), Color.rgb(40, 72, 117))); return view; }
    private TextView chip(String value, int background) { TextView view = text(value, 13, Color.WHITE, true); view.setGravity(Gravity.CENTER); view.setPadding(dp(12), 0, dp(12), 0); view.setBackground(round(background, dp(16), 0)); return view; }
    private void action(LinearLayout parent, String value, int background, Runnable click, boolean weighted) { TextView view = chip(value, background); view.setMinHeight(dp(44)); LinearLayout.LayoutParams params = weighted ? new LinearLayout.LayoutParams(0, dp(44), 1) : new LinearLayout.LayoutParams(-1, dp(44)); if (parent.getOrientation() == LinearLayout.HORIZONTAL) { params = new LinearLayout.LayoutParams(0, dp(44), 1); params.setMargins(0, 0, dp(7), dp(10)); } else params.setMargins(0, dp(8), 0, dp(8)); parent.addView(view, params); view.setOnClickListener(v -> click.run()); }
    private EditText input(String hint) { EditText view = new EditText(this); view.setSingleLine(false); view.setTextSize(16); view.setTextColor(Color.WHITE); view.setHintTextColor(MUTED); view.setHint(hint); view.setPadding(dp(14), dp(12), dp(14), dp(12)); view.setBackground(round(CARD_DARK, dp(14), Color.rgb(44, 75, 121))); return view; }
    private interface TextChange { void onChange(String value); }
    private TextWatcher watcher(TextChange handler) { return new TextWatcher() { public void beforeTextChanged(CharSequence value, int start, int count, int after) { } public void onTextChanged(CharSequence value, int start, int before, int count) { handler.onChange(value.toString()); } public void afterTextChanged(Editable value) { } }; }
    private void handleBack() {
        if (!route.isEmpty()) { route = ""; render(); }
        else if (tab != 0) { tab = 0; render(); }
        else finish();
    }
    @Override public boolean onKeyDown(int keyCode, KeyEvent event) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU && keyCode == KeyEvent.KEYCODE_BACK) {
            handleBack();
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }
    @Override protected void onDestroy() { io.shutdownNow(); auth.close(); if (speaker != null) speaker.shutdown(); super.onDestroy(); }
}
