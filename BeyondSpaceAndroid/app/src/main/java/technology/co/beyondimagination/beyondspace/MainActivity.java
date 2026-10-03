package technology.co.beyondimagination.beyondspace;

import android.app.Activity;
import android.content.Intent;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.Gravity;
import android.view.View;
import android.widget.Button;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.Spinner;
import android.widget.TextView;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class MainActivity extends Activity {
    private static final String FACT_ENDPOINT = "https://beyondimagination.co.technology/beyond-space/api/daily-space-fact.php";
    private static final String HOROSCOPE_ENDPOINT = "https://beyondimagination.co.technology/beyond-space/api/daily-horoscope.php";
    private static final int INK = Color.rgb(7, 11, 24);
    private static final int PANEL = Color.rgb(17, 28, 48);
    private static final int CYAN = Color.rgb(142, 233, 255);
    private static final int WHITE = Color.rgb(248, 250, 255);
    private static final int MUTED = Color.rgb(192, 205, 224);

    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private final Handler mainHandler = new Handler(Looper.getMainLooper());
    private TextView factNumber;
    private TextView title;
    private TextView factText;
    private TextView lessonText;
    private TextView dateText;
    private TextView statusText;
    private Button retryButton;
    private Button saveButton;
    private TextView astrologyReading;
    private TextView astrologyDate;
    private JSONObject astrologyItems = new JSONObject();
    private String selectedSign = "Virgo";
    private String currentFactId = "";
    private String sourceUrl = "";
    private String academyUrl = "https://beyondimagination.co.technology/beyond-space/academy.php";
    private String spaceTVUrl = "https://beyondimagination.co.technology/beyond-tv/channel.php?slug=space-tv";
    private String youtubeUrl = "https://www.youtube.com/playlist?list=PLXBcsPKqNstB10447aKbDnkPEJdTV9sj-";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getWindow().setStatusBarColor(INK);
        getWindow().setNavigationBarColor(INK);
        buildScreen();
        loadToday();
        loadDailyAstrology();
    }

    private void buildScreen() {
        ScrollView scroll = new ScrollView(this);
        scroll.setFillViewport(true);
        scroll.setBackgroundColor(INK);
        LinearLayout page = new LinearLayout(this);
        page.setOrientation(LinearLayout.VERTICAL);
        page.setPadding(dp(22), dp(28), dp(22), dp(30));
        scroll.addView(page);

        TextView brand = text("BEYOND SPACE", 13, CYAN, true);
        brand.setLetterSpacing(0.14f);
        page.addView(brand);
        addSpace(page, 12);

        TextView heading = text("Daily Space", 34, WHITE, true);
        page.addView(heading);
        TextView subtitle = text("One verified wonder, every day.", 17, MUTED, false);
        add(page, subtitle, 0, 8);
        statusText = text("Loading today’s fact…", 14, MUTED, false);
        add(page, statusText, 0, 22);
        retryButton = actionButton("Try again", false);
        retryButton.setVisibility(View.GONE);
        retryButton.setOnClickListener(view -> loadToday());
        add(page, retryButton, 0, 14);

        LinearLayout card = new LinearLayout(this);
        card.setOrientation(LinearLayout.VERTICAL);
        card.setPadding(dp(20), dp(22), dp(20), dp(22));
        card.setBackground(rounded(PANEL, dp(22), 0x36FFFFFF));
        page.addView(card, new LinearLayout.LayoutParams(-1, -2));

        factNumber = text("DAILY FACT", 12, CYAN, true);
        factNumber.setLetterSpacing(0.1f);
        card.addView(factNumber);
        addSpace(card, 12);
        title = text("Space is full of surprises.", 25, WHITE, true);
        add(card, title, 0, 12);
        factText = text("Connect to Beyond Space to load today’s science fact.", 17, WHITE, false);
        add(card, factText, 0, 18);
        View divider = new View(this);
        divider.setBackgroundColor(0x44FFFFFF);
        add(card, divider, dp(1), 16);
        TextView learnLabel = text("WHAT THIS HELPS YOU LEARN", 11, CYAN, true);
        learnLabel.setLetterSpacing(0.09f);
        card.addView(learnLabel);
        lessonText = text("Explore a related lesson in Beyond Space Academy.", 15, MUTED, false);
        add(card, lessonText, 0, 12);
        Button sourceButton = actionButton("Read the science source", false);
        sourceButton.setOnClickListener(view -> open(sourceUrl));
        add(card, sourceButton, 0, 8);

        dateText = text("", 13, MUTED, false);
        add(page, dateText, 0, 14);

        saveButton = actionButton("Save this fact", true);
        saveButton.setOnClickListener(view -> toggleSaved());
        add(page, saveButton, 0, 10);
        Button academyButton = actionButton("Continue learning in Academy", false);
        academyButton.setOnClickListener(view -> open(academyUrl));
        add(page, academyButton, 0, 22);

        TextView astrologyHeading = text("Daily Astrology", 20, WHITE, true);
        add(page, astrologyHeading, 0, 9);
        TextView astrologyNote = text("A daily cosmic reflection for entertainment and personal reflection.", 14, MUTED, false);
        add(page, astrologyNote, 0, 10);
        LinearLayout astrologyCard = new LinearLayout(this);
        astrologyCard.setOrientation(LinearLayout.VERTICAL);
        astrologyCard.setPadding(dp(20), dp(20), dp(20), dp(20));
        astrologyCard.setBackground(rounded(PANEL, dp(22), 0x36FFFFFF));
        page.addView(astrologyCard, new LinearLayout.LayoutParams(-1, -2));
        Spinner signPicker = new Spinner(this);
        String[] signs = {"Aries", "Taurus", "Gemini", "Cancer", "Leo", "Virgo", "Libra", "Scorpio", "Sagittarius", "Capricorn", "Aquarius", "Pisces"};
        selectedSign = getPreferences(MODE_PRIVATE).getString("selected_zodiac_sign", "Virgo");
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, signs);
        signPicker.setAdapter(adapter);
        for (int i = 0; i < signs.length; i++) if (signs[i].equals(selectedSign)) signPicker.setSelection(i);
        astrologyCard.addView(signPicker);
        astrologyReading = text("Loading today’s reflection…", 17, WHITE, false);
        add(astrologyCard, astrologyReading, 0, 14);
        astrologyDate = text("", 13, MUTED, false);
        astrologyCard.addView(astrologyDate);
        signPicker.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                selectedSign = signs[position];
                getPreferences(MODE_PRIVATE).edit().putString("selected_zodiac_sign", selectedSign).apply();
                renderDailyAstrology();
            }
            @Override public void onNothingSelected(AdapterView<?> parent) { }
        });
        addSpace(page, 22);

        TextView watchHeading = text("Watch Beyond Space", 20, WHITE, true);
        add(page, watchHeading, 0, 9);
        LinearLayout watchRow = new LinearLayout(this);
        watchRow.setOrientation(LinearLayout.HORIZONTAL);
        Button tvButton = actionButton("Beyond Space TV", false);
        tvButton.setOnClickListener(view -> open(spaceTVUrl));
        Button youtubeButton = actionButton("YouTube", false);
        youtubeButton.setOnClickListener(view -> open(youtubeUrl));
        LinearLayout.LayoutParams half = new LinearLayout.LayoutParams(0, dp(50), 1);
        watchRow.addView(tvButton, half);
        LinearLayout.LayoutParams halfRight = new LinearLayout.LayoutParams(0, dp(50), 1);
        halfRight.leftMargin = dp(10);
        watchRow.addView(youtubeButton, halfRight);
        page.addView(watchRow);
        setContentView(scroll);
        refreshSaveButton();
    }

    private void loadDailyAstrology() {
        executor.execute(() -> {
            HttpURLConnection connection = null;
            try {
                connection = (HttpURLConnection) new URL(HOROSCOPE_ENDPOINT).openConnection();
                connection.setRequestMethod("GET");
                connection.setConnectTimeout(10000);
                connection.setReadTimeout(10000);
                connection.setRequestProperty("Accept", "application/json");
                if (connection.getResponseCode() < 200 || connection.getResponseCode() >= 300) throw new IllegalStateException();
                StringBuilder body = new StringBuilder();
                try (BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream(), StandardCharsets.UTF_8))) {
                    String line;
                    while ((line = reader.readLine()) != null) body.append(line);
                }
                JSONObject response = new JSONObject(body.toString());
                JSONObject indexed = new JSONObject();
                JSONArray items = response.optJSONArray("items");
                if (items != null) for (int i = 0; i < items.length(); i++) {
                    JSONObject item = items.optJSONObject(i);
                    if (item != null) indexed.put(item.optString("sign", "").toLowerCase(), item);
                }
                String date = response.optString("date", "");
                mainHandler.post(() -> {
                    astrologyItems = indexed;
                    astrologyDate.setText(date.isEmpty() ? "" : "Daily Astrology · " + date);
                    renderDailyAstrology();
                });
            } catch (Exception exception) {
                mainHandler.post(() -> astrologyReading.setText("Today’s reflection is unavailable. Try again later."));
            } finally {
                if (connection != null) connection.disconnect();
            }
        });
    }

    private void renderDailyAstrology() {
        if (astrologyReading == null) return;
        JSONObject item = astrologyItems.optJSONObject(selectedSign.toLowerCase());
        if (item == null) return;
        JSONArray paragraphs = item.optJSONArray("paragraphs");
        StringBuilder reading = new StringBuilder();
        if (paragraphs != null) for (int i = 0; i < paragraphs.length(); i++) {
            if (reading.length() > 0) reading.append(' ');
            reading.append(paragraphs.optString(i));
        }
        String mood = item.optString("mood", "");
        astrologyReading.setText((mood.isEmpty() ? "" : mood + "\n") + reading);
    }

    private void loadToday() {
        executor.execute(() -> {
            HttpURLConnection connection = null;
            try {
                connection = (HttpURLConnection) new URL(FACT_ENDPOINT).openConnection();
                connection.setRequestMethod("GET");
                connection.setConnectTimeout(10000);
                connection.setReadTimeout(10000);
                connection.setRequestProperty("Accept", "application/json");
                if (connection.getResponseCode() < 200 || connection.getResponseCode() >= 300) {
                    throw new IllegalStateException("The daily fact service is unavailable.");
                }
                StringBuilder body = new StringBuilder();
                try (BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream(), StandardCharsets.UTF_8))) {
                    String line;
                    while ((line = reader.readLine()) != null) body.append(line);
                }
                JSONObject response = new JSONObject(body.toString());
                JSONObject fact = response.optJSONObject("fact");
                if (fact == null) throw new IllegalStateException("Today’s fact is not available yet.");
                JSONObject distribution = fact.optJSONObject("distribution");
                String id = String.valueOf(fact.optInt("number", 0));
                String loadedTitle = fact.optString("title", "Daily Space Fact");
                String loadedFact = fact.optString("fact", "");
                String loadedLesson = fact.optString("lesson", "");
                String loadedSource = fact.optString("source_url", "");
                String loadedAcademy = fact.optString("academy_url", academyUrl);
                String loadedTV = distribution == null ? spaceTVUrl : distribution.optString("space_tv_url", spaceTVUrl);
                String loadedYouTube = distribution == null ? youtubeUrl : distribution.optString("youtube_url", youtubeUrl);
                String date = response.optString("date", "");
                mainHandler.post(() -> {
                    currentFactId = id;
                    title.setText(loadedTitle);
                    factText.setText(loadedFact);
                    lessonText.setText(loadedLesson);
                    sourceUrl = loadedSource;
                    academyUrl = loadedAcademy;
                    spaceTVUrl = loadedTV;
                    youtubeUrl = loadedYouTube;
                    factNumber.setText("FACT " + id + " · DAILY SPACE");
                    dateText.setText(date.isEmpty() ? "" : "Published " + date);
                    statusText.setText("A short science discovery, connected to your next lesson.");
                    retryButton.setVisibility(View.GONE);
                    refreshSaveButton();
                });
            } catch (Exception exception) {
                mainHandler.post(() -> {
                    statusText.setText("Couldn’t load today’s fact. Check your connection and try again.");
                    retryButton.setVisibility(View.VISIBLE);
                });
            } finally {
                if (connection != null) connection.disconnect();
            }
        });
    }

    private void toggleSaved() {
        if (currentFactId.isEmpty()) return;
        String saved = getPreferences(MODE_PRIVATE).getString("saved_fact_ids", "");
        boolean currentlySaved = containsSaved(saved, currentFactId);
        String next = updateSaved(saved, currentFactId, !currentlySaved);
        getPreferences(MODE_PRIVATE).edit().putString("saved_fact_ids", next).apply();
        refreshSaveButton();
    }

    private void refreshSaveButton() {
        if (saveButton == null) return;
        String saved = getPreferences(MODE_PRIVATE).getString("saved_fact_ids", "");
        saveButton.setText(containsSaved(saved, currentFactId) ? "Saved ✓" : "Save this fact");
    }

    private static boolean containsSaved(String saved, String id) {
        if (id == null || id.isEmpty()) return false;
        for (String item : saved.split(",")) if (item.equals(id)) return true;
        return false;
    }

    private static String updateSaved(String saved, String id, boolean add) {
        StringBuilder result = new StringBuilder();
        for (String item : saved.split(",")) {
            if (item.isEmpty() || item.equals(id)) continue;
            if (result.length() > 0) result.append(',');
            result.append(item);
        }
        if (add) {
            if (result.length() > 0) result.append(',');
            result.append(id);
        }
        return result.toString();
    }

    private void open(String url) {
        if (url == null || url.isEmpty()) return;
        try { startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url))); }
        catch (Exception ignored) { }
    }

    private Button actionButton(String label, boolean primary) {
        Button button = new Button(this);
        button.setText(label);
        button.setTextSize(14);
        button.setAllCaps(false);
        button.setTypeface(Typeface.DEFAULT, Typeface.BOLD);
        button.setTextColor(primary ? INK : WHITE);
        button.setMinHeight(dp(50));
        button.setPadding(dp(12), dp(8), dp(12), dp(8));
        button.setBackground(rounded(primary ? CYAN : PANEL, dp(16), primary ? 0 : 0x40FFFFFF));
        return button;
    }

    private TextView text(String value, int size, int color, boolean bold) {
        TextView view = new TextView(this);
        view.setText(value);
        view.setTextColor(color);
        view.setTextSize(size);
        view.setGravity(Gravity.START | Gravity.CENTER_VERTICAL);
        view.setLineSpacing(dp(3), 1.0f);
        view.setTypeface(Typeface.DEFAULT, bold ? Typeface.BOLD : Typeface.NORMAL);
        return view;
    }

    private void add(LinearLayout parent, View view, int height, int bottomMargin) {
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(-1, height <= 0 ? -2 : height);
        params.bottomMargin = dp(bottomMargin);
        parent.addView(view, params);
    }

    private void addSpace(LinearLayout parent, int height) {
        View space = new View(this);
        parent.addView(space, new LinearLayout.LayoutParams(1, dp(height)));
    }

    private GradientDrawable rounded(int color, int radius, int stroke) {
        GradientDrawable drawable = new GradientDrawable();
        drawable.setColor(color);
        drawable.setCornerRadius(radius);
        if (stroke != 0) drawable.setStroke(dp(1), stroke);
        return drawable;
    }

    private int dp(int value) { return Math.round(value * getResources().getDisplayMetrics().density); }

    @Override
    protected void onDestroy() {
        executor.shutdownNow();
        super.onDestroy();
    }
}
