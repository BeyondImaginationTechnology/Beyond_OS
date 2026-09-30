package technology.co.beyondimagination.beyondhealth;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.os.Bundle;
import android.os.CountDownTimer;
import android.view.Gravity;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.HorizontalScrollView;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.SeekBar;
import android.widget.TextView;
import android.widget.Toast;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import java.text.DateFormat;
import java.util.Date;
import java.util.Locale;

/** Native, offline v0.0.1 daily wellbeing companion. */
public final class MainActivity extends Activity {
    private static final int PAPER = Color.rgb(244, 248, 243);
    private static final int INK = Color.rgb(23, 53, 43);
    private static final int MUTED = Color.rgb(104, 128, 117);
    private static final int GREEN = Color.rgb(22, 122, 89);
    private static final int MINT = Color.rgb(223, 243, 229);
    private static final String[] MOODS = {"Grounded", "Tired", "Stretched", "Hopeful", "Heavy"};
    private static final String[] TABS = {"Today", "Journal", "Practices", "Insights", "Settings"};
    private static final String[] PRACTICES = {"Box breathing", "Reset your body", "Name the good"};
    private static final String[] PRACTICE_DETAILS = {
        "Breathe in, hold, breathe out, and hold for four counts each.",
        "Look away from the screen, roll your shoulders, and walk if you can.",
        "Write one sentence about something steady, kind, or possible today."
    };
    private static final int[] PRACTICE_SECONDS = {60, 120, 60};
    private SharedPreferences preferences;
    private JSONArray checkIns = new JSONArray();
    private JSONArray notes = new JSONArray();
    private LinearLayout content;
    private int selectedTab = 0;
    private String selectedMood;
    private int energy = 3, stress = 3, sleep = 3;
    private int activePractice = -1, remainingSeconds;
    private CountDownTimer timer;
    private boolean timerRunning;
    private TextView timerReadout;

    @Override public void onCreate(Bundle state) {
        super.onCreate(state);
        preferences = getSharedPreferences("wellbeing-v0.0.1", MODE_PRIVATE);
        try { checkIns = new JSONArray(preferences.getString("checkIns", "[]")); } catch (JSONException ignored) { checkIns = new JSONArray(); }
        try { notes = new JSONArray(preferences.getString("notes", "[]")); } catch (JSONException ignored) { notes = new JSONArray(); }
        render();
    }

