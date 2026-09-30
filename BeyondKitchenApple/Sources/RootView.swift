import SwiftUI
import UIKit

enum KitchenStyle {
    static let paper = Color(red: 0.965, green: 0.961, blue: 0.937)
    static let ink = Color(red: 0.157, green: 0.192, blue: 0.145)
    static let green = Color(red: 0.251, green: 0.357, blue: 0.259)
    static let muted = Color(red: 0.45, green: 0.48, blue: 0.42)
    static let gold = Color(red: 0.86, green: 0.75, blue: 0.46)
}

private enum KitchenTab: Hashable {
    case discover, saved
}

struct KitchenRootView: View {
    @AppStorage("beyond.kitchen.favoriteIds") private var savedIDs = ""
    @State private var tab: KitchenTab = .discover
    private let recipes = Recipe.load()

    private var favorites: Set<String> {
        Set(savedIDs.split(separator: ",").map(String.init))
    }

    var body: some View {
        TabView(selection: $tab) {
            RecipeLibraryView(recipes: recipes, savedIDs: $savedIDs)
                .tabItem { Label("Discover", systemImage: "sparkles") }
                .tag(KitchenTab.discover)
            RecipeLibraryView(recipes: recipes, savedIDs: $savedIDs, savedOnly: true)
                .tabItem { Label("Saved", systemImage: "heart.fill") }
                .tag(KitchenTab.saved)
        }
        .tint(KitchenStyle.green)
    }
}

private struct RecipeLibraryView: View {
    let recipes: [Recipe]
    @Binding var savedIDs: String
    var savedOnly = false
    @State private var query = ""
    @State private var filter = "All"
    @State private var selected: Recipe?
    private let filters = ["All", "Quick", "Vegetarian", "Dinner", "Haitian"]

    private var saved: Set<String> { Set(savedIDs.split(separator: ",").map(String.init)) }
    private var featured: Recipe? {
        guard !recipes.isEmpty else { return nil }
        var localCalendar = Calendar(identifier: .gregorian)
        localCalendar.timeZone = .current
        var utcCalendar = Calendar(identifier: .gregorian)
        utcCalendar.timeZone = TimeZone(secondsFromGMT: 0) ?? .current
        var localDay = localCalendar.dateComponents([.year, .month, .day], from: .now)
        localDay.calendar = utcCalendar
        localDay.timeZone = utcCalendar.timeZone
        let dayStart = utcCalendar.date(from: localDay) ?? .now
        let day = Int(dayStart.timeIntervalSince1970 / 86_400)
        return recipes[((day % recipes.count) + recipes.count) % recipes.count]
    }
    private var visibleRecipes: [Recipe] {
        recipes.filter { recipe in
            let matchesQuery = query.isEmpty || ([recipe.name, recipe.description, recipe.category] + recipe.tags + recipe.ingredients.map(\.name))
                .joined(separator: " ").localizedCaseInsensitiveContains(query)
            let matchesFilter = filter == "All"
                || (filter == "Quick" && recipe.timeMinutes < 30)
                || (filter == "Vegetarian" && recipe.tags.contains("Vegetarian"))
                || (filter == "Haitian" && recipe.tags.contains("Haitian"))
                || recipe.category == filter
            return matchesQuery && matchesFilter && (!savedOnly || saved.contains(recipe.id))
        }
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                LazyVStack(alignment: .leading, spacing: 22) {
                    if savedOnly {
                        heading("Your saved recipes", subtitle: "Little favourites, ready when you are.")
                    } else {
                        heading("Good food,\nmade simple.", subtitle: "A fresh recipe and a little inspiration for today.")
                        if let featured {
                            FeaturedRecipeCard(recipe: featured) { selected = featured }
                            DinnerPlannerView(recipes: recipes, fallbackRecipe: featured) { selected = $0 }
                            DailyCarouselView(recipe: featured) { selected = featured }
                        }
                    }

                    if !savedOnly {
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 8) {
                                ForEach(filters, id: \.self) { option in
                                    Button(option) { filter = option }
                                        .font(.system(size: 12, weight: .semibold))
                                        .padding(.horizontal, 14)
                                        .padding(.vertical, 9)
                                        .foregroundStyle(filter == option ? .white : KitchenStyle.muted)
                                        .background(filter == option ? KitchenStyle.green : .white, in: Capsule())
                                        .overlay(Capsule().stroke(KitchenStyle.green.opacity(filter == option ? 0 : 0.12)))
                                        .buttonStyle(.plain)
                                }
                            }
                        }
                    }

