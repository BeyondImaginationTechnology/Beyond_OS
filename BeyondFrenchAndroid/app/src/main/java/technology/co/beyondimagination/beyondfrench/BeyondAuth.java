package technology.co.beyondimagination.beyondfrench;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.net.Uri;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyProperties;
import android.util.Base64;
import android.os.Build;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.security.KeyStore;
import java.security.MessageDigest;
import java.security.SecureRandom;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.HashMap;
import java.util.LinkedHashSet;
import java.util.Map;
import java.util.Objects;

import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;

/** PKCE sign-in and Android Keystore storage for a Beyond ID mobile session. */
final class BeyondAuth {
    interface Listener { void changed(); }
    static final String AUDIENCE = "beyond-french-android";
    private static final String ROOT = "https://beyondimagination.co.technology";
    private static final String KEY_ALIAS = "beyond-french-mobile-token";
    private final Activity activity;
    private final SharedPreferences prefs;
    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private final Listener listener;
    private final Object sessionLock = new Object();
    private volatile String token;
    private volatile String refreshToken;
    private String verifier;
    private volatile int userId;
    private String message = "Learn as a guest, then sign in to sync progress.";
    private String progressRevision;

    BeyondAuth(Activity activity, Listener listener) {
        this.activity = activity;
        this.listener = listener;
        prefs = activity.getSharedPreferences("beyond_french_auth", Context.MODE_PRIVATE);
        token = loadToken();
        refreshToken = loadSecret("refresh_token");
        verifier = loadSecret("verifier");
    }

    boolean signedIn() { return token != null && userId > 0; }
    int userId() { return userId; }
    String token() { return signedIn() ? token : null; }
    String message() { return message; }

    void restore() {
        if (token == null) return;
        io.execute(() -> {
            try {
                refreshIfNeeded();
                int id = sessionId(token);
                activity.runOnUiThread(() -> { userId = id; message = "Beyond ID connected. Progress sync is ready."; listener.changed(); });
            } catch (Exception error) {
                activity.runOnUiThread(() -> { message = "Could not restore Beyond ID. Guest progress remains here."; listener.changed(); });
            }
        });
    }

    void signIn() {
        try {
            byte[] random = new byte[48];
            new SecureRandom().nextBytes(random);
            verifier = Base64.encodeToString(random, Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP);
            byte[] digest = MessageDigest.getInstance("SHA-256").digest(verifier.getBytes(StandardCharsets.US_ASCII));
            storeSecret("verifier", verifier);
            String challenge = Base64.encodeToString(digest, Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP);
            String target = "/beyond-id/auth/mobile-complete.php?scheme=beyondfrenchandroid&code_challenge=" + challenge;
            Uri login = Uri.parse(ROOT + "/beyond-id/auth/login.php").buildUpon()
                .appendQueryParameter("app", "beyond-french")
                .appendQueryParameter("return", target).build();
            activity.startActivity(new Intent(Intent.ACTION_VIEW, login));
        } catch (Exception error) { message = "Could not open Beyond ID."; listener.changed(); }
    }

    void complete(Uri callback) {
        if (callback == null || !"beyondfrenchandroid".equals(callback.getScheme()) || !"auth".equals(callback.getHost())) return;
        String code = callback.getQueryParameter("code");
        if (code == null || verifier == null) {
            message = callback.getQueryParameter("error") == null ? "Sign-in expired. Try again." : callback.getQueryParameter("error");
            listener.changed();
            return;
        }
        String challengeVerifier = verifier;
        verifier = null;
        clearSecret("verifier");
        message = "Connecting Beyond ID…";
        listener.changed();
        io.execute(() -> {
            try {
                JSONObject request = new JSONObject();
                request.put("code", code);
                request.put("code_verifier", challengeVerifier);
                request.put("device_id", deviceId());
                request.put("device_name", "Beyond French on " + Build.MODEL);
                request.put("token_version", "0.4");
                JSONObject response = request("POST", ROOT + "/beyond-id/api/mobile-token.php", null, request);
                String newToken = response.getString("access_token");
                String newRefreshToken = response.getString("refresh_token");
                int id = sessionId(newToken);
                storeToken(newToken);
                storeSecret("refresh_token", newRefreshToken);
                activity.runOnUiThread(() -> {
                    token = newToken; refreshToken = newRefreshToken; userId = id;
                    message = "Signed in. Your progress is syncing.";
                    listener.changed();
                });
            } catch (Exception error) {
                activity.runOnUiThread(() -> { message = "Sign-in could not finish. Guest progress remains here."; listener.changed(); });
            }
        });
    }

    void signOut() {
        String previous = token;
        String previousRefresh = refreshToken;
        synchronized (sessionLock) {
            token = null; refreshToken = null; userId = 0;
            clearSecret("token");
            clearSecret("refresh_token");
        }
        message = "Signed out. You can continue learning as a guest.";
        listener.changed();
        if (previous != null) io.execute(() -> {
            try {
                JSONObject body = new JSONObject();
                if (previousRefresh != null) body.put("refresh_token", previousRefresh);
                request("POST", ROOT + "/beyond-id/api/mobile-token-revoke.php", previous, body);
            }
            catch (Exception ignored) { }
        });
    }