    private int dp(float value) { return Math.round(getResources().getDisplayMetrics().density * value); }
    private GradientDrawable shape(int color, int radius) {
        GradientDrawable result = new GradientDrawable();
        result.setColor(color);
        result.setCornerRadius(dp(radius));
        return result;
    }
    private LinearLayout column() {
        LinearLayout view = new LinearLayout(this);
        view.setOrientation(LinearLayout.VERTICAL);
        return view;
    }
    private LinearLayout row() {
        LinearLayout view = new LinearLayout(this);
        view.setOrientation(LinearLayout.HORIZONTAL);
        view.setGravity(Gravity.CENTER_VERTICAL);
        return view;
    }
    private TextView text(String value, int size, int color, boolean bold) {
        TextView view = new TextView(this);
        view.setText(value);
        view.setTextSize(size);
        view.setTextColor(color);
        if (bold) view.setTypeface(Typeface.DEFAULT, Typeface.BOLD);
        return view;
    }
    private void gap(LinearLayout parent, int height) {
        View gap = new View(this);
        parent.addView(gap, new LinearLayout.LayoutParams(1, dp(height)));
    }
    private LinearLayout card() {
        LinearLayout view = column();
        view.setPadding(dp(18), dp(18), dp(18), dp(18));
        view.setBackground(shape(Color.WHITE, 22));
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(-1, -2);
        params.bottomMargin = dp(15);
        content.addView(view, params);
        return view;
    }
    private Button button(String label, boolean primary, Runnable action) {
        Button view = new Button(this);
        view.setText(label);
        view.setAllCaps(false);
        view.setTextSize(14);
        view.setTypeface(Typeface.DEFAULT, Typeface.BOLD);
        view.setTextColor(primary ? Color.WHITE : GREEN);
        view.setBackground(shape(primary ? GREEN : MINT, 14));
        view.setPadding(dp(14), dp(8), dp(14), dp(8));
        view.setOnClickListener(v -> action.run());
        return view;
    }
    private void persist() {
        boolean saved = preferences.edit()
            .putString("checkIns", checkIns.toString())
            .putString("notes", notes.toString())
            .commit();
        if (!saved) Toast.makeText(this, "Could not save on this device.", Toast.LENGTH_LONG).show();
    }
    private void render() {
        LinearLayout root = column();
        root.setBackgroundColor(PAPER);
        TextView brand = text("♥  Beyond Health", 19, GREEN, true);
        brand.setPadding(dp(20), dp(20), dp(20), dp(14));
        root.addView(brand);

        ScrollView scroll = new ScrollView(this);
        scroll.setFillViewport(true);
        content = column();
        content.setPadding(dp(18), dp(8), dp(18), dp(20));
        scroll.addView(content);
        root.addView(scroll, new LinearLayout.LayoutParams(-1, 0, 1));

        switch (selectedTab) {
            case 0: today(); break;
            case 1: journal(); break;
            case 2: practices(); break;
            case 3: insights(); break;
            default: settings(); break;
        }

        HorizontalScrollView navScroll = new HorizontalScrollView(this);
        navScroll.setHorizontalScrollBarEnabled(false);
        navScroll.setBackgroundColor(Color.WHITE);
        LinearLayout nav = row();
        nav.setPadding(dp(8), dp(7), dp(8), dp(7));
        for (int i = 0; i < TABS.length; i++) {
            final int target = i;
            Button tab = button(TABS[i], false, () -> {
                stopTimer();
                activePractice = -1;
                selectedTab = target;
                render();
            });
            tab.setBackground(shape(selectedTab == i ? MINT : Color.WHITE, 12));
            tab.setTextColor(selectedTab == i ? GREEN : MUTED);
            nav.addView(tab);
        }
        navScroll.addView(nav);
        root.addView(navScroll);
        setContentView(root);
    }
    private void heading(String kicker, String title, String subtitle) {
        content.addView(text(kicker.toUpperCase(Locale.getDefault()), 11, GREEN, true));
        gap(content, 6);
        content.addView(text(title, 30, INK, true));
        gap(content, 5);
        content.addView(text(subtitle, 14, MUTED, false));
        gap(content, 22);
    }
    private void today() {
        String date = DateFormat.getDateInstance(DateFormat.FULL).format(new Date());
        heading(date, "How are you arriving?", "Notice where you are. One honest signal is enough.");
        LinearLayout check = card();
        check.addView(text("Daily check-in", 19, INK, true));
        gap(check, 8);
        check.addView(text("Right now, I feel", 14, MUTED, false));
        gap(check, 10);
        HorizontalScrollView moodScroll = new HorizontalScrollView(this);
        moodScroll.setHorizontalScrollBarEnabled(false);
        LinearLayout moodRow = row();
        for (String item : MOODS) {
            Button chip = button(item, item.equals(selectedMood), () -> { selectedMood = item; render(); });
            LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(-2, dp(45));
            params.rightMargin = dp(7);
            moodRow.addView(chip, params);
        }
        moodScroll.addView(moodRow);
        check.addView(moodScroll);
        rating(check, "Energy", energy, value -> energy = value);
        rating(check, "Stress", stress, value -> stress = value);
        rating(check, "Sleep", sleep, value -> sleep = value);
        gap(check, 12);
        check.addView(button("Save today’s check-in", true, () -> {
            if (selectedMood == null) {
                Toast.makeText(this, "Choose a feeling first.", Toast.LENGTH_SHORT).show();
                return;
            }
            JSONObject item = new JSONObject();
            try {
                item.put("date", System.currentTimeMillis());
                item.put("mood", selectedMood);
                item.put("energy", energy);
                item.put("stress", stress);
                item.put("sleep", sleep);
                JSONArray updated = new JSONArray();
                updated.put(item);
                for (int i = 0; i < checkIns.length(); i++) updated.put(checkIns.optJSONObject(i));
                checkIns = updated;
                persist();
                Toast.makeText(this, "Check-in saved on this device.", Toast.LENGTH_SHORT).show();
                render();
            } catch (JSONException error) {
                Toast.makeText(this, "Could not save this check-in.", Toast.LENGTH_SHORT).show();
            }
        }));
        String mood = selectedMood;
        if (mood == null && checkIns.length() > 0) mood = checkIns.optJSONObject(0).optString("mood", "");
        LinearLayout next = card();
        next.addView(text("A gentle next step", 12, GREEN, true));
        gap(next, 7);
        next.addView(text(suggestion(mood), 19, INK, true));
        gap(next, 14);
        final int practice = practiceFor(mood);
        next.addView(button(PRACTICES[practice] + " · " + (PRACTICE_SECONDS[practice] / 60) + " min", false, () -> openPractice(practice)));
    }
    private interface RatingChanged { void accept(int value); }
    private void rating(LinearLayout parent, String title, int current, RatingChanged onChange) {
        gap(parent, 12);
        LinearLayout line = row();
        TextView name = text(title, 14, INK, true);
        line.addView(name, new LinearLayout.LayoutParams(0, -2, 1));
        TextView amount = text(current + " / 5", 14, MUTED, false);
        line.addView(amount);
        parent.addView(line);
        SeekBar slider = new SeekBar(this);
        slider.setMax(4);
        slider.setProgress(current - 1);
        slider.setOnSeekBarChangeListener(new SeekBar.OnSeekBarChangeListener() {
            public void onProgressChanged(SeekBar bar, int value, boolean fromUser) {
                amount.setText((value + 1) + " / 5");
                onChange.accept(value + 1);
            }
            public void onStartTrackingTouch(SeekBar bar) {}
            public void onStopTrackingTouch(SeekBar bar) {}
        });
        parent.addView(slider);
    }
    private String suggestion(String mood) {
        if ("Grounded".equals(mood)) return "Keep the rhythm. Notice one good thing.";
        if ("Tired".equals(mood)) return "Make the next thing smaller. Try a brief movement reset.";
        if ("Stretched".equals(mood)) return "Pause before you push. Take one quiet breath.";
        if ("Hopeful".equals(mood)) return "Build on what feels possible. Capture one thought.";
        if ("Heavy".equals(mood)) return "Be gentle with the next hour. One step is enough.";
        return "Start with a pause. Choose a feeling to find your next step.";
    }
    private int practiceFor(String mood) {
        if ("Tired".equals(mood)) return 1;
        if ("Grounded".equals(mood) || "Hopeful".equals(mood)) return 2;
        return 0;
    }
    private void journal() {
        heading("A private record", "Keep what mattered.", "A thought, a win, a worry, or a small thing that helped.");
        LinearLayout editor = card();
        editor.addView(text("New note", 19, INK, true));
        gap(editor, 10);
        EditText input = new EditText(this);
        input.setHint("Write a private note…");
        input.setGravity(Gravity.TOP);
        input.setMinLines(5);
        input.setTextSize(15);
        input.setPadding(dp(12), dp(12), dp(12), dp(12));
        input.setBackground(shape(PAPER, 12));
        editor.addView(input, new LinearLayout.LayoutParams(-1, dp(150)));
        gap(editor, 12);
        editor.addView(button("Save note", true, () -> {
            String value = input.getText().toString().trim();
            if (value.isEmpty()) return;
            JSONObject item = new JSONObject();
            try {
                item.put("date", System.currentTimeMillis());
                item.put("text", value);
                JSONArray updated = new JSONArray();
                updated.put(item);
                for (int i = 0; i < notes.length(); i++) updated.put(notes.optJSONObject(i));
                notes = updated;
                persist();
                render();
            } catch (JSONException error) {
                Toast.makeText(this, "Could not save this note.", Toast.LENGTH_SHORT).show();
            }
        }));
        LinearLayout recent = card();
        recent.addView(text("Recent notes", 19, INK, true));
        if (notes.length() == 0) {
            gap(recent, 8);
            recent.addView(text("Your private notes will appear here.", 14, MUTED, false));
        }
        for (int i = 0; i < notes.length(); i++) {
            JSONObject item = notes.optJSONObject(i);
            if (item == null) continue;
            final int index = i;
            gap(recent, 14);
            recent.addView(text(item.optString("text"), 15, INK, false));
            recent.addView(text(formatDate(item.optLong("date")), 12, MUTED, false));
            recent.addView(button("Delete note", false, () -> new AlertDialog.Builder(this)
                .setTitle("Delete this note?")
                .setNegativeButton("Cancel", null)
                .setPositiveButton("Delete", (d, which) -> {
                    JSONArray updated = new JSONArray();
                    for (int j = 0; j < notes.length(); j++) if (j != index) updated.put(notes.optJSONObject(j));
                    notes = updated;
                    persist();
                    render();
                }).show()));
        }
    }
    private void practices() {
        heading("Meet the moment", "Short enough to begin.", "Stop whenever you need.");
        if (activePractice >= 0) {
            final int index = activePractice;
            LinearLayout session = card();
            session.addView(text(PRACTICES[index], 22, INK, true));
            gap(session, 8);
            session.addView(text(PRACTICE_DETAILS[index], 15, MUTED, false));
            gap(session, 20);
            timerReadout = text(timerLabel(), 48, GREEN, true);
            timerReadout.setGravity(Gravity.CENTER);
            session.addView(timerReadout);
            gap(session, 15);
            session.addView(button(timerRunning ? "Pause" : "Start", true, () -> {
                if (timerRunning) stopTimer();
                else startTimer();
                render();
            }));
            gap(session, 8);
            session.addView(button("Reset", false, () -> {
                stopTimer();
                remainingSeconds = PRACTICE_SECONDS[index];
                render();
            }));
            return;
        }
        for (int i = 0; i < PRACTICES.length; i++) {
            final int index = i;
            LinearLayout item = card();
            item.addView(text(PRACTICES[i], 20, INK, true));
            gap(item, 7);
            item.addView(text(PRACTICE_DETAILS[i], 14, MUTED, false));
            gap(item, 14);
            item.addView(button("Begin · " + (PRACTICE_SECONDS[i] / 60) + " min", false, () -> openPractice(index)));
        }
    }
    private void openPractice(int index) {
        stopTimer();
        activePractice = index;
        remainingSeconds = PRACTICE_SECONDS[index];
        selectedTab = 2;
        render();
    }
    private String timerLabel() {
        return String.format(Locale.getDefault(), "%d:%02d", remainingSeconds / 60, remainingSeconds % 60);
    }
    private void startTimer() {
        if (activePractice < 0) return;
        if (remainingSeconds <= 0) remainingSeconds = PRACTICE_SECONDS[activePractice];
        timerRunning = true;
        timer = new CountDownTimer(remainingSeconds * 1000L, 1000L) {
            public void onTick(long millis) {
                remainingSeconds = Math.max(0, (int)Math.ceil(millis / 1000.0));
                if (timerReadout != null) timerReadout.setText(timerLabel());
            }
            public void onFinish() {
                timerRunning = false;
                remainingSeconds = 0;
                timer = null;
                Toast.makeText(MainActivity.this, "Practice complete. Notice how you feel.", Toast.LENGTH_LONG).show();
                render();
            }
        }.start();
    }
    private void stopTimer() {
        if (timer != null) { timer.cancel(); timer = null; }
        timerRunning = false;
    }
    private void insights() {
        heading("Your rhythm, in context", "Insights", "Personal reflections, never a diagnosis.");
        LinearLayout weekly = card();
        weekly.addView(text("This week", 12, GREEN, true));
        int count = 0, totalEnergy = 0, totalStress = 0, totalSleep = 0;
        long weekAgo = System.currentTimeMillis() - 7L * 24 * 60 * 60 * 1000;
        for (int i = 0; i < checkIns.length(); i++) {
            JSONObject item = checkIns.optJSONObject(i);
            if (item != null && item.optLong("date") >= weekAgo) {
                count++;
                totalEnergy += item.optInt("energy");
                totalStress += item.optInt("stress");
                totalSleep += item.optInt("sleep");
            }
        }
        gap(weekly, 9);
        if (count == 0) weekly.addView(text("Check in a few times to see your own patterns.", 15, MUTED, false));
        else {
            weekly.addView(text(count + " check-in" + (count == 1 ? "" : "s") + " this week", 20, INK, true));
            weekly.addView(text("Average energy: " + Math.round((float)totalEnergy / count) + " / 5", 15, INK, false));
            weekly.addView(text("Average stress: " + Math.round((float)totalStress / count) + " / 5", 15, INK, false));
            weekly.addView(text("Average sleep: " + Math.round((float)totalSleep / count) + " / 5", 15, INK, false));
        }
        LinearLayout recent = card();
        recent.addView(text("Recent check-ins", 19, INK, true));
        if (checkIns.length() == 0) recent.addView(text("No check-ins yet. Begin with Today.", 14, MUTED, false));
        for (int i = 0; i < Math.min(checkIns.length(), 12); i++) {
            JSONObject item = checkIns.optJSONObject(i);
            if (item == null) continue;
            gap(recent, 12);
            recent.addView(text(item.optString("mood") + " · " + formatDate(item.optLong("date")), 15, INK, true));
            recent.addView(text("Energy " + item.optInt("energy") + "/5 · Stress " + item.optInt("stress") + "/5 · Sleep " + item.optInt("sleep") + "/5", 13, MUTED, false));
        }
    }
    private String formatDate(long timestamp) {
        return DateFormat.getDateTimeInstance(DateFormat.MEDIUM, DateFormat.SHORT).format(new Date(timestamp));
    }
    private void settings() {
        heading("Your space", "Settings", "Private on this device.");
        LinearLayout privacy = card();
        privacy.addView(text("Local data", 19, INK, true));
        gap(privacy, 8);
        privacy.addView(text("Check-ins and notes stay inside this app on this phone. v0.0.1 does not sync with the web app or another phone.", 15, MUTED, false));
        LinearLayout data = card();
        data.addView(text("Your data", 19, INK, true));
        gap(data, 8);
        data.addView(text(checkIns.length() + " check-ins · " + notes.length() + " notes", 15, MUTED, false));
        gap(data, 14);
        data.addView(button("Delete all local data", false, () -> new AlertDialog.Builder(this)
            .setTitle("Delete all Beyond Health data on this device?")
            .setMessage("This removes your check-ins and notes from this phone.")
            .setNegativeButton("Cancel", null)
            .setPositiveButton("Delete all data", (dialog, which) -> {
                checkIns = new JSONArray();
                notes = new JSONArray();
                persist();
                render();
            }).show()));
    }
    @Override protected void onStop() {
        super.onStop();
        stopTimer();
    }
}