                    if recipes.isEmpty {
                        ContentUnavailableView("Recipes unavailable", systemImage: "fork.knife", description: Text("The recipe library could not be loaded."))
                    } else if visibleRecipes.isEmpty {
                        ContentUnavailableView(savedOnly ? "Nothing saved yet" : "No recipes found", systemImage: savedOnly ? "heart" : "magnifyingglass", description: Text(savedOnly ? "Save a recipe to keep it close." : "Try another search or filter."))
                    } else {
                        LazyVStack(spacing: 12) {
                            ForEach(visibleRecipes) { recipe in
                                RecipeRow(recipe: recipe, isSaved: saved.contains(recipe.id), toggleSave: { toggle(recipe) }) {
                                    selected = recipe
                                }
                            }
                        }
                    }
                }
                .padding(.horizontal, 18)
                .padding(.top, 14)
                .padding(.bottom, 28)
            }
            .background(KitchenStyle.paper.ignoresSafeArea())
            .navigationTitle("Beyond Kitchen")
            .navigationBarTitleDisplayMode(.inline)
            .searchable(text: $query, prompt: "Recipes or ingredients")
            .sheet(item: $selected) { recipe in
                RecipeDetailView(recipe: recipe, savedIDs: $savedIDs)
                    .presentationDetents([.large])
                    .presentationDragIndicator(.visible)
            }
        }
        .onChange(of: savedIDs) { _, value in
            if savedOnly && value.isEmpty { query = "" }
        }
    }

    private func heading(_ title: String, subtitle: String) -> some View {
        VStack(alignment: .leading, spacing: 9) {
            Text(savedOnly ? "YOUR LITTLE RECIPE BOX" : "A FRESH START, EVERY DAY")
                .font(.system(size: 10, weight: .bold, design: .rounded))
                .tracking(1.7)
                .foregroundStyle(KitchenStyle.green)
            Text(title)
                .font(.system(size: savedOnly ? 35 : 42, weight: .medium, design: .serif))
                .tracking(-1.2)
                .lineSpacing(-2)
                .foregroundStyle(KitchenStyle.ink)
                .fixedSize(horizontal: false, vertical: true)
            Text(subtitle)
                .font(.system(size: 14))
                .lineSpacing(4)
                .foregroundStyle(KitchenStyle.muted)
        }
        .padding(.top, 8)
    }

    private func toggle(_ recipe: Recipe) {
        var ids = saved
        if !ids.insert(recipe.id).inserted { ids.remove(recipe.id) }
        savedIDs = ids.sorted().joined(separator: ",")
    }
}

private struct FeaturedRecipeCard: View {
    let recipe: Recipe
    let open: () -> Void