    JSONObject progress(String method, JSONObject payload) throws Exception {
        String current = token();
        if (current == null) throw new IllegalStateException("Not signed in");
        String endpoint = ROOT + "/beyond-id/api/v1/french-progress.php";
        JSONObject response;
        if ("GET".equals(method)) {
            response = request(method, endpoint, current, null);
            progressRevision = response.optString("revision", null);
        } else {
            if (progressRevision == null) throw new IllegalStateException("Fetch the latest progress revision before saving.");
            try {
                response = writeProgress(endpoint, current, payload);
            } catch (IllegalStateException conflict) {
                if (conflict.getMessage() == null || !conflict.getMessage().contains("changed on another device")) throw conflict;
                JSONObject latest = request("GET", endpoint, current, null);
                JSONObject latestData = latest.optJSONObject("data");
                if (latestData == null) throw conflict;
                progressRevision = latest.optString("revision", progressRevision);
                JSONObject merged = mergeProgress(payload, latestData);
                response = writeProgress(endpoint, current, merged);
            }
            progressRevision = response.optString("revision", progressRevision);
        }
        JSONObject data = response.optJSONObject("data");
        if (data == null) throw new IllegalStateException("The progress response is incomplete.");
        data.put("revision", progressRevision);
        return data;
    }

    private JSONObject writeProgress(String endpoint, String bearer, JSONObject payload) throws Exception {
        Map<String, String> headers = new HashMap<>();
        headers.put("If-Match", "\"" + progressRevision + "\"");
        headers.put("Idempotency-Key", deviceId() + ":" + java.util.UUID.randomUUID());
        return request("PUT", endpoint, bearer, payload, headers);
    }

    private JSONObject mergeProgress(JSONObject local, JSONObject remote) throws Exception {
        LinkedHashSet<String> lessons = new LinkedHashSet<>();
        JSONArray remoteLessons = remote.optJSONArray("completed_lesson_ids");
        if (remoteLessons != null) for (int i = 0; i < remoteLessons.length(); i++) lessons.add(remoteLessons.optString(i));
        JSONArray localLessons = local.optJSONArray("completed_lesson_ids");
        if (localLessons != null) for (int i = 0; i < localLessons.length(); i++) lessons.add(localLessons.optString(i));
        JSONArray daily = new JSONArray();
        LinkedHashSet<Integer> dailyIds = new LinkedHashSet<>();
        JSONArray remoteDaily = remote.optJSONArray("completed_daily_lesson_ids");
        if (remoteDaily != null) for (int i = 0; i < remoteDaily.length(); i++) dailyIds.add(remoteDaily.optInt(i));
        JSONArray localDaily = local.optJSONArray("completed_daily_lesson_ids");
        if (localDaily != null) for (int i = 0; i < localDaily.length(); i++) dailyIds.add(localDaily.optInt(i));
        for (Integer id : dailyIds) daily.put(id);
        return new JSONObject()
            .put("completed_lesson_ids", new JSONArray(lessons))
            .put("completed_daily_lesson_ids", daily)
            .put("correct_practice_count", Math.max(remote.optInt("correct_practice_count"), local.optInt("correct_practice_count")));
    }

    void requestDeletion() {
        String current = token();
        if (current == null) return;
        io.execute(() -> {
            try {
                JSONObject body = new JSONObject(); body.put("confirm", "DELETE");
                JSONObject reply = request("POST", ROOT + "/beyond-id/api/account-deletion-request.php", current, body);
                activity.runOnUiThread(() -> {
                    message = reply.optString("message", "Account deletion request submitted.");
                    listener.changed();
                });
            } catch (Exception error) {
                activity.runOnUiThread(() -> { message = "Could not submit the deletion request. Try again later."; listener.changed(); });
            }
        });
    }

    private int sessionId(String current) throws Exception {
        JSONObject response = request("GET", ROOT + "/beyond-id/api/mobile-session.php", current, null);
        int id = response.getJSONObject("user").getInt("id");
        if (id <= 0) throw new IllegalStateException("Invalid account");
        return id;
    }

    private JSONObject request(String method, String location, String bearer, JSONObject body) throws Exception {
        return request(method, location, bearer, body, null);
    }

