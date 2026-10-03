import SwiftUI

struct MealPlannerView: View {
    @EnvironmentObject private var store: HealthStore
    @State private var weekOffset = 0
    @State private var selectedRecipe: KitchenRecipe?
    private let recipes = KitchenRecipe.load()
    private let prepOptions = [5, 10, 15, 20, 30, 45]

    private var calendar: Calendar {
        var value = Calendar(identifier: .iso8601)
        value.timeZone = .current
        return value
    }

    private var weekStart: Date {
        let current = calendar.dateInterval(of: .weekOfYear, for: .now)?.start ?? .now
        return calendar.date(byAdding: .weekOfYear, value: weekOffset, to: current) ?? current
    }

    private var days: [Date] {
        (0..<7).compactMap { calendar.date(byAdding: .day, value: $0, to: weekStart) }
    }

    private var recipesByID: [String: KitchenRecipe] {
        Dictionary(uniqueKeysWithValues: recipes.map { ($0.id, $0) })
    }

    var body: some View {
        HealthScreen(title: "Meal calendar") {
            VStack(alignment: .leading, spacing: 6) {
                Text("Plan a little nourishment.").font(.largeTitle.bold()).foregroundStyle(.healthInk)
                Text("Breakfast ideas based on your available morning prep time, from Beyond Kitchen.")
                    .foregroundStyle(.secondary)
            }

            HealthCard {
                HStack {
                    Text("\(weekStart.formatted(.dateTime.month(.abbreviated).day())) – \(days.last?.formatted(.dateTime.month(.abbreviated).day()) ?? "")")
                        .font(.headline)
                    Spacer()
                    Button { weekOffset -= 1 } label: { Image(systemName: "chevron.left") }
                        .accessibilityLabel("Previous week")
                    Button("Today") { weekOffset = 0 }
                    Button { weekOffset += 1 } label: { Image(systemName: "chevron.right") }
                        .accessibilityLabel("Next week")
                }
            }

            if recipes.isEmpty {
                HealthCard { Text("Beyond Kitchen recipes are unavailable in this build.") }
            } else {
                ForEach(days, id: \.self) { day in dayCard(day) }
                groceryCard
            }
        }
        .sheet(item: $selectedRecipe) { recipe in
            NavigationStack {
                ScrollView {
                    VStack(alignment: .leading, spacing: 20) {
                        Text(recipe.description).foregroundStyle(.secondary)
                        Label("\(recipe.timeMinutes) min · \(recipe.servings) servings", systemImage: "clock")
                        Text("Ingredients").font(.headline)
                        ForEach(recipe.ingredients.indices, id: \.self) { index in
                            let item = recipe.ingredients[index]
                            Text("\(item.amount.formatted()) \(item.unit) \(item.name)")
                        }
                        Text("Steps").font(.headline)
                        ForEach(recipe.steps.indices, id: \.self) { index in
                            Text("\(index + 1). \(recipe.steps[index])")
                        }
                    }
                    .padding()
                }
                .navigationTitle(recipe.name)
                .navigationBarTitleDisplayMode(.inline)
                .toolbar { Button("Done") { selectedRecipe = nil } }
            }
        }
    }

    private func dayCard(_ day: Date) -> some View {
        let minutes = store.prepTime(on: day)
        let suggested = recipes
            .filter { $0.category == "Breakfast" && $0.timeMinutes <= minutes }
            .sorted { $0.timeMinutes > $1.timeMinutes }
            .first

        return HealthCard {
            Text(day.formatted(.dateTime.weekday(.wide).month(.abbreviated).day()))
                .font(.title3.bold()).foregroundStyle(.healthInk)
            Picker("Morning prep time", selection: Binding(
                get: { store.prepTime(on: day) },
                set: { store.setPrepTime($0, on: day) }
            )) {
                ForEach(prepOptions, id: \.self) { value in Text("\(value) minutes").tag(value) }
            }
            .pickerStyle(.menu)
            if let suggested {
                HStack {
                    Text("Fits your morning: \(suggested.name) · \(suggested.timeMinutes) min")
                        .font(.footnote).foregroundStyle(.secondary)
                    Spacer()
                }
                if store.recipeID(on: day, slot: .breakfast) != suggested.id {
                    Button("Add suggested breakfast") {
                        store.setMeal(on: day, slot: .breakfast, recipeID: suggested.id)
                    }
                    .buttonStyle(.bordered)
                    .tint(.healthGreen)
                }
            } else {
                Text("No breakfast recipe fits this prep window yet.")
                    .font(.footnote).foregroundStyle(.secondary)
            }
            ForEach(MealSlot.allCases) { slot in
                Picker(slot.rawValue, selection: Binding(
                    get: { store.recipeID(on: day, slot: slot) },
                    set: { store.setMeal(on: day, slot: slot, recipeID: $0) }
                )) {
                    Text("Choose a recipe").tag("")
                    ForEach(recipes) { recipe in
                        Text("\(recipe.name) · \(recipe.timeMinutes) min").tag(recipe.id)
                    }
                }
                .pickerStyle(.menu)
                if let recipe = recipesByID[store.recipeID(on: day, slot: slot)] {
                    Button("See \(recipe.name) recipe") { selectedRecipe = recipe }
                        .font(.footnote)
                }
            }
        }
    }

    private var groceryCard: some View {
        var totals: [String: (name: String, unit: String, amount: Double)] = [:]
        for day in days {
            for slot in MealSlot.allCases {
                guard let recipe = recipesByID[store.recipeID(on: day, slot: slot)] else { continue }
                for item in recipe.ingredients {
                    let key = "\(item.name.lowercased())|\(item.unit.lowercased())"
                    let previous = totals[key]
                    totals[key] = (item.name, item.unit, (previous?.amount ?? 0) + item.amount)
                }
            }
        }
        return HealthCard {
            Text("Grocery list").font(.headline)
            Text("Ingredients for this week’s planned recipes, at their default servings.")
                .font(.footnote).foregroundStyle(.secondary)
            if totals.isEmpty {
                Text("Choose a recipe to build your list.").foregroundStyle(.secondary)
            } else {
                ForEach(totals.keys.sorted(), id: \.self) { key in
                    if let item = totals[key] {
                        Text("\(item.amount.formatted()) \(item.unit) \(item.name)")
                    }
                }
            }
        }
    }
}