    var body: some View {
        Button(action: open) {
            VStack(alignment: .leading, spacing: 0) {
                ZStack(alignment: .bottomLeading) {
                    RoundedRectangle(cornerRadius: 21, style: .continuous)
                        .fill(LinearGradient(colors: [KitchenStyle.green, Color(red: 0.51, green: 0.55, blue: 0.38)], startPoint: .topLeading, endPoint: .bottomTrailing))
                    Image(systemName: "leaf.circle.fill")
                        .font(.system(size: 120, weight: .ultraLight))
                        .foregroundStyle(.white.opacity(0.12))
                        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topTrailing)
                        .padding(18)
                    VStack(alignment: .leading, spacing: 12) {
                        Label("TODAY'S RECIPE", systemImage: "sparkle")
                            .font(.system(size: 10, weight: .bold, design: .rounded))
                            .tracking(1.3)
                            .foregroundStyle(KitchenStyle.gold)
                        Text(recipe.name)
                            .font(.system(size: 31, weight: .medium, design: .serif))
                            .tracking(-0.6)
                            .foregroundStyle(.white)
                        Text(recipe.description)
                            .font(.system(size: 12))
                            .lineSpacing(3)
                            .foregroundStyle(.white.opacity(0.8))
                            .lineLimit(3)
                        Label("\(recipe.timeMinutes) min  ·  \(recipe.difficulty)", systemImage: "clock")
                            .font(.system(size: 11, weight: .semibold))
                            .foregroundStyle(.white.opacity(0.9))
                    }
                    .padding(23)
                }
                .frame(minHeight: 260)
            }
            .clipShape(RoundedRectangle(cornerRadius: 21, style: .continuous))
            .shadow(color: KitchenStyle.green.opacity(0.16), radius: 18, y: 9)
        }
        .buttonStyle(.plain)
        .accessibilityHint("Opens ingredients and cooking steps")
    }
}

private struct DailyCarouselView: View {
    let recipe: Recipe
    let open: () -> Void

    private var photo: UIImage? {
        let file = URL(fileURLWithPath: recipe.image)
        let name = file.deletingPathExtension().lastPathComponent
        let extensionName = file.pathExtension
        guard let url = Bundle.main.url(forResource: name, withExtension: extensionName, subdirectory: "recipes")
            ?? Bundle.main.url(forResource: name, withExtension: extensionName) else { return nil }
        return UIImage(contentsOfFile: url.path)
    }

    private var slides: [(label: String, title: String, body: String)] {
        let ingredients = recipe.ingredients.map { item in
            [String(format: "%g", item.amount), item.unit, item.name].filter { !$0.isEmpty }.joined(separator: " ")
        }.joined(separator: "\n")
        return [
            ("TODAY'S RECIPE", recipe.name, recipe.description),
            ("WHAT YOU'LL NEED", "The ingredients", ingredients),
            ("LET'S MAKE IT · 1", "Get started", recipe.steps.first ?? ""),
            ("LET'S MAKE IT · 2", "Bring it together", recipe.steps.dropFirst().joined(separator: "\n\n")),
            ("MAKE IT YOUR OWN", "Ready to enjoy", "Save this recipe for later. Find the full method in Beyond Kitchen.")
        ]
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("TODAY'S CAROUSEL")
                .font(.system(size: 10, weight: .bold, design: .rounded))
                .tracking(1.5)
                .foregroundStyle(KitchenStyle.green)
            Text("From ingredients to the finished plate")
                .font(.system(size: 23, weight: .medium, design: .serif))
                .foregroundStyle(KitchenStyle.ink)
            ScrollView(.horizontal, showsIndicators: false) {
                HStack(alignment: .top, spacing: 12) {
                    ForEach(Array(slides.enumerated()), id: \.offset) { entry in
                        let index = entry.offset
                        let slide = entry.element
                        Button(action: open) {
                            VStack(alignment: .leading, spacing: 11) {
                                if (index == 0 || index == 4), let photo {
                                    Image(uiImage: photo)
                                        .resizable()
                                        .scaledToFill()
                                        .frame(width: 238, height: 126)
                                        .clipped()
                                        .clipShape(RoundedRectangle(cornerRadius: 12))
                                }
                                Text(slide.label)
                                    .font(.system(size: 10, weight: .bold, design: .rounded))
                                    .tracking(1)
                                    .foregroundStyle(index == 0 || index == 4 ? KitchenStyle.gold : KitchenStyle.green)
                                Text(slide.title)
                                    .font(.system(size: 24, weight: .medium, design: .serif))
                                    .foregroundStyle(index == 0 || index == 4 ? .white : KitchenStyle.ink)
                                Text(slide.body)
                                    .font(.system(size: index == 1 ? 12 : 13))
                                    .lineSpacing(index == 1 ? 3 : 5)
                                    .foregroundStyle(index == 0 || index == 4 ? .white.opacity(0.9) : KitchenStyle.muted)
                                Spacer(minLength: 0)
                                Text("\(index + 1) / 5  ·  Tap for full recipe")
                                    .font(.system(size: 10, weight: .semibold))
                                    .foregroundStyle(index == 0 || index == 4 ? .white.opacity(0.8) : KitchenStyle.muted)
                            }
                            .padding(16)
                            .frame(width: 270, height: 405, alignment: .topLeading)
                            .background(index == 0 || index == 4 ? KitchenStyle.green : .white, in: RoundedRectangle(cornerRadius: 19))
                            .overlay(RoundedRectangle(cornerRadius: 19).stroke(KitchenStyle.ink.opacity(0.06)))
                        }
                        .buttonStyle(.plain)
                        .accessibilityLabel("Slide \(index + 1) of 5. \(slide.label). \(slide.title). \(slide.body)")
                    }
                }
                .padding(.trailing, 18)
            }
        }
    }
}

