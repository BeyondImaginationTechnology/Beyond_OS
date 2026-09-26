package technology.co.beyondimagination.dailybreath;

import android.app.PendingIntent;
import android.appwidget.AppWidgetManager;
import android.appwidget.AppWidgetProvider;
import android.content.ComponentName;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.BitmapFactory;
import android.graphics.Color;
import android.net.Uri;
import android.view.Gravity;
import android.view.View;
import android.widget.RemoteViews;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.InputStream;
import java.io.ByteArrayOutputStream;
import java.time.LocalDate;
import java.util.Locale;

/** Home-screen reading widget, refreshed by app syncs and the launcher timeline. */
public final class DailyBreathWidgetProvider extends AppWidgetProvider {
    private static final String PREFS = "daily_breath";

    @Override
    public void onUpdate(Context context, AppWidgetManager manager, int[] widgetIds) {
        for (int widgetId : widgetIds) manager.updateAppWidget(widgetId, buildViews(context));
    }

    static void updateWidgets(Context context) {
        AppWidgetManager manager = AppWidgetManager.getInstance(context);
        ComponentName provider = new ComponentName(context, DailyBreathWidgetProvider.class);
        for (int widgetId : manager.getAppWidgetIds(provider)) {
            manager.updateAppWidget(widgetId, buildViews(context));
        }
    }

    private static RemoteViews buildViews(Context context) {
        SharedPreferences prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE);
        String date = LocalDate.now().toString();
        String faith = prefs.getString("selected_faith", "BIBLE");
        String locale = faith.equals("TANAKH") ? "he" : faith.equals("QURAN") ? "ar" : prefs.getString("interface_language", "en");
        String theme = prefs.getString("daily_breath_theme", "seasonal");
        Reading reading = readCachedReading(prefs, date, faith, locale);
        if (reading == null) reading = localReading(context, date, faith);

        RemoteViews views = new RemoteViews(context.getPackageName(), R.layout.daily_breath_widget);
        views.setTextViewText(R.id.widget_reading_label, faith.equals("TANAKH") ? "TANAKH PASSAGE OF THE DAY" : faith.equals("QURAN") ? "QURAN AYAH OF THE DAY" : "BIBLE VERSE OF THE DAY");
        views.setTextViewText(R.id.widget_verse, reading.text);
        views.setTextViewText(R.id.widget_reference, reading.reference);
        int background = backgroundColor(theme);
        views.setInt(R.id.widget_root, "setBackgroundColor", background);

        String artwork = artworkPath(theme);
        if (artwork != null) {
            try (InputStream stream = context.getAssets().open(artwork)) {
                BitmapFactory.Options options = new BitmapFactory.Options();
                options.inSampleSize = 4;
                views.setImageViewBitmap(R.id.widget_artwork, BitmapFactory.decodeStream(stream, null, options));
                views.setViewVisibility(R.id.widget_artwork, View.VISIBLE);
                views.setViewVisibility(R.id.widget_scrim, View.VISIBLE);
            } catch (Exception ignored) {
                views.setViewVisibility(R.id.widget_artwork, View.GONE);
                views.setViewVisibility(R.id.widget_scrim, View.GONE);
            }
        } else {
            views.setViewVisibility(R.id.widget_artwork, View.GONE);
            views.setViewVisibility(R.id.widget_scrim, View.GONE);
        }

        boolean rtl = hasRightToLeftCharacters(reading.text);
        views.setInt(R.id.widget_verse, "setTextDirection", rtl ? View.TEXT_DIRECTION_RTL : View.TEXT_DIRECTION_FIRST_STRONG);
        views.setInt(R.id.widget_verse, "setGravity", rtl ? Gravity.END | Gravity.CENTER_VERTICAL : Gravity.START | Gravity.CENTER_VERTICAL);

