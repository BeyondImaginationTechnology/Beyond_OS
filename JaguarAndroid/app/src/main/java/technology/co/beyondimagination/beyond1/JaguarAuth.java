package technology.co.beyondimagination.beyond1;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.net.Uri;
import android.os.Build;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyProperties;
import android.util.Base64;

import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.security.KeyStore;
import java.security.MessageDigest;
import java.security.SecureRandom;

import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;

/** PKCE sign-in with the Android Keystore protecting the long-lived access token. */
final class JaguarAuth {
    interface Listener { void changed(); }

    private static final String ROOT = "https://beyondimagination.co.technology";
    private static final String KEY_ALIAS = "beyond1-android-token";
    private final Activity activity;
    private final SharedPreferences preferences;
    private final Listener listener;
    private volatile String token;
    private String verifier;
    private String status = "Sign in with Beyond ID to use live Jaguar. Or try the reviewer demo.";

    JaguarAuth(Activity activity, Listener listener) {
        this.activity = activity;
        this.listener = listener;
        this.preferences = activity.getSharedPreferences("beyond1_auth", Context.MODE_PRIVATE);
        this.token = loadSecret("token");
        this.verifier = loadSecret("verifier");
        if (token != null) status = "Beyond ID connected.";
    }

    boolean signedIn() { return token != null && !token.isEmpty(); }
    String token() { return signedIn() ? token : null; }
    String status() { return status; }

    void signIn() {
        try {
            byte[] random = new byte[48];
            new SecureRandom().nextBytes(random);
            verifier = Base64.encodeToString(random, Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP);
            storeSecret("verifier", verifier);
            String challenge = Base64.encodeToString(
                MessageDigest.getInstance("SHA-256").digest(verifier.getBytes(StandardCharsets.US_ASCII)),
                Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP
            );
            String complete = "/beyond-id/auth/mobile-complete.php?scheme=jaguarandroid&code_challenge=" + challenge;
            Uri login = Uri.parse(ROOT + "/beyond-id/auth/login.php").buildUpon()
                .appendQueryParameter("app", "jaguar")
                .appendQueryParameter("return", complete)
                .build();
            status = "Opening secure Beyond ID sign-in…";
            listener.changed();
            activity.startActivity(new Intent(Intent.ACTION_VIEW, login));
        } catch (Exception exception) {
            status = "Could not open Beyond ID. Try again.";
            listener.changed();
        }
    }

    void complete(Uri callback) {
        if (callback == null || !"jaguarandroid".equals(callback.getScheme()) || !"auth".equals(callback.getHost())) return;
        String code = callback.getQueryParameter("code");
        if (code == null || verifier == null) {
            status = "Sign-in expired. Start a new Beyond ID sign-in attempt.";
            listener.changed();
            return;
        }
        String usedVerifier = verifier;
        verifier = null;
        clearSecret("verifier");
        status = "Connecting Beyond ID…";
        listener.changed();
        new Thread(() -> {
            try {
                JSONObject payload = new JSONObject();
                payload.put("code", code);
                payload.put("code_verifier", usedVerifier);
                payload.put("device_id", deviceId());
                payload.put("device_name", "Beyond-1 on " + Build.MODEL);
                JSONObject response = request(payload);
                String accessToken = response.getString("access_token");
                storeSecret("token", accessToken);
                activity.runOnUiThread(() -> {
                    token = accessToken;
                    status = "Beyond ID connected.";
                    listener.changed();
                });
            } catch (Exception exception) {
                activity.runOnUiThread(() -> {
                    status = "Beyond ID sign-in could not finish. Try again.";
                    listener.changed();
                });
            }
        }).start();
    }

    void signOut() {
        token = null;
        clearSecret("token");
        status = "Signed out. Reviewer demo remains available.";
        listener.changed();
    }

    private JSONObject request(JSONObject payload) throws Exception {
        HttpURLConnection connection = (HttpURLConnection) new URL(ROOT + "/beyond-id/api/mobile-token.php").openConnection();
        connection.setConnectTimeout(15000);
        connection.setReadTimeout(30000);
        connection.setRequestMethod("POST");
        connection.setRequestProperty("Accept", "application/json");
        connection.setRequestProperty("Content-Type", "application/json");
        connection.setDoOutput(true);
        connection.getOutputStream().write(payload.toString().getBytes(StandardCharsets.UTF_8));
        int statusCode = connection.getResponseCode();
        InputStream stream = statusCode < 400 ? connection.getInputStream() : connection.getErrorStream();
        ByteArrayOutputStream bytes = new ByteArrayOutputStream();
        try (InputStream input = stream) {
            byte[] buffer = new byte[4096];
            for (int count; (count = input.read(buffer)) != -1;) bytes.write(buffer, 0, count);
        }
        connection.disconnect();
        JSONObject result = new JSONObject(bytes.toString(StandardCharsets.UTF_8));
        if (statusCode < 200 || statusCode >= 300 || !result.optBoolean("ok", false)) throw new IllegalStateException("Token exchange failed");
        return result;
    }

    private String deviceId() {
        String current = preferences.getString("device_id", null);
        if (current != null) return current;
        byte[] random = new byte[24];
        new SecureRandom().nextBytes(random);
        current = Base64.encodeToString(random, Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP);
        preferences.edit().putString("device_id", current).apply();
        return current;
    }

    private SecretKey key() throws Exception {
        KeyStore store = KeyStore.getInstance("AndroidKeyStore");
        store.load(null);
        if (store.containsAlias(KEY_ALIAS)) return (SecretKey) store.getKey(KEY_ALIAS, null);
        KeyGenerator generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore");
        generator.init(new KeyGenParameterSpec.Builder(KEY_ALIAS, KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT)
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
            .build());
        return generator.generateKey();
    }

    private void storeSecret(String name, String value) throws Exception {
        Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
        cipher.init(Cipher.ENCRYPT_MODE, key());
        preferences.edit()
            .putString(name, Base64.encodeToString(cipher.doFinal(value.getBytes(StandardCharsets.UTF_8)), Base64.NO_WRAP))
            .putString(name + "_iv", Base64.encodeToString(cipher.getIV(), Base64.NO_WRAP))
            .apply();
    }

    private String loadSecret(String name) {
        try {
            String value = preferences.getString(name, null);
            String iv = preferences.getString(name + "_iv", null);
            if (value == null || iv == null) return null;
            Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
            cipher.init(Cipher.DECRYPT_MODE, key(), new GCMParameterSpec(128, Base64.decode(iv, Base64.DEFAULT)));
            return new String(cipher.doFinal(Base64.decode(value, Base64.DEFAULT)), StandardCharsets.UTF_8);
        } catch (Exception exception) {
            clearSecret(name);
            return null;
        }
    }

    private void clearSecret(String name) { preferences.edit().remove(name).remove(name + "_iv").apply(); }
}
