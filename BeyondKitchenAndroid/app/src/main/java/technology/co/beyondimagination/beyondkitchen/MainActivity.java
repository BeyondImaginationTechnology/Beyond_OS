package technology.co.beyondimagination.beyondkitchen;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Intent;
import android.graphics.Insets;
import android.graphics.Color;
import android.graphics.BitmapFactory;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.os.Build;
import android.os.Bundle;
import android.net.Uri;
import android.text.Editable;
import android.text.InputFilter;
import android.text.TextWatcher;
import android.util.Log;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.view.WindowInsets;
import android.widget.Button;
import android.widget.EditText;
import android.widget.HorizontalScrollView;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.text.DecimalFormat;
import java.time.LocalDate;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collections;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;

public final class MainActivity extends Activity {
    private static final String DINNER_ENDPOINT = "https://beyondimagination.co.technology/beyond-kitchen/api/dinner.php";
    private static final int PAPER = Color.rgb(246, 245, 239);
    private static final int CARD = Color.rgb(255, 254, 250);
    private static final int INK = Color.rgb(40, 49, 38);
    private static final int MUTED = Color.rgb(119, 122, 110);
    private static final int GREEN = Color.rgb(64, 91, 66);
    private static final int GOLD = Color.rgb(219, 189, 117);

    private final List<Recipe> recipes = new ArrayList<>();
    private final List<TextView> filterChips = new ArrayList<>();
    private final Set<String> favorites = new HashSet<>();
    private LinearLayout recipeList;
    private EditText searchInput;
    private TextView savedButton;
    private TextView sectionTitle;
    private String filter = "All";
    private boolean savedOnly;
    private String loadError;

    @Override
    public void onCreate(Bundle state) {
        super.onCreate(state);
        getWindow().setStatusBarColor(PAPER);
        getWindow().setNavigationBarColor(PAPER);
        getWindow().getDecorView().setSystemUiVisibility(View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR | View.SYSTEM_UI_FLAG_LIGHT_NAVIGATION_BAR);
        favorites.addAll(getSharedPreferences("beyond-kitchen", MODE_PRIVATE).getStringSet("favorites", Collections.emptySet()));
        loadRecipes();
        buildScreen();
        renderRecipes();
    }