    private JSONObject request(String method, String location, String bearer, JSONObject body, Map<String, String> extraHeaders) throws Exception {
        if (bearer != null && !location.endsWith("mobile-token-refresh.php") && !location.endsWith("mobile-token.php")) {
            refreshIfNeeded();
            if (token != null) bearer = token;
        }
        HttpURLConnection connection = (HttpURLConnection) new URL(location).openConnection();
        connection.setConnectTimeout(10000);
        connection.setReadTimeout(12000);
        connection.setRequestMethod(method);
        connection.setRequestProperty("Accept", "application/json");
        if (bearer != null) connection.setRequestProperty("Authorization", "Bearer " + bearer);
        if (location.endsWith("mobile-session.php") || location.endsWith("/api/v1/french-progress.php")) connection.setRequestProperty("X-Beyond-App", AUDIENCE);
        if (extraHeaders != null) for (Map.Entry<String, String> header : extraHeaders.entrySet()) connection.setRequestProperty(header.getKey(), header.getValue());
        if (body != null) {
            connection.setRequestProperty("Content-Type", "application/json");
            connection.setDoOutput(true);
            connection.getOutputStream().write(body.toString().getBytes(StandardCharsets.UTF_8));
        }
        int status = connection.getResponseCode();
        InputStream stream = status < 400 ? connection.getInputStream() : connection.getErrorStream();
        ByteArrayOutputStream bytes = new ByteArrayOutputStream();
        try (InputStream input = stream) {
            byte[] buffer = new byte[4096]; int count;
            while ((count = input.read(buffer)) != -1) bytes.write(buffer, 0, count);
        }
        connection.disconnect();
        JSONObject reply = new JSONObject(bytes.toString("UTF-8"));
        if (status < 200 || status >= 300 || !reply.optBoolean("ok", false)) {
            Object error = reply.opt("error");
            String detail = error instanceof JSONObject ? ((JSONObject) error).optString("message", "Request failed") : String.valueOf(error == null ? "Request failed" : error);
            throw new IllegalStateException(detail);
        }
        return reply;
    }

    private void refreshIfNeeded() throws Exception {
        if (token == null || refreshToken == null) return;
        long expires = 0;
        try {
            String[] parts = token.split("\\.");
            JSONObject claims = new JSONObject(new String(Base64.decode(parts[0], Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP), StandardCharsets.UTF_8));
            expires = claims.optLong("exp", 0);
        } catch (Exception ignored) { }
        if (expires > System.currentTimeMillis() / 1000L + 60) return;
        JSONObject body = new JSONObject();
        String usedRefresh = refreshToken;
        body.put("refresh_token", usedRefresh);
        body.put("audience", AUDIENCE);
        try {
            JSONObject response = request("POST", ROOT + "/beyond-id/api/mobile-token-refresh.php", null, body);
            String nextToken = response.getString("access_token");
            String nextRefresh = response.getString("refresh_token");
            synchronized (sessionLock) {
                if (token == null || !Objects.equals(refreshToken, usedRefresh)) throw new IllegalStateException("The device was signed out during token renewal.");
                storeToken(nextToken);
                storeSecret("refresh_token", nextRefresh);
                token = nextToken;
                refreshToken = nextRefresh;
            }
        } catch (Exception error) {
            clearSecret("token");
            clearSecret("refresh_token");
            token = null;
            refreshToken = null;
            userId = 0;
            activity.runOnUiThread(() -> {
                message = "Your Beyond ID session expired. Sign in again to continue syncing.";
                listener.changed();
            });
            throw error;
        }
    }

    private String deviceId() {
        String id = prefs.getString("device_id", null);
        if (id == null) {
            byte[] random = new byte[24];
            new SecureRandom().nextBytes(random);
            id = Base64.encodeToString(random, Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP);
            prefs.edit().putString("device_id", id).apply();
        }
        return id;
    }

    private SecretKey key() throws Exception {
        KeyStore store = KeyStore.getInstance("AndroidKeyStore"); store.load(null);
        if (store.containsAlias(KEY_ALIAS)) return (SecretKey) store.getKey(KEY_ALIAS, null);
        KeyGenerator generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore");
        generator.init(new KeyGenParameterSpec.Builder(KEY_ALIAS, KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT)
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM).setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE).build());
        return generator.generateKey();
    }

    private void storeToken(String value) throws Exception { storeSecret("token", value); }

    private void storeSecret(String name, String value) throws Exception {
        Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding"); cipher.init(Cipher.ENCRYPT_MODE, key());
        String encoded = Base64.encodeToString(cipher.doFinal(value.getBytes(StandardCharsets.UTF_8)), Base64.NO_WRAP);
        String iv = Base64.encodeToString(cipher.getIV(), Base64.NO_WRAP);
        prefs.edit().putString(name, encoded).putString(name + "_iv", iv).apply();
    }

    private String loadToken() { return loadSecret("token"); }

    private String loadSecret(String name) {
        try {
            String encoded = prefs.getString(name, null), iv = prefs.getString(name + "_iv", null);
            if (encoded == null || iv == null) return null;
            Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
            cipher.init(Cipher.DECRYPT_MODE, key(), new GCMParameterSpec(128, Base64.decode(iv, Base64.DEFAULT)));
            return new String(cipher.doFinal(Base64.decode(encoded, Base64.DEFAULT)), StandardCharsets.UTF_8);
        } catch (Exception error) { clearSecret(name); return null; }
    }

    private void clearSecret(String name) { prefs.edit().remove(name).remove(name + "_iv").apply(); }

    void close() { io.shutdownNow(); }
}
