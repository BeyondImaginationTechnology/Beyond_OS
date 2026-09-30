package technology.co.beyondimagination.beyondtv;

import android.app.Activity;
import android.content.Intent;
import android.graphics.Bitmap;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkCapabilities;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.webkit.JavaScriptReplyProxy;
import androidx.webkit.WebViewCompat;
import androidx.webkit.WebViewFeature;

import com.unity3d.ads.IUnityAdsInitializationListener;
import com.unity3d.ads.IUnityAdsLoadListener;
import com.unity3d.ads.IUnityAdsShowListener;
import com.unity3d.ads.UnityAds;
import com.unity3d.ads.UnityAdsShowOptions;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.Collections;

public final class MainActivity extends Activity implements IUnityAdsInitializationListener {
    private static final String HOME_URL = "https://beyondimagination.co.technology/beyond-tv/";
    private static final String ALLOWED_HOST = "beyondimagination.co.technology";
    private static final String UNITY_GAME_ID = "6197579";
    private static final String INTERSTITIAL_ID = "Interstitial_Android";
    private static final String TAG = "BeyondTVAds";

    private WebView webView;
    private ProgressBar progress;
    private View errorPanel;
    private TextView errorMessage;
    private final Handler adHandler = new Handler(Looper.getMainLooper());
    private volatile boolean adsReady;
    private boolean adShowing;
    private int adRequestSerial;
    private String adRequestId;
    private JavaScriptReplyProxy adReply;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        webView = findViewById(R.id.web_view);
        progress = findViewById(R.id.progress);
        errorPanel = findViewById(R.id.error_panel);
        errorMessage = findViewById(R.id.error_message);
        Button retry = findViewById(R.id.retry_button);
        retry.setOnClickListener(view -> loadHome());

