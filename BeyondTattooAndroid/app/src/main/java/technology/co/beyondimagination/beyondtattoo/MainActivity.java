package technology.co.beyondimagination.beyondtattoo;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.content.Intent;
import android.provider.MediaStore;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.webkit.CookieManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.webkit.ValueCallback;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.TextView;
import androidx.core.content.FileProvider;
import java.io.File;
import java.io.IOException;

/** Native Android container for the responsive Beyond Tattoo 1.2 experience. */
public final class MainActivity extends Activity {
    private static final String BASE = "https://beyondimagination.co.technology/beyond-tattoo/";
    private WebView webView;
    private TextView title;
    private Button selectedButton;
    private ValueCallback<Uri[]> fileUploadCallback;
    private Uri cameraUri;
    private static final int FILE_REQUEST = 4102;

    @Override @SuppressLint("SetJavaScriptEnabled")
    public void onCreate(Bundle state) {
        super.onCreate(state);
        CookieManager.getInstance().setAcceptCookie(true);

        LinearLayout root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setBackgroundColor(Color.rgb(11, 7, 18));

        LinearLayout header = new LinearLayout(this);
        header.setOrientation(LinearLayout.VERTICAL);
        header.setPadding(dp(18), dp(12), dp(18), dp(10));
        header.setBackgroundColor(Color.rgb(23, 16, 30));
        title = new TextView(this);
        title.setTextColor(Color.rgb(241, 232, 245));
        title.setTextSize(20);
        title.setTypeface(null, 1);
        header.addView(title);
        TextView subtitle = new TextView(this);
        subtitle.setText("Beyond Tattoo 1.2.1 · mobile studio");
        subtitle.setTextColor(Color.rgb(196, 174, 210));
        subtitle.setTextSize(12);
        header.addView(subtitle);
        root.addView(header);

        webView = new WebView(this);
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setAllowFileAccess(false);
        settings.setMediaPlaybackRequiresUserGesture(true);
        webView.setBackgroundColor(Color.rgb(11, 7, 18));
        webView.setWebChromeClient(new WebChromeClient() {
            @Override public boolean onShowFileChooser(WebView view, ValueCallback<Uri[]> callback, FileChooserParams params) {
                if (fileUploadCallback != null) fileUploadCallback.onReceiveValue(null);
                fileUploadCallback = callback;
                Intent picker = new Intent(Intent.ACTION_OPEN_DOCUMENT);
                picker.addCategory(Intent.CATEGORY_OPENABLE);
                picker.setType("image/*");
                Intent chooser = Intent.createChooser(picker, "Choose a drawing or picture");
                try {
                    File capture = File.createTempFile("violet-trace-", ".jpg", getCacheDir());
                    cameraUri = FileProvider.getUriForFile(MainActivity.this, getPackageName() + ".files", capture);
                    Intent camera = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
                    camera.putExtra(MediaStore.EXTRA_OUTPUT, cameraUri);
                    camera.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION | Intent.FLAG_GRANT_WRITE_URI_PERMISSION);
                    chooser.putExtra(Intent.EXTRA_INITIAL_INTENTS, new Intent[]{camera});
                } catch (IOException error) {
                    cameraUri = null;
                }
                startActivityForResult(chooser, FILE_REQUEST);
                return true;
            }
        });
        webView.setWebViewClient(new WebViewClient() {
            @Override public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                // Keep editor, Needle Bot, and Jaguar requests in this signed-in app session.
                return false;
            }
        });
        root.addView(webView, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, 0, 1));

        LinearLayout navigation = new LinearLayout(this);
        navigation.setGravity(Gravity.CENTER);
        navigation.setPadding(dp(5), dp(7), dp(5), dp(9));
        navigation.setBackgroundColor(Color.rgb(23, 16, 30));
        addNav(navigation, getString(R.string.home), BASE, "Today");
        addNav(navigation, getString(R.string.library), BASE + "stencils.php", "Library");
        addNav(navigation, getString(R.string.create), BASE + "tattoo-generator.php", "Create");
        addNav(navigation, getString(R.string.needle_bot), BASE + "needle-bot.php?embed=1", "Needle Bot");
        addNav(navigation, getString(R.string.profile), BASE + "profile.php", "Profile");
        root.addView(navigation);
        setContentView(root);
        select("Today", BASE, null);
    }

    @Override protected void onActivityResult(int requestCode, int resultCode, android.content.Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode != FILE_REQUEST || fileUploadCallback == null) return;
        Uri selected = resultCode == RESULT_OK ? (data != null && data.getData() != null ? data.getData() : cameraUri) : null;
        Uri[] result = selected == null ? null : new Uri[]{selected};
        fileUploadCallback.onReceiveValue(result);
        fileUploadCallback = null;
        cameraUri = null;
    }

    private void addNav(LinearLayout navigation, String label, String url, String pageTitle) {
        Button button = new Button(this);
        button.setText(label);
        button.setTextSize(11);
        button.setAllCaps(false);
        button.setTextColor(Color.rgb(241, 232, 245));
        button.setBackgroundColor(Color.TRANSPARENT);
        button.setOnClickListener(v -> select(pageTitle, url, button));
        navigation.addView(button, new LinearLayout.LayoutParams(0, dp(48), 1));
    }

    private void select(String pageTitle, String url, Button button) {
        title.setText(pageTitle);
        if (selectedButton != null) selectedButton.setTextColor(Color.rgb(241, 232, 245));
        selectedButton = button;
        if (selectedButton != null) selectedButton.setTextColor(Color.rgb(188, 126, 255));
        webView.loadUrl(url);
    }

    @Override public void onBackPressed() {
        if (webView != null && webView.canGoBack()) webView.goBack(); else super.onBackPressed();
    }

    private int dp(int value) { return Math.round(value * getResources().getDisplayMetrics().density); }
}
