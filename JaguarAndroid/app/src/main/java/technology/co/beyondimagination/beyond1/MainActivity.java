package technology.co.beyondimagination.beyond1;

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
import android.view.Gravity;
import android.view.View;
import android.view.Window;
import android.view.inputmethod.InputMethodManager;
import android.content.Context;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;

/** Native Android v0.5.1 client for Jaguar Explain. */
public final class MainActivity extends Activity {
    private static final String ENDPOINT = "https://beyondimagination.co.technology/ai/api/chat.php";
    private static final int INK = Color.rgb(8, 5, 15);
    private static final int PANEL = Color.rgb(23, 16, 39);
    private static final int VIOLET = Color.rgb(179, 92, 255);
    private static final int MAGENTA = Color.rgb(242, 100, 207);
    private static final int MINT = Color.rgb(131, 239, 168);
    private static final int WHITE = Color.rgb(250, 247, 255);
    private static final int MUTED = Color.rgb(174, 165, 188);

    private final Handler main = new Handler(Looper.getMainLooper());
    private final List<Message> messages = new ArrayList<>();
    private JaguarAuth auth;
    private SharedPreferences preferences;
    private LinearLayout transcript;
    private ScrollView scroll;
    private TextView status;
    private Button signInButton;
    private Button languageButton;
    private Button sendButton;
    private EditText prompt;
    private boolean reviewerDemo;
    private boolean sending;
    private String language = "en";

    @Override public void onCreate(Bundle state) {
        super.onCreate(state);
        Window window = getWindow();
        window.setStatusBarColor(INK);
        window.setNavigationBarColor(INK);
        preferences = getSharedPreferences("beyond1_conversation", MODE_PRIVATE);
        auth = new JaguarAuth(this, this::refreshAccount);
        loadConversation();
        buildScreen();
        auth.complete(getIntent().getData());
    }