        configureWebView();
        UnityAds.initialize(getApplicationContext(), UNITY_GAME_ID, BuildConfig.DEBUG, this);
        if (savedInstanceState == null) {
            loadIntent(getIntent());
        } else {
            webView.restoreState(savedInstanceState);
        }
    }

    private void configureWebView() {
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setAllowFileAccess(false);
        settings.setAllowContentAccess(false);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        settings.setUserAgentString(settings.getUserAgentString() + " BeyondTV-Android/" + BuildConfig.VERSION_NAME + " (" + BuildConfig.VERSION_CODE + ")");

        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, true);
        WebView.setWebContentsDebuggingEnabled(BuildConfig.DEBUG);

        if (WebViewFeature.isFeatureSupported(WebViewFeature.WEB_MESSAGE_LISTENER)) {
            WebViewCompat.addWebMessageListener(webView, "BeyondTVNativeAds",
                    Collections.singleton("https://" + ALLOWED_HOST),
                    (view, message, sourceOrigin, isMainFrame, replyProxy) -> {
                        if (!isMainFrame || view != webView
                                || !"https".equalsIgnoreCase(sourceOrigin.getScheme())
                                || !ALLOWED_HOST.equalsIgnoreCase(sourceOrigin.getHost())
                                || view.getUrl() == null
                                || !view.getUrl().startsWith("https://" + ALLOWED_HOST + "/beyond-tv/")) {
                            return;
                        }
                        try {
                            String payload = message.getData();
                            if (payload == null) return;
                            JSONObject request = new JSONObject(payload);
                            String id = request.optString("id", "");
                            if (id.isEmpty()) return;
                            String type = request.optString("type", "");
                            if ("show-break-ad".equals(type)) {
                                showBreakAd(id, replyProxy);
                            } else if ("cancel-break-ad".equals(type)
                                    && id.equals(adRequestId) && !adShowing) {
                                clearAdRequest();
                            }
                        } catch (JSONException ignored) {
                            // Only the Beyond TV break protocol may request a native ad.
                        }
                    });
        }

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                progress.setProgress(newProgress);
                progress.setVisibility(newProgress < 100 ? View.VISIBLE : View.GONE);
            }
        });

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                if (!adShowing) clearAdRequest();
                errorPanel.setVisibility(View.GONE);
                webView.setVisibility(View.VISIBLE);
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
                if (request.isForMainFrame()) {
                    showError(getString(R.string.load_error));
                }
            }

            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                Uri uri = request.getUrl();
                if ("https".equalsIgnoreCase(uri.getScheme()) && ALLOWED_HOST.equalsIgnoreCase(uri.getHost())) {
                    return false;
                }
                try {
                    startActivity(new Intent(Intent.ACTION_VIEW, uri));
                } catch (RuntimeException ignored) {
                    showError(getString(R.string.external_link_error));
                }
                return true;
            }
        });
    }

    private void showBreakAd(String id, JavaScriptReplyProxy replyProxy) {
        if (!adsReady || adRequestId != null) {
            sendAdEvent(replyProxy, id, "failed");
            return;
        }
        adRequestId = id;
        adReply = replyProxy;
        adShowing = false;
        final int serial = ++adRequestSerial;
        adHandler.postDelayed(() -> {
            if (serial == adRequestSerial && !adShowing) finishAdRequest("failed");
        }, 9000);
        UnityAds.load(INTERSTITIAL_ID, new IUnityAdsLoadListener() {
            @Override
            public void onUnityAdsAdLoaded(String placementId) {
                runOnUiThread(() -> {
                    if (serial != adRequestSerial || adRequestId == null) return;
                    adShowing = true;
                    sendAdEvent(adReply, adRequestId, "started");
                    try {
                        UnityAds.show(MainActivity.this, INTERSTITIAL_ID,
                            new UnityAdsShowOptions(), new IUnityAdsShowListener() {
                                @Override
                                public void onUnityAdsShowStart(String placementId) { }

                                @Override
                                public void onUnityAdsShowClick(String placementId) { }

                                @Override
                                public void onUnityAdsShowComplete(String placementId,
                                        UnityAds.UnityAdsShowCompletionState state) {
                                    runOnUiThread(() -> {
                                        if (serial == adRequestSerial) finishAdRequest("complete");
                                    });
                                }

                                @Override
                                public void onUnityAdsShowFailure(String placementId,
                                        UnityAds.UnityAdsShowError error, String message) {
                                    Log.w(TAG, "Unity interstitial show failed: " + error + " " + message);
                                    runOnUiThread(() -> {
                                        if (serial == adRequestSerial) finishAdRequest("failed");
                                    });
                                }
                            });
                    } catch (RuntimeException error) {
                        Log.w(TAG, "Unity interstitial could not open", error);
                        finishAdRequest("failed");
                    }
                });
            }

            @Override
            public void onUnityAdsFailedToLoad(String placementId,
                    UnityAds.UnityAdsLoadError error, String message) {
                Log.w(TAG, "Unity interstitial load failed: " + error + " " + message);
                runOnUiThread(() -> {
                    if (serial == adRequestSerial) finishAdRequest("failed");
                });
            }
        });
    }

    private void sendAdEvent(JavaScriptReplyProxy reply, String id, String type) {
        if (reply == null || id == null) return;
        try {
            reply.postMessage(new JSONObject().put("id", id).put("type", type).toString());
        } catch (JSONException | RuntimeException error) {
            Log.w(TAG, "Could not report ad state", error);
        }
    }

    private void finishAdRequest(String type) {
        sendAdEvent(adReply, adRequestId, type);
        clearAdRequest();
    }

    private void clearAdRequest() {
        adRequestSerial++;
        adRequestId = null;
        adReply = null;
        adShowing = false;
    }

    @Override
    public void onInitializationComplete() {
        adsReady = true;
    }

    @Override
    public void onInitializationFailed(UnityAds.UnityAdsInitializationError error, String message) {
        Log.w(TAG, "Unity Ads initialization failed: " + error + " " + message);
    }

    private void loadIntent(Intent intent) {
        Uri uri = intent.getData();
        if (uri != null && "https".equalsIgnoreCase(uri.getScheme()) && ALLOWED_HOST.equalsIgnoreCase(uri.getHost())) {
            webView.loadUrl(uri.toString());
        } else {
            loadHome();
        }
    }

    private void loadHome() {
        if (!isOnline()) {
            showError(getString(R.string.offline_error));
            return;
        }
        errorPanel.setVisibility(View.GONE);
        webView.setVisibility(View.VISIBLE);
        webView.loadUrl(HOME_URL);
    }

    private boolean isOnline() {
        ConnectivityManager manager = getSystemService(ConnectivityManager.class);
        Network network = manager.getActiveNetwork();
        NetworkCapabilities capabilities = network == null ? null : manager.getNetworkCapabilities(network);
        return capabilities != null && capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET);
    }

    private void showError(String message) {
        errorMessage.setText(message);
        progress.setVisibility(View.GONE);
        webView.setVisibility(View.INVISIBLE);
        errorPanel.setVisibility(View.VISIBLE);
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        webView.saveState(outState);
        super.onSaveInstanceState(outState);
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.onResume();
    }

    @Override
    protected void onPause() {
        webView.onPause();
        super.onPause();
    }
}
