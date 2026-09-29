import Foundation

struct Recipe: Identifiable, Decodable, Hashable {
    struct Ingredient: Decodable, Hashable, Identifiable {
        let name: String
        let amount: Double
        let unit: String

        var id: String { "\(name)-\(unit)" }
    }

    let id: String
    let name: String
    let description: String
    let category: String
    let timeMinutes: Int
    let difficulty: String
    let servings: Int
    let tags: [String]
    let imageAlt: String
    let ingredients: [Ingredient]
    let steps: [String]

    static func load() -> [Recipe] {
        guard let url = Bundle.main.url(forResource: "recipes", withExtension: "json") else {
            NSLog("Beyond Kitchen recipe catalog is missing from the app bundle.")
            return []
        }
        do {
            let data = try Data(contentsOf: url)
            return try JSONDecoder().decode([Recipe].self, from: data)
        } catch {
            NSLog("Beyond Kitchen recipe catalog could not be decoded: %@", error.localizedDescription)
            return []
        }
    }
}
