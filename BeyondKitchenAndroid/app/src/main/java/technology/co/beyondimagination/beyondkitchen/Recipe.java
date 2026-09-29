package technology.co.beyondimagination.beyondkitchen;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

final class Recipe {
    static final class Ingredient {
        final String name;
        final double amount;
        final String unit;

        Ingredient(JSONObject source) {
            name = source.optString("name");
            amount = source.optDouble("amount", Double.NaN);
            unit = source.optString("unit");
        }
    }

    final String id;
    final String name;
    final String description;
    final String category;
    final String image;
    final int timeMinutes;
    final String difficulty;
    final int servings;
    final List<String> tags;
    final List<Ingredient> ingredients;
    final List<String> steps;

    Recipe(JSONObject source) {
        id = source.optString("id");
        name = source.optString("name");
        description = source.optString("description");
        category = source.optString("category");
        image = source.optString("image");
        timeMinutes = source.optInt("timeMinutes");
        difficulty = source.optString("difficulty");
        servings = source.optInt("servings");
        tags = strings(source.optJSONArray("tags"));
        ingredients = ingredients(source.optJSONArray("ingredients"));
        steps = strings(source.optJSONArray("steps"));
    }

    private static List<String> strings(JSONArray values) {
        List<String> result = new ArrayList<>();
        if (values != null) {
            for (int i = 0; i < values.length(); i++) result.add(values.optString(i));
        }
        return result;
    }

    private static List<Ingredient> ingredients(JSONArray values) {
        List<Ingredient> result = new ArrayList<>();
        if (values != null) {
            for (int i = 0; i < values.length(); i++) {
                JSONObject value = values.optJSONObject(i);
                if (value != null) result.add(new Ingredient(value));
            }
        }
        return result;
    }
}