private struct RecipeRow: View {
    let recipe: Recipe
    let isSaved: Bool
    let toggleSave: () -> Void
    let open: () -> Void

    var body: some View {
        HStack(spacing: 13) {
            Button(action: open) {
                ZStack {
                    RoundedRectangle(cornerRadius: 15, style: .continuous)
                        .fill(LinearGradient(colors: [KitchenStyle.green.opacity(0.18), KitchenStyle.gold.opacity(0.25)], startPoint: .topLeading, endPoint: .bottomTrailing))
                    Image(systemName: icon)
                        .font(.system(size: 23, weight: .medium))
                        .foregroundStyle(KitchenStyle.green)
                }
                .frame(width: 62, height: 67)
            }
            .buttonStyle(.plain)
            VStack(alignment: .leading, spacing: 5) {
                Text(recipe.category.uppercased())
                    .font(.system(size: 9, weight: .bold))
                    .tracking(1)
                    .foregroundStyle(KitchenStyle.green)
                Button(action: open) {
                    Text(recipe.name)
                        .font(.system(size: 18, weight: .medium, design: .serif))
                        .foregroundStyle(KitchenStyle.ink)
                        .lineLimit(1)
                }
                .buttonStyle(.plain)
                Text("\(recipe.timeMinutes) min  ·  \(recipe.tags.first ?? recipe.difficulty)")
                    .font(.system(size: 10, weight: .medium))
                    .foregroundStyle(KitchenStyle.muted)
            }
            Spacer(minLength: 2)
            Button(action: toggleSave) {
                Image(systemName: isSaved ? "heart.fill" : "heart")
                    .font(.system(size: 17, weight: .medium))
                    .foregroundStyle(isSaved ? Color(red: 0.68, green: 0.3, blue: 0.26) : KitchenStyle.muted)
                    .frame(width: 38, height: 42)
                    .contentShape(Rectangle())
            }
            .buttonStyle(.plain)
            .accessibilityLabel(isSaved ? "Remove \(recipe.name) from saved recipes" : "Save \(recipe.name)")
        }
        .padding(11)
        .background(.white, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
        .overlay(RoundedRectangle(cornerRadius: 18).stroke(KitchenStyle.ink.opacity(0.06)))
    }

    private var icon: String {
        switch recipe.category {
        case "Breakfast": "sun.max"
        case "Dessert": "birthday.cake"
        case "Lunch": "leaf"
        default: "fork.knife"
        }
    }
}

private struct RecipeDetailView: View {
    let recipe: Recipe
    @Binding var savedIDs: String
    @State private var servings: Int

    init(recipe: Recipe, savedIDs: Binding<String>) {
        self.recipe = recipe
        _savedIDs = savedIDs
        _servings = State(initialValue: recipe.servings)
    }