    private void loadRecipes() {
        try (InputStream input = getAssets().open("recipes.json");
             BufferedReader reader = new BufferedReader(new InputStreamReader(input, StandardCharsets.UTF_8))) {
            StringBuilder json = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) json.append(line);
            JSONArray rows = new JSONArray(json.toString());
            for (int i = 0; i < rows.length(); i++) {
                Recipe recipe = new Recipe(rows.getJSONObject(i));
                if (!recipe.id.isEmpty() && !recipe.name.isEmpty() && !recipe.ingredients.isEmpty() && !recipe.steps.isEmpty()) {
                    recipes.add(recipe);
                }
            }
            if (recipes.isEmpty()) loadError = "The recipe library is empty.";
        } catch (IOException | JSONException error) {
            loadError = "Recipes could not be loaded. Please try again later.";
            Log.e("BeyondKitchen", "Unable to load the bundled recipe catalog.", error);
        }
    }

    private void buildScreen() {
        ScrollView scroll = new ScrollView(this);
        scroll.setFillViewport(true);
        LinearLayout page = column();
        page.setPadding(dp(20), dp(12), dp(20), dp(30));
        scroll.addView(page);
        scroll.setOnApplyWindowInsetsListener((view, insets) -> {
            if (Build.VERSION.SDK_INT >= 30) {
                Insets bars = insets.getInsets(WindowInsets.Type.systemBars());
                page.setPadding(dp(20), bars.top + dp(12), dp(20), bars.bottom + dp(18));
            }
            return insets;
        });

        LinearLayout top = row();
        TextView brand = text("Beyond Kitchen", 19, INK, true);
        top.addView(brand, new LinearLayout.LayoutParams(0, dp(46), 1));
        savedButton = text("♡ Saved", 13, GREEN, true);
        savedButton.setGravity(Gravity.CENTER);
        savedButton.setPadding(dp(14), 0, dp(14), 0);
        savedButton.setBackground(shape(CARD, 24, 0xffe5e4da));
        savedButton.setOnClickListener(view -> toggleSavedOnly());
        top.addView(savedButton, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.WRAP_CONTENT, dp(40)));
        page.addView(top);

        TextView eyebrow = text("A FRESH START, EVERY DAY", 10, GREEN, true);
        eyebrow.setLetterSpacing(0.15f);
        LinearLayout.LayoutParams eyebrowParams = params(0, 0, 0, 24);
        page.addView(eyebrow, eyebrowParams);
        page.addView(text("Good food,\nmade simple.", 37, INK, false), params(0, 0, 0, 8));
        page.addView(text("A little inspiration for what to make next.", 13, MUTED, false), params(0, 0, 0, 19));

        if (loadError != null) {
            TextView error = text(loadError, 14, MUTED, false);
            error.setPadding(dp(18), dp(18), dp(18), dp(18));
            error.setBackground(shape(CARD, 17, 0xffe5e4da));
            page.addView(error);
            setContentView(scroll);
            return;
        }

        Recipe featured = dailyRecipe();
        page.addView(featuredCard(featured), params(0, 0, 0, 23));
        page.addView(dinnerPlanner(), params(0, 0, 0, 24));
        page.addView(text("TODAY'S CAROUSEL", 10, GREEN, true), params(0, 0, 0, 8));
        page.addView(text("From ingredients to the finished plate", 20, INK, false), params(0, 0, 0, 12));
        page.addView(dailyCarousel(featured), params(0, 0, 0, 24));
        searchInput = new EditText(this);
        searchInput.setSingleLine(true);
        searchInput.setTextSize(13);
        searchInput.setTextColor(INK);
        searchInput.setHintTextColor(0xff929587);
        searchInput.setHint("Search recipes or ingredients");
        searchInput.setPadding(dp(15), 0, dp(15), 0);
        searchInput.setBackground(shape(CARD, 12, 0xffe5e4da));
        page.addView(searchInput, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, dp(46)));
        searchInput.addTextChangedListener(new TextWatcher() {
            @Override public void beforeTextChanged(CharSequence value, int start, int count, int after) {}
            @Override public void onTextChanged(CharSequence value, int start, int before, int count) { renderRecipes(); }
            @Override public void afterTextChanged(Editable value) {}
        });

        HorizontalScrollView filterScroll = new HorizontalScrollView(this);
        filterScroll.setHorizontalScrollBarEnabled(false);
        LinearLayout filters = row();
        String[] choices = {"All", "Quick", "Vegetarian", "Dinner", "Haitian"};
        for (String choice : choices) {
            TextView chip = text(choice, 11, GREEN, true);
            chip.setGravity(Gravity.CENTER);
            chip.setPadding(dp(13), 0, dp(13), 0);
            chip.setBackground(shape(0xffe7ecdf, 20, 0x00ffffff));
            chip.setOnClickListener(view -> {
                filter = choice;
                refreshFilterChips();
                renderRecipes();
            });
            LinearLayout.LayoutParams chipParams = new LinearLayout.LayoutParams(ViewGroup.LayoutParams.WRAP_CONTENT, dp(36));
            chipParams.setMargins(0, 0, dp(8), 0);
            filters.addView(chip, chipParams);
            filterChips.add(chip);
        }
        refreshFilterChips();
        filterScroll.addView(filters);
        page.addView(filterScroll, params(0, 16, 0, 9));

        sectionTitle = text("Find your next favourite", 24, INK, false);
        page.addView(sectionTitle, params(0, 6, 0, 12));
        recipeList = column();
        page.addView(recipeList);
        setContentView(scroll);
    }

    private Recipe dailyRecipe() {
        return recipes.get(Math.floorMod((int) LocalDate.now().toEpochDay(), recipes.size()));
    }

    private View featuredCard(Recipe recipe) {
        LinearLayout card = column();
        card.setPadding(dp(21), dp(21), dp(21), dp(21));
        card.setBackground(shape(GREEN, 21, 0x00405b42));
        TextView kicker = text("✳  TODAY'S RECIPE", 10, GOLD, true);
        kicker.setLetterSpacing(0.12f);
        card.addView(kicker, params(0, 0, 0, 12));
        card.addView(text(recipe.name, 28, Color.WHITE, false), params(0, 0, 0, 8));
        TextView description = text(recipe.description, 12, 0xffe4e8dc, false);
        description.setLineSpacing(dp(3), 1f);
        card.addView(description, params(0, 0, 0, 15));
        card.addView(text("◷  " + recipe.timeMinutes + " min     ·     " + recipe.difficulty, 11, Color.WHITE, true), params(0, 0, 0, 18));
        Button cook = new Button(this);
        cook.setText("Get cooking   →");
        cook.setTextAllCaps(false);
        cook.setTextSize(12);
        cook.setTextColor(GREEN);
        cook.setTypeface(Typeface.DEFAULT, Typeface.BOLD);
        cook.setBackground(shape(0xfff4d989, 22, 0x00ffffff));
        cook.setOnClickListener(view -> showRecipe(recipe));
        card.addView(cook, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.WRAP_CONTENT, dp(43)));
        return card;
    }

    private View dinnerPlanner() {
        LinearLayout panel = column();
        panel.setPadding(dp(18), dp(20), dp(18), dp(20));
        panel.setBackground(shape(0xffeef2e9, 19, 0xffdce4d7));
        panel.addView(text("DINNER GUIDE · BETA 0.0.1", 10, GREEN, true), params(0, 0, 0, 8));
        panel.addView(text("What's for dinner?", 26, INK, false), params(0, 0, 0, 7));
        panel.addView(text("Tell us your mood, time, budget, or what's in the fridge.", 12, MUTED, false), params(0, 0, 0, 12));
        EditText prompt = new EditText(this);
        prompt.setHint("I'm tired, want something spicy, and have about $25…");
        prompt.setTextSize(13);
        prompt.setTextColor(INK);
        prompt.setHintTextColor(MUTED);
        prompt.setMinLines(2);
        prompt.setMaxLines(3);
        prompt.setFilters(new InputFilter[]{new InputFilter.LengthFilter(400)});
        prompt.setPadding(dp(13), dp(10), dp(13), dp(10));
        prompt.setBackground(shape(CARD, 13, 0xffcfd9ca));
        panel.addView(prompt, params(0, 0, 0, 11));
        HorizontalScrollView hintScroll = new HorizontalScrollView(this);
        hintScroll.setHorizontalScrollBarEnabled(false);
        LinearLayout hints = row();
        String[][] choices = {{"20 min", "under 20 minutes"}, {"Under $25", "under $25"}, {"Low energy", "low energy"}, {"Spicy", "spicy"}, {"Vegetarian", "vegetarian"}};
        for (String[] choice : choices) {
            TextView hint = text(choice[0], 11, GREEN, true);
            hint.setGravity(Gravity.CENTER);
            hint.setPadding(dp(11), 0, dp(11), 0);
            hint.setBackground(shape(CARD, 18, 0xffcbd7c5));
            hint.setOnClickListener(view -> {
                String current = prompt.getText().toString().trim();
                prompt.setText(current + (current.isEmpty() ? "" : ", ") + choice[1]);
                prompt.setSelection(prompt.length());
            });
            LinearLayout.LayoutParams hintParams = new LinearLayout.LayoutParams(ViewGroup.LayoutParams.WRAP_CONTENT, dp(32));
            hintParams.setMargins(0, 0, dp(7), 0);
            hints.addView(hint, hintParams);
        }
        hintScroll.addView(hints);
        panel.addView(hintScroll, params(0, 0, 0, 12));
        Button submit = new Button(this);
        submit.setText("Find dinner ideas  →");
        submit.setTextAllCaps(false);
        submit.setTextColor(Color.WHITE);
        submit.setTextSize(12);
        submit.setBackground(shape(GREEN, 22, 0x00000000));
        panel.addView(submit, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.WRAP_CONTENT, dp(43)));
        panel.addView(text("Pickup and delivery are dish ideas. Check nearby menus and availability.", 10, MUTED, false), params(0, 10, 0, 0));
        LinearLayout results = column();
        panel.addView(results, params(0, 15, 0, 0));
        submit.setOnClickListener(view -> {
            String requestPrompt = prompt.getText().toString().trim();
            if (requestPrompt.isEmpty()) requestPrompt = "Surprise me with a good dinner tonight.";
            submit.setEnabled(false);
            submit.setText("Finding ideas…");
            results.removeAllViews();
            results.addView(text("Putting a few dinner ideas together…", 12, GREEN, false));
            String finalPrompt = requestPrompt;
            new Thread(() -> {
                try {
                    JSONObject answer = fetchDinnerIdeas(finalPrompt);
                    runOnUiThread(() -> renderDinnerIdeas(results, answer));
                } catch (Exception error) {
                    Log.e("BeyondKitchen", "Dinner ideas unavailable.", error);
                    runOnUiThread(() -> renderDinnerUnavailable(results));
                } finally {
                    runOnUiThread(() -> {
                        submit.setEnabled(true);
                        submit.setText("Find dinner ideas  →");
                    });
                }
            }).start();
        });
        return panel;
    }

    private JSONObject fetchDinnerIdeas(String prompt) throws Exception {
        HttpURLConnection connection = (HttpURLConnection) new URL(DINNER_ENDPOINT).openConnection();
        try {
            connection.setRequestMethod("POST");
            connection.setConnectTimeout(8000);
            connection.setReadTimeout(35000);
            connection.setDoOutput(true);
            connection.setRequestProperty("Content-Type", "application/json; charset=utf-8");
            byte[] body = new JSONObject().put("prompt", prompt).toString().getBytes(StandardCharsets.UTF_8);
            try (OutputStream output = connection.getOutputStream()) { output.write(body); }
            InputStream stream = connection.getResponseCode() < 400 ? connection.getInputStream() : connection.getErrorStream();
            if (stream == null) throw new IOException("Dinner service did not respond.");
            StringBuilder response = new StringBuilder();
            try (BufferedReader reader = new BufferedReader(new InputStreamReader(stream, StandardCharsets.UTF_8))) {
                String line;
                while ((line = reader.readLine()) != null) response.append(line);
            }
            JSONObject result = new JSONObject(response.toString());
            if (connection.getResponseCode() >= 400 || !result.optBoolean("ok")) throw new IOException(result.optString("error", "AI dinner ideas are unavailable."));
            return result;
        } finally {
            connection.disconnect();
        }
    }

    private void renderDinnerIdeas(LinearLayout results, JSONObject answer) {
        results.removeAllViews();
        JSONObject cook = answer.optJSONObject("cook");
        JSONObject pickup = answer.optJSONObject("pickup");
        JSONObject delivery = answer.optJSONObject("delivery");
        if (cook == null || pickup == null || delivery == null) {
            renderDinnerUnavailable(results);
            return;
        }
        Recipe chosen = dailyRecipe();
        for (Recipe recipe : recipes) if (recipe.id.equals(cook.optString("recipeId"))) chosen = recipe;
        Recipe cookRecipe = chosen;
        results.addView(dinnerIdeaCard("COOK", chosen.name, cook.optString("why"), "Open recipe  →", () -> showRecipe(cookRecipe)), params(0, 0, 0, 9));
        String pickupDish = pickup.optString("dish");
        Uri pickupUrl = Uri.parse("https://www.google.com/maps/search/").buildUpon().appendQueryParameter("api", "1").appendQueryParameter("query", pickupDish + " pickup near me").build();
        results.addView(dinnerIdeaCard("PICK UP", pickupDish, pickup.optString("why"), "Search nearby pickup  →", () -> startActivity(new Intent(Intent.ACTION_VIEW, pickupUrl))), params(0, 0, 0, 9));
        String deliveryDish = delivery.optString("dish");
        Uri deliveryUrl = Uri.parse("https://www.google.com/search").buildUpon().appendQueryParameter("q", deliveryDish + " delivery near me").build();
        results.addView(dinnerIdeaCard("DELIVER", deliveryDish, delivery.optString("why"), "Search delivery  →", () -> startActivity(new Intent(Intent.ACTION_VIEW, deliveryUrl))));
    }

    private void renderDinnerUnavailable(LinearLayout results) {
        results.removeAllViews();
        results.addView(text("AI dinner ideas are unavailable right now. Here's today's recipe.", 12, MUTED, false), params(0, 0, 0, 10));
        Recipe recipe = dailyRecipe();
        results.addView(dinnerIdeaCard("A RECIPE FOR NOW", recipe.name, recipe.description, "Open recipe  →", () -> showRecipe(recipe)));
    }

    private View dinnerIdeaCard(String label, String title, String why, String action, Runnable onAction) {
        LinearLayout card = column();
        card.setPadding(dp(15), dp(15), dp(15), dp(15));
        card.setBackground(shape(CARD, 15, 0xffe5e4da));
        card.addView(text(label, 10, GREEN, true), params(0, 0, 0, 6));
        card.addView(text(title, 20, INK, true), params(0, 0, 0, 7));
        card.addView(text(why, 12, MUTED, false), params(0, 0, 0, 9));
        TextView link = text(action, 12, GREEN, true);
        card.addView(link);
        card.setOnClickListener(view -> onAction.run());
        return card;
    }

    private View dailyCarousel(Recipe recipe) {
        List<String> labels = Arrays.asList("TODAY'S RECIPE", "WHAT YOU'LL NEED", "LET'S MAKE IT · 1", "LET'S MAKE IT · 2", "MAKE IT YOUR OWN");
        List<String> titles = Arrays.asList(recipe.name, "The ingredients", "Get started", "Bring it together", "Ready to enjoy");
        DecimalFormat format = new DecimalFormat("#.#");
        StringBuilder ingredients = new StringBuilder();
        for (Recipe.Ingredient item : recipe.ingredients) {
            if (ingredients.length() > 0) ingredients.append('\n');
            ingredients.append("• ").append(format.format(item.amount)).append(' ');
            if (!item.unit.isEmpty()) ingredients.append(item.unit).append(' ');
            ingredients.append(item.name);
        }
        String remainingSteps = String.join("\n\n", recipe.steps.subList(1, recipe.steps.size()));
        List<String> bodies = Arrays.asList(recipe.description, ingredients.toString(), recipe.steps.get(0), remainingSteps,
                "Save this recipe for later. Find the full method in Beyond Kitchen.");
        HorizontalScrollView scroller = new HorizontalScrollView(this);
        scroller.setHorizontalScrollBarEnabled(false);
        LinearLayout cards = row();
        for (int i = 0; i < labels.size(); i++) {
            LinearLayout card = column();
            card.setPadding(dp(18), dp(18), dp(18), dp(16));
            card.setBackground(shape(i == 0 || i == 4 ? GREEN : CARD, 19, 0xffe5e4da));
            int color = i == 0 || i == 4 ? Color.WHITE : INK;
            if (i == 0 || i == 4) {
                ImageView photo = recipePhoto(recipe);
                card.addView(photo, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, dp(150)));
            }
            TextView label = text(labels.get(i), 10, i == 0 || i == 4 ? GOLD : GREEN, true);
            card.addView(label, params(0, 16, 0, 9));
            card.addView(text(titles.get(i), 23, color, true), params(0, 0, 0, 12));
            TextView body = text(bodies.get(i), i == 1 ? 12 : 13, color, false);
            body.setLineSpacing(dp(4), 1f);
            card.addView(body, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, 0, 1));
            card.addView(text((i + 1) + " / 5  ·  Tap for full recipe", 10, color, true), params(0, 12, 0, 0));
            card.setOnClickListener(view -> showRecipe(recipe));
            LinearLayout.LayoutParams cardParams = new LinearLayout.LayoutParams(dp(265), dp(420));
            cardParams.setMargins(0, 0, dp(12), 0);
            cards.addView(card, cardParams);
        }
        scroller.addView(cards);
        return scroller;
    }

    private ImageView recipePhoto(Recipe recipe) {
        ImageView image = new ImageView(this);
        image.setScaleType(ImageView.ScaleType.CENTER_CROP);
        String fileName = recipe.image.substring(recipe.image.lastIndexOf('/') + 1);
        BitmapFactory.Options options = new BitmapFactory.Options();
        options.inSampleSize = 4;
        try (InputStream source = getAssets().open(fileName)) {
            image.setImageBitmap(BitmapFactory.decodeStream(source, null, options));
        } catch (IOException error) {
            image.setBackgroundColor(0xffe7ecdf);
            Log.e("BeyondKitchen", "Unable to load bundled recipe photo: " + fileName, error);
        }
        return image;
    }

    private void renderRecipes() {
        if (recipeList == null) return;
        recipeList.removeAllViews();
        String query = searchInput.getText().toString().trim().toLowerCase(Locale.ROOT);
        int count = 0;
        for (Recipe recipe : recipes) {
            if (savedOnly && !favorites.contains(recipe.id)) continue;
            if (!matchesFilter(recipe)) continue;
            if (!query.isEmpty() && !searchText(recipe).contains(query)) continue;
            recipeList.addView(recipeCard(recipe), params(0, 0, 0, 10));
            count++;
        }
        if (count == 0) {
            String message = savedOnly ? "Save a recipe to keep it close." : "No recipes match. Try another search.";
            recipeList.addView(text(message, 13, MUTED, false), params(0, 12, 0, 15));
        }
        sectionTitle.setText(savedOnly ? "Your saved recipes" : "Find your next favourite");
        savedButton.setText(savedOnly ? "All recipes" : "♡ Saved  " + favorites.size());
        savedButton.setTextColor(savedOnly ? Color.WHITE : GREEN);
        savedButton.setBackground(shape(savedOnly ? GREEN : CARD, 24, 0xffe5e4da));
    }

    private void refreshFilterChips() {
        for (TextView chip : filterChips) {
            boolean selected = chip.getText().toString().equals(filter);
            chip.setTextColor(selected ? Color.WHITE : GREEN);
            chip.setBackground(shape(selected ? GREEN : 0xffe7ecdf, 20, 0x00ffffff));
        }
    }

    private boolean matchesFilter(Recipe recipe) {
        if ("All".equals(filter)) return true;
        if ("Quick".equals(filter)) return recipe.timeMinutes < 30;
        if ("Vegetarian".equals(filter)) return recipe.tags.contains("Vegetarian");
        if ("Haitian".equals(filter)) return recipe.tags.contains("Haitian");
        return recipe.category.equals(filter);
    }

    private String searchText(Recipe recipe) {
        StringBuilder value = new StringBuilder(recipe.name).append(' ').append(recipe.description).append(' ').append(recipe.category);
        for (String tag : recipe.tags) value.append(' ').append(tag);
        for (Recipe.Ingredient ingredient : recipe.ingredients) value.append(' ').append(ingredient.name);
        return value.toString().toLowerCase(Locale.ROOT);
    }

    private View recipeCard(Recipe recipe) {
        LinearLayout card = row();
        card.setGravity(Gravity.CENTER_VERTICAL);
        card.setPadding(dp(13), dp(12), dp(9), dp(12));
        card.setBackground(shape(CARD, 17, 0xffe5e4da));
        LinearLayout copy = column();
        copy.addView(text(recipe.category.toUpperCase(Locale.ROOT), 9, GREEN, true), params(0, 0, 0, 4));
        copy.addView(text(recipe.name, 18, INK, false), params(0, 0, 0, 5));
        copy.addView(text(recipe.timeMinutes + " min  ·  " + (recipe.tags.isEmpty() ? recipe.difficulty : recipe.tags.get(0)), 10, MUTED, false));
        card.addView(copy, new LinearLayout.LayoutParams(0, ViewGroup.LayoutParams.WRAP_CONTENT, 1));
        TextView heart = text(favorites.contains(recipe.id) ? "♥" : "♡", 23, favorites.contains(recipe.id) ? 0xffa0463f : MUTED, false);
        heart.setGravity(Gravity.CENTER);
        card.addView(heart, new LinearLayout.LayoutParams(dp(42), dp(48)));
        heart.setOnClickListener(view -> toggleFavorite(recipe));
        card.setOnClickListener(view -> showRecipe(recipe));
        return card;
    }

    private void showRecipe(Recipe recipe) {
        final int[] servings = {recipe.servings};
        LinearLayout detail = column();
        detail.setPadding(dp(22), dp(12), dp(22), dp(6));
        TextView title = text(recipe.name, 27, INK, false);
        detail.addView(title, params(0, 0, 0, 8));
        detail.addView(text(recipe.description, 13, MUTED, false), params(0, 0, 0, 10));
        detail.addView(text(recipe.timeMinutes + " min  ·  " + recipe.difficulty, 11, GREEN, true), params(0, 0, 0, 18));
        detail.addView(text("WHAT YOU'LL NEED", 10, GREEN, true), params(0, 0, 0, 6));

        LinearLayout servingsRow = row();
        servingsRow.setGravity(Gravity.CENTER_VERTICAL);
        servingsRow.addView(text("Servings", 12, MUTED, true), new LinearLayout.LayoutParams(0, dp(39), 1));
        TextView quantity = text(String.valueOf(servings[0]), 13, INK, true);
        TextView minus = text("−", 22, GREEN, true);
        TextView plus = text("+", 22, GREEN, true);
        minus.setGravity(Gravity.CENTER);
        plus.setGravity(Gravity.CENTER);
        servingsRow.addView(minus, new LinearLayout.LayoutParams(dp(38), dp(38)));
        servingsRow.addView(quantity, new LinearLayout.LayoutParams(dp(30), dp(38)));
        servingsRow.addView(plus, new LinearLayout.LayoutParams(dp(38), dp(38)));
        detail.addView(servingsRow);

        LinearLayout ingredientList = column();
        detail.addView(ingredientList);
        Runnable updateIngredients = () -> {
            quantity.setText(String.valueOf(servings[0]));
            ingredientList.removeAllViews();
            DecimalFormat format = new DecimalFormat("#.#");
            for (Recipe.Ingredient ingredient : recipe.ingredients) {
                String amount = Double.isNaN(ingredient.amount) ? "" : format.format(ingredient.amount * servings[0] / recipe.servings);
                String line = ingredient.name + (amount.isEmpty() ? "" : "   " + amount + (ingredient.unit.isEmpty() ? "" : " " + ingredient.unit));
                TextView item = text(line, 12, MUTED, false);
                item.setPadding(0, dp(8), 0, dp(8));
                ingredientList.addView(item);
            }
        };
        updateIngredients.run();
        minus.setOnClickListener(view -> { servings[0] = Math.max(1, servings[0] - 1); updateIngredients.run(); });
        plus.setOnClickListener(view -> { servings[0] = Math.min(12, servings[0] + 1); updateIngredients.run(); });

        detail.addView(text("LET'S MAKE IT", 10, GREEN, true), params(0, 20, 0, 8));
        for (int i = 0; i < recipe.steps.size(); i++) {
            TextView step = text(String.format(Locale.ROOT, "%02d   %s", i + 1, recipe.steps.get(i)), 12, MUTED, false);
            step.setLineSpacing(dp(3), 1f);
            detail.addView(step, params(0, 0, 0, 14));
        }

        ScrollView content = new ScrollView(this);
        content.addView(detail);
        AlertDialog dialog = new AlertDialog.Builder(this)
                .setView(content)
                .setNegativeButton("Close", null)
                .setPositiveButton(favorites.contains(recipe.id) ? "Remove saved" : "Save recipe", (ignored, which) -> toggleFavorite(recipe))
                .create();
        dialog.show();
    }

    private void toggleFavorite(Recipe recipe) {
        if (!favorites.add(recipe.id)) favorites.remove(recipe.id);
        getSharedPreferences("beyond-kitchen", MODE_PRIVATE).edit().putStringSet("favorites", new HashSet<>(favorites)).apply();
        renderRecipes();
    }

    private void toggleSavedOnly() {
        savedOnly = !savedOnly;
        renderRecipes();
    }

    private LinearLayout column() {
        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        return layout;
    }

    private LinearLayout row() {
        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.HORIZONTAL);
        return layout;
    }

    private TextView text(String value, float size, int color, boolean bold) {
        TextView view = new TextView(this);
        view.setText(value);
        view.setTextSize(size);
        view.setTextColor(color);
        view.setTypeface(Typeface.create("sans-serif", bold ? Typeface.BOLD : Typeface.NORMAL));
        return view;
    }

    private GradientDrawable shape(int fill, int radius, int stroke) {
        GradientDrawable shape = new GradientDrawable();
        shape.setColor(fill);
        shape.setCornerRadius(dp(radius));
        if (Color.alpha(stroke) != 0) shape.setStroke(dp(1), stroke);
        return shape;
    }

    private LinearLayout.LayoutParams params(int left, int top, int right, int bottom) {
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT);
        params.setMargins(dp(left), dp(top), dp(right), dp(bottom));
        return params;
    }

    private int dp(int value) {
        return Math.round(value * getResources().getDisplayMetrics().density);
    }
}