        Intent openToday = new Intent(context, MainActivity.class).setData(Uri.parse("dailybreath://today"));
        int flags = PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE;
        PendingIntent pendingIntent = PendingIntent.getActivity(context, 2301, openToday, flags);
        views.setOnClickPendingIntent(R.id.widget_root, pendingIntent);
        return views;
    }

    private static Reading readCachedReading(SharedPreferences prefs, String date, String faith, String locale) {
        String key = "daily_content_" + date + "_" + faith.toLowerCase(Locale.US) + "_" + locale;
        try {
            String stored = prefs.getString(key, "");
            if (stored.isEmpty()) return null;
            JSONObject json = new JSONObject(stored);
            String text = json.optString("text", "");
            String reference = json.optString("reference", "");
            return text.isEmpty() || reference.isEmpty() ? null : new Reading(text, reference);
        } catch (Exception ignored) {
            return null;
        }
    }

    private static Reading localReading(Context context, String date, String faith) {
        if (faith.equals("TANAKH")) return new Reading("בְּטַח אֶל־יְהוָה בְּכָל־לִבֶּךָ", "משלי 3:5");
        if (faith.equals("QURAN")) return new Reading("قُلْ أَعُوذُ بِرَبِّ الْفَلَقِ", "الفلق 113:1");
        try (InputStream stream = context.getAssets().open("daily-verses.json")) {
            ByteArrayOutputStream output = new ByteArrayOutputStream();
            byte[] buffer = new byte[8192];
            int count;
            while ((count = stream.read(buffer)) != -1) output.write(buffer, 0, count);
            JSONObject document = new JSONObject(output.toString(java.nio.charset.StandardCharsets.UTF_8.name()));
            JSONArray entries = document.optJSONArray("entries");
            if (entries != null && entries.length() > 0) {
                for (int index = 0; index < entries.length(); index++) {
                    JSONObject item = entries.optJSONObject(index);
                    if (item != null && date.equals(item.optString("schedule_date", ""))) {
                        return new Reading(item.optString("text"), item.optString("reference"));
                    }
                }
                int day = LocalDate.parse(date).getDayOfYear();
                JSONObject item = entries.optJSONObject((day - 1) % entries.length());
                if (item != null) return new Reading(item.optString("text"), item.optString("reference"));
            }
        } catch (Exception ignored) { }
        return new Reading("Be still, and know that I am God.", "Psalm 46:10");
    }

    private static String artworkPath(String theme) {
        switch (theme) {
            case "bibleForest": return "artwork/bible-forest.png";
            case "tanakhNavy": return "artwork/tanakh-navy.png";
            case "quranEmerald": return "artwork/quran-emerald.png";
            default: return null;
        }
    }

    private static int backgroundColor(String theme) {
        if (theme.equals("seasonal")) {
            int month = LocalDate.now().getMonthValue();
            theme = month >= 9 && month <= 11 ? "fall" : month == 12 || month <= 2 ? "forest" : "botanical";
        }
        switch (theme) {
            case "fall": return Color.rgb(79, 43, 27);
            case "forest": return Color.rgb(15, 54, 37);
            case "botanical": return Color.rgb(42, 67, 46);
            case "dawn": return Color.rgb(99, 47, 34);
            case "rose": return Color.rgb(92, 25, 53);
            case "torahLight": return Color.rgb(31, 60, 105);
            case "quranMoon": return Color.rgb(6, 20, 43);
            case "tanakhNavy": return Color.rgb(7, 19, 44);
            case "quranEmerald": return Color.rgb(5, 43, 34);
            case "bibleForest": return Color.rgb(8, 40, 29);
            default: return Color.rgb(7, 39, 25);
        }
    }

    private static boolean hasRightToLeftCharacters(String text) {
        return text.codePoints().anyMatch(codePoint -> (codePoint >= 0x0590 && codePoint <= 0x08FF) || (codePoint >= 0xFB1D && codePoint <= 0xFEFC));
    }

    private static final class Reading {
        final String text;
        final String reference;

        Reading(String text, String reference) {
            this.text = text;
            this.reference = reference;
        }
    }
}
