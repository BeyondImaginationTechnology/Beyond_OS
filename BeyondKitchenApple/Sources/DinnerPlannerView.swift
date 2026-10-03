import Foundation
import SwiftUI
import UIKit

private struct DinnerSuggestion: Decodable {
    struct Cook: Decodable {
        let recipeId: String
        let name: String
        let minutes: Int
        let why: String
    }

    struct Order: Decodable {
        let dish: String
        let why: String
    }

    let ok: Bool
    let cook: Cook
    let pickup: Order
    let delivery: Order
}

private struct DinnerServiceError: Decodable {
    let error: String?
}

struct DinnerPlannerView: View {
    let recipes: [Recipe]
    let fallbackRecipe: Recipe
    let openRecipe: (Recipe) -> Void

    @State private var prompt = ""
    @State private var loading = false
    @State private var suggestion: DinnerSuggestion?
    @State private var message: String?

    private let hints: [(label: String, value: String)] = [
        ("20 min", "under 20 minutes"),
        ("Under $25", "under $25"),
        ("Low energy", "low energy"),
        ("Spicy", "spicy"),
        ("Vegetarian", "vegetarian")
    ]

    init(recipes: [Recipe], fallbackRecipe: Recipe, openRecipe: @escaping (Recipe) -> Void) {
        self.recipes = recipes
        self.fallbackRecipe = fallbackRecipe
        self.openRecipe = openRecipe
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 13) {
            Text("DINNER GUIDE · BETA 0.0.1")
                .font(.system(size: 10, weight: .bold, design: .rounded))
                .tracking(1.3)
                .foregroundStyle(KitchenStyle.green)
            Text("What's for dinner?")
                .font(.system(size: 29, weight: .medium, design: .serif))
                .foregroundStyle(KitchenStyle.ink)
            Text("Tell us your mood, time, budget, or what's in the fridge.")
                .font(.system(size: 12))
                .foregroundStyle(KitchenStyle.muted)
            TextField("I'm tired, want something spicy, and have about $25…", text: $prompt, axis: .vertical)
                .lineLimit(2...4)
                .font(.system(size: 13))
                .padding(13)
                .background(.white, in: RoundedRectangle(cornerRadius: 13))
                .overlay(RoundedRectangle(cornerRadius: 13).stroke(KitchenStyle.green.opacity(0.2)))
                .onChange(of: prompt) { _, value in
                    if value.count > 400 { prompt = String(value.prefix(400)) }
                }
            ScrollView(.horizontal, showsIndicators: false) {
                HStack(spacing: 7) {
                    ForEach(Array(hints.enumerated()), id: \.offset) { entry in
                        Button(entry.element.label) {
                            let existing = prompt.trimmingCharacters(in: .whitespacesAndNewlines)
                            prompt = String((existing + (existing.isEmpty ? "" : ", ") + entry.element.value).prefix(400))
                        }
                        .font(.system(size: 11, weight: .semibold))
                        .padding(.horizontal, 11)
                        .padding(.vertical, 8)
                        .foregroundStyle(KitchenStyle.green)
                        .background(.white, in: Capsule())
                    }
                }
            }
            Button {
                Task { await suggestDinner() }
            } label: {
                Label(loading ? "Finding ideas…" : "Find dinner ideas", systemImage: "arrow.right")
                    .font(.system(size: 12, weight: .bold))
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, 14)
                    .foregroundStyle(.white)
                    .background(KitchenStyle.green, in: Capsule())
            }
            .buttonStyle(.plain)
            .disabled(loading)
            Text("Pickup and delivery are dish ideas. Check nearby menus and availability.")
                .font(.system(size: 10))
                .foregroundStyle(KitchenStyle.muted)

            if let message {
                Text(message)
                    .font(.system(size: 12))
                    .foregroundStyle(KitchenStyle.muted)
            }
            if let suggestion {
                let cookRecipe = recipes.first { $0.id == suggestion.cook.recipeId } ?? fallbackRecipe
                ideaCard("COOK", suggestion.cook.name, "\(suggestion.cook.why) · \(suggestion.cook.minutes) min", action: "Open recipe") {
                    openRecipe(cookRecipe)
                }
                ideaCard("PICK UP", suggestion.pickup.dish, suggestion.pickup.why, action: "Search nearby pickup") {
                    openSearch("https://www.google.com/maps/search/", queryName: "query", query: "\(suggestion.pickup.dish) pickup near me", maps: true)
                }
                ideaCard("DELIVER", suggestion.delivery.dish, suggestion.delivery.why, action: "Search delivery") {
                    openSearch("https://www.google.com/search", queryName: "q", query: "\(suggestion.delivery.dish) delivery near me")
                }
            } else if message != nil {
                ideaCard("A RECIPE FOR NOW", fallbackRecipe.name, fallbackRecipe.description, action: "Open recipe") {
                    openRecipe(fallbackRecipe)
                }
            }
        }
        .padding(19)
        .background(Color(red: 0.933, green: 0.949, blue: 0.914), in: RoundedRectangle(cornerRadius: 19))
    }

    private func ideaCard(_ label: String, _ title: String, _ why: String, action: String, perform: @escaping () -> Void) -> some View {
        Button(action: perform) {
            VStack(alignment: .leading, spacing: 8) {
                Text(label)
                    .font(.system(size: 10, weight: .bold, design: .rounded))
                    .tracking(1)
                    .foregroundStyle(KitchenStyle.green)
                Text(title)
                    .font(.system(size: 21, weight: .medium, design: .serif))
                    .foregroundStyle(KitchenStyle.ink)
                Text(why)
                    .font(.system(size: 12))
                    .foregroundStyle(KitchenStyle.muted)
                Text("\(action) →")
                    .font(.system(size: 12, weight: .bold))
                    .foregroundStyle(KitchenStyle.green)
                    .padding(.top, 3)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            .padding(15)
            .background(.white, in: RoundedRectangle(cornerRadius: 15))
        }
        .buttonStyle(.plain)
    }

    private func openSearch(_ address: String, queryName: String, query: String, maps: Bool = false) {
        guard var parts = URLComponents(string: address) else { return }
        parts.queryItems = (maps ? [URLQueryItem(name: "api", value: "1")] : []) + [URLQueryItem(name: queryName, value: query)]
        if let url = parts.url { UIApplication.shared.open(url) }
    }

    @MainActor
    private func suggestDinner() async {
        loading = true
        message = nil
        suggestion = nil
        defer { loading = false }
        do {
            let url = URL(string: "https://recipe.beyondimagination.co.technology/api/dinner.php")!
            var request = URLRequest(url: url)
            request.httpMethod = "POST"
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
            request.timeoutInterval = 43
            let text = prompt.trimmingCharacters(in: .whitespacesAndNewlines)
            request.httpBody = try JSONEncoder().encode(["prompt": text.isEmpty ? "Surprise me with a good dinner tonight." : text])
            let (data, response) = try await URLSession.shared.data(for: request)
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
                let problem = try? JSONDecoder().decode(DinnerServiceError.self, from: data)
                message = problem?.error ?? "AI dinner ideas are unavailable right now. Here's a recipe for today."
                return
            }
            let result = try JSONDecoder().decode(DinnerSuggestion.self, from: data)
            guard result.ok else { throw URLError(.badServerResponse) }
            suggestion = result
        } catch {
            message = "AI dinner ideas are unavailable right now. Here's a recipe for today."
        }
    }
}