    private var saved: Bool { Set(savedIDs.split(separator: ",").map(String.init)).contains(recipe.id) }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 24) {
                    VStack(alignment: .leading, spacing: 12) {
                        Text(recipe.category.uppercased())
                            .font(.system(size: 10, weight: .bold))
                            .tracking(1.5)
                            .foregroundStyle(KitchenStyle.green)
                        Text(recipe.name)
                            .font(.system(size: 37, weight: .medium, design: .serif))
                            .tracking(-1)
                            .foregroundStyle(KitchenStyle.ink)
                        Text(recipe.description)
                            .font(.system(size: 14))
                            .lineSpacing(5)
                            .foregroundStyle(KitchenStyle.muted)
                        Label("\(recipe.timeMinutes) min  ·  \(recipe.difficulty)", systemImage: "clock")
                            .font(.system(size: 12, weight: .semibold))
                            .foregroundStyle(KitchenStyle.green)
                    }
                    .padding(20)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .background(KitchenStyle.paper, in: RoundedRectangle(cornerRadius: 20))

                    HStack {
                        Text("What you'll need").font(.system(size: 25, weight: .medium, design: .serif))
                        Spacer()
                        HStack(spacing: 11) {
                            Button { servings = max(1, servings - 1) } label: { Image(systemName: "minus.circle") }
                            Text("\(servings)").font(.system(size: 13, weight: .bold)).monospacedDigit()
                            Button { servings = min(12, servings + 1) } label: { Image(systemName: "plus.circle") }
                        }
                        .foregroundStyle(KitchenStyle.green)
                        .buttonStyle(.plain)
                    }
                    VStack(spacing: 0) {
                        ForEach(recipe.ingredients) { ingredient in
                            HStack(alignment: .firstTextBaseline) {
                                Text(ingredient.name).foregroundStyle(KitchenStyle.ink)
                                Spacer(minLength: 10)
                                Text(formattedAmount(ingredient))
                                    .fontWeight(.semibold)
                                    .foregroundStyle(KitchenStyle.green)
                            }
                            .font(.system(size: 12))
                            .padding(.vertical, 11)
                            if ingredient.id != recipe.ingredients.last?.id {
                                Divider().overlay(KitchenStyle.ink.opacity(0.07))
                            }
                        }
                    }

                    Text("Let's make it")
                        .font(.system(size: 25, weight: .medium, design: .serif))
                    VStack(alignment: .leading, spacing: 17) {
                        ForEach(Array(recipe.steps.enumerated()), id: \.offset) { item in
                            HStack(alignment: .top, spacing: 13) {
                                Text(String(format: "%02d", item.offset + 1))
                                    .font(.system(size: 11, weight: .bold, design: .rounded))
                                    .foregroundStyle(KitchenStyle.green)
                                    .padding(.top, 2)
                                Text(item.element)
                                    .font(.system(size: 13))
                                    .lineSpacing(4)
                                    .foregroundStyle(KitchenStyle.muted)
                            }
                        }
                    }
                    Button(action: toggleSaved) {
                        Label(saved ? "Saved to your recipe box" : "Save this recipe", systemImage: saved ? "heart.fill" : "heart")
                            .font(.system(size: 13, weight: .bold))
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 14)
                            .foregroundStyle(.white)
                            .background(KitchenStyle.green, in: Capsule())
                    }
                    .buttonStyle(.plain)
                }
                .padding(20)
                .padding(.bottom, 24)
            }
            .background(.white)
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button { toggleSaved() } label: {
                        Image(systemName: saved ? "heart.fill" : "heart")
                            .foregroundStyle(saved ? Color.red : KitchenStyle.green)
                    }
                    .accessibilityLabel(saved ? "Remove from saved recipes" : "Save recipe")
                }
            }
        }
        .tint(KitchenStyle.green)
    }

    private func toggleSaved() {
        var ids = Set(savedIDs.split(separator: ",").map(String.init))
        if !ids.insert(recipe.id).inserted { ids.remove(recipe.id) }
        savedIDs = ids.sorted().joined(separator: ",")
    }

    private func formattedAmount(_ ingredient: Recipe.Ingredient) -> String {
        let amount = ingredient.amount * Double(servings) / Double(recipe.servings)
        let quantity = amount.rounded() == amount ? String(Int(amount)) : String(format: "%.1f", amount)
        return [quantity, ingredient.unit].filter { !$0.isEmpty }.joined(separator: " ")
    }
}