    @Override public void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        auth.complete(intent.getData());
    }

    private void buildScreen() {
        LinearLayout root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setBackgroundColor(INK);

        LinearLayout header = row();
        header.setPadding(dp(20), dp(16), dp(20), dp(12));
        header.setGravity(Gravity.CENTER_VERTICAL);
        TextView mark = text("◉", 28, MAGENTA, true);
        header.addView(mark, new LinearLayout.LayoutParams(dp(42), dp(42)));
        LinearLayout identity = column();
        identity.setPadding(dp(10), 0, dp(8), 0);
        identity.addView(text("BEYOND-1", 18, WHITE, true));
        TextView version = text("JAGUAR · v0.5.1", 11, MINT, true);
        version.setLetterSpacing(0.12f);
        identity.addView(version);
        header.addView(identity, new LinearLayout.LayoutParams(0, -2, 1));
        Button fresh = smallButton("New");
        fresh.setOnClickListener(view -> { messages.clear(); saveConversation(); renderMessages(); });
        header.addView(fresh);
        root.addView(header);

        LinearLayout controls = row();
        controls.setPadding(dp(20), 0, dp(20), dp(10));
        languageButton = smallButton("English");
        languageButton.setOnClickListener(view -> cycleLanguage());
        controls.addView(languageButton);
        signInButton = smallButton("Beyond ID");
        signInButton.setOnClickListener(view -> {
            if (auth.signedIn()) auth.signOut(); else auth.signIn();
        });
        LinearLayout.LayoutParams accountParams = new LinearLayout.LayoutParams(-2, dp(40));
        accountParams.leftMargin = dp(9);
        controls.addView(signInButton, accountParams);
        Button demo = smallButton("Reviewer demo");
        demo.setOnClickListener(view -> { reviewerDemo = !reviewerDemo; refreshAccount(); });
        LinearLayout.LayoutParams demoParams = new LinearLayout.LayoutParams(-2, dp(40));
        demoParams.leftMargin = dp(9);
        controls.addView(demo, demoParams);
        root.addView(controls);

        status = text("", 12, MUTED, false);
        status.setPadding(dp(20), 0, dp(20), dp(10));
        root.addView(status);

        scroll = new ScrollView(this);
        scroll.setFillViewport(true);
        transcript = column();
        transcript.setPadding(dp(20), dp(10), dp(20), dp(18));
        scroll.addView(transcript);
        root.addView(scroll, new LinearLayout.LayoutParams(-1, 0, 1));

        LinearLayout composer = row();
        composer.setPadding(dp(16), dp(10), dp(16), dp(18));
        composer.setGravity(Gravity.BOTTOM);
        composer.setBackgroundColor(PANEL);
        prompt = new EditText(this);
        prompt.setHint("Message Beyond-1…");
        prompt.setHintTextColor(Color.rgb(129, 120, 142));
        prompt.setTextColor(WHITE);
        prompt.setTextSize(16);
        prompt.setMinLines(1);
        prompt.setMaxLines(5);
        prompt.setBackground(round(Color.rgb(17, 11, 29), dp(18), 0x50B35CFF));
        prompt.setPadding(dp(16), dp(8), dp(12), dp(8));
        composer.addView(prompt, new LinearLayout.LayoutParams(0, -2, 1));
        sendButton = actionButton("Send", true);
        sendButton.setOnClickListener(view -> send());
        LinearLayout.LayoutParams sendParams = new LinearLayout.LayoutParams(dp(76), dp(52));
        sendParams.leftMargin = dp(10);
        composer.addView(sendButton, sendParams);
        root.addView(composer);

        setContentView(root);
        refreshAccount();
        renderMessages();
    }

    private void refreshAccount() {
        if (signInButton == null) return;
        signInButton.setText(auth.signedIn() ? "Sign out" : "Beyond ID");
        String mode = reviewerDemo ? "Reviewer demo runs locally." : auth.signedIn() ? "Secure Beyond ID session connected." : auth.status();
        status.setText(mode);
    }

    private void cycleLanguage() {
        language = language.equals("en") ? "fr" : language.equals("fr") ? "es" : "en";
        languageButton.setText(language.equals("en") ? "English" : language.equals("fr") ? "Français" : "Español");
    }

    private void send() {
        String text = prompt.getText().toString().trim();
        if (text.isEmpty() || sending) return;
        if (!reviewerDemo && !auth.signedIn()) {
            status.setText("Sign in with Beyond ID or choose Reviewer demo before sending.");
            return;
        }
        prompt.setText("");
        messages.add(new Message("user", text));
        saveConversation();
        renderMessages();
        sending = true;
        sendButton.setEnabled(false);
        status.setText(reviewerDemo ? "Preparing a local demo…" : "Jaguar is thinking…");
        hideKeyboard();
        if (reviewerDemo) {
            main.postDelayed(() -> finish("Demo response for: \"" + text + "\"\n\nBeyond-1 can explain ideas, shape plans, and break down difficult topics in plain language. This reviewer demo runs locally and does not send your message to a server."), 350);
            return;
        }
        new Thread(() -> {
            try { finish(requestChat()); }
            catch (Exception exception) { fail(exception.getMessage()); }
        }).start();
    }

    private String requestChat() throws Exception {
        JSONObject body = new JSONObject();
        body.put("mode", "core");
        body.put("language", language);
        JSONArray history = new JSONArray();
        int start = Math.max(0, messages.size() - 24);
        for (int i = start; i < messages.size(); i++) {
            Message message = messages.get(i);
            history.put(new JSONObject().put("role", message.role).put("content", message.content));
        }
        body.put("messages", history);
        HttpURLConnection connection = (HttpURLConnection) new URL(ENDPOINT).openConnection();
        connection.setConnectTimeout(15000);
        connection.setReadTimeout(120000);
        connection.setRequestMethod("POST");
        connection.setRequestProperty("Accept", "application/json");
        connection.setRequestProperty("Content-Type", "application/json");
        connection.setRequestProperty("Authorization", "Bearer " + auth.token());
        connection.setRequestProperty("X-Jaguar-Client", "android");
        connection.setDoOutput(true);
        connection.getOutputStream().write(body.toString().getBytes(StandardCharsets.UTF_8));
        int code = connection.getResponseCode();
        InputStream stream = code < 400 ? connection.getInputStream() : connection.getErrorStream();
        ByteArrayOutputStream output = new ByteArrayOutputStream();
        try (InputStream input = stream) {
            byte[] buffer = new byte[4096];
            for (int read; (read = input.read(buffer)) != -1;) output.write(buffer, 0, read);
        }
        connection.disconnect();
        JSONObject response = new JSONObject(output.toString(StandardCharsets.UTF_8));
        if (code == 401) { main.post(auth::signOut); throw new IllegalStateException("Your Beyond ID session expired. Sign in again."); }
        if (code < 200 || code >= 300) throw new IllegalStateException(response.optString("error", "Beyond-1 is temporarily unavailable."));
        String reply = response.optString("message", "");
        if (reply.isEmpty()) throw new IllegalStateException("Beyond-1 returned an unreadable response.");
        return reply;
    }

    private void finish(String reply) {
        main.post(() -> {
            messages.add(new Message("assistant", reply));
            saveConversation();
            sending = false;
            sendButton.setEnabled(true);
            refreshAccount();
            renderMessages();
        });
    }

    private void fail(String problem) {
        main.post(() -> {
            sending = false;
            sendButton.setEnabled(true);
            status.setText(problem == null || problem.isEmpty() ? "Beyond-1 could not complete that request." : problem);
        });
    }

    private void renderMessages() {
        transcript.removeAllViews();
        if (messages.isEmpty()) {
            TextView welcome = text("Where will we go beyond?", 30, WHITE, true);
            transcript.addView(welcome);
            TextView copy = text("Explain ideas, shape plans, and learn in plain language.", 17, MUTED, false);
            add(transcript, copy, 0, 18);
            for (String suggestion : new String[]{"Explain how a token works", "Help me plan a feature", "Teach me a difficult concept"}) {
                Button button = actionButton(suggestion, false);
                button.setOnClickListener(view -> { prompt.setText(((Button) view).getText()); prompt.requestFocus(); });
                add(transcript, button, dp(48), 10);
            }
        } else {
            for (Message message : messages) {
                TextView label = text(message.role.equals("user") ? "YOU" : "BEYOND-1", 11, message.role.equals("user") ? MINT : MAGENTA, true);
                label.setLetterSpacing(0.1f);
                add(transcript, label, 0, 4);
                TextView bubble = text(message.content, 17, WHITE, false);
                bubble.setBackground(round(message.role.equals("user") ? Color.rgb(34, 22, 53) : PANEL, dp(16), 0x30FFFFFF));
                bubble.setPadding(dp(15), dp(12), dp(15), dp(12));
                add(transcript, bubble, 0, 18);
            }
        }
        scroll.post(() -> scroll.fullScroll(View.FOCUS_DOWN));
    }

    private void saveConversation() {
        try {
            JSONArray saved = new JSONArray();
            for (Message message : messages) saved.put(new JSONObject().put("role", message.role).put("content", message.content));
            preferences.edit().putString("messages", saved.toString()).apply();
        } catch (Exception ignored) { }
    }

    private void loadConversation() {
        try {
            JSONArray saved = new JSONArray(preferences.getString("messages", "[]"));
            for (int i = 0; i < saved.length(); i++) {
                JSONObject row = saved.getJSONObject(i);
                String role = row.optString("role");
                String content = row.optString("content");
                if ((role.equals("user") || role.equals("assistant")) && !content.isEmpty()) messages.add(new Message(role, content));
            }
        } catch (Exception ignored) { }
    }

    private LinearLayout column() { LinearLayout layout = new LinearLayout(this); layout.setOrientation(LinearLayout.VERTICAL); return layout; }
    private LinearLayout row() { LinearLayout layout = new LinearLayout(this); layout.setOrientation(LinearLayout.HORIZONTAL); return layout; }
    private TextView text(String value, int size, int color, boolean bold) { TextView view = new TextView(this); view.setText(value); view.setTextSize(size); view.setTextColor(color); view.setGravity(Gravity.CENTER_VERTICAL); view.setLineSpacing(dp(3), 1f); view.setTypeface(Typeface.DEFAULT, bold ? Typeface.BOLD : Typeface.NORMAL); return view; }
    private Button smallButton(String label) { Button button = new Button(this); button.setText(label); button.setTextSize(12); button.setAllCaps(false); button.setTextColor(WHITE); button.setTypeface(Typeface.DEFAULT, Typeface.BOLD); button.setPadding(dp(10), 0, dp(10), 0); button.setBackground(round(PANEL, dp(14), 0x45B35CFF)); return button; }
    private Button actionButton(String label, boolean primary) { Button button = new Button(this); button.setText(label); button.setTextSize(14); button.setAllCaps(false); button.setTypeface(Typeface.DEFAULT, Typeface.BOLD); button.setTextColor(primary ? INK : WHITE); button.setBackground(round(primary ? VIOLET : PANEL, dp(15), primary ? 0 : 0x42FFFFFF)); return button; }
    private GradientDrawable round(int color, int radius, int stroke) { GradientDrawable shape = new GradientDrawable(); shape.setColor(color); shape.setCornerRadius(radius); if (stroke != 0) shape.setStroke(dp(1), stroke); return shape; }
    private void add(LinearLayout parent, View view, int height, int bottomMargin) { LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(-1, height <= 0 ? -2 : height); params.bottomMargin = dp(bottomMargin); parent.addView(view, params); }
    private int dp(int value) { return Math.round(value * getResources().getDisplayMetrics().density); }
    private void hideKeyboard() { ((InputMethodManager) getSystemService(Context.INPUT_METHOD_SERVICE)).hideSoftInputFromWindow(prompt.getWindowToken(), 0); }

    private static final class Message { final String role; final String content; Message(String role, String content) { this.role = role; this.content = content; } }
}
