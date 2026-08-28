import Foundation

@MainActor
final class BabyNameStore: ObservableObject {
    @Published private(set) var favorites: Set<String>
    @Published private(set) var partnerLikes: Set<String>
    @Published private(set) var choices: [String: SwipeChoice]
    @Published var preferredVibes: Set<String>
    @Published var partnerName: String

    private let defaults: UserDefaults
    private enum Key {
        static let favorites = "beyond.baby.favorites"
        static let partnerLikes = "beyond.baby.partnerLikes"
        static let choices = "beyond.baby.choices"
        static let vibes = "beyond.baby.vibes"
        static let partnerName = "beyond.baby.partnerName"
    }

    init(defaults: UserDefaults = .standard) {
        self.defaults = defaults
        favorites = Set(defaults.stringArray(forKey: Key.favorites) ?? [])
        partnerLikes = Set(defaults.stringArray(forKey: Key.partnerLikes) ?? ["Luna", "Ezra", "Kai", "Elsa"])
        preferredVibes = Set(defaults.stringArray(forKey: Key.vibes) ?? ["Rare", "Gentle"])
        partnerName = defaults.string(forKey: Key.partnerName) ?? "My partner"
        if let data = defaults.data(forKey: Key.choices),
           let value = try? JSONDecoder().decode([String: SwipeChoice].self, from: data) {
            choices = value
        } else {
            choices = [:]
        }
    }

    var favoriteNames: [BabyName] { NameLibrary.all.filter { favorites.contains($0.id) } }
    var matches: [BabyName] { NameLibrary.all.filter { favorites.contains($0.id) && partnerLikes.contains($0.id) } }
    var inviteCode: String { "BBN-" + String(String(partnerName.hashValue.magnitude, radix: 36).uppercased().prefix(5)) }

    func toggleFavorite(_ name: BabyName) {
        if favorites.contains(name.id) { favorites.remove(name.id) } else { favorites.insert(name.id) }
        defaults.set(Array(favorites), forKey: Key.favorites)
    }

    func choose(_ choice: SwipeChoice, for name: BabyName) {
        choices[name.id] = choice
        if choice == .love { favorites.insert(name.id) }
        defaults.set(Array(favorites), forKey: Key.favorites)
        if let data = try? JSONEncoder().encode(choices) { defaults.set(data, forKey: Key.choices) }
    }

    func toggleVibe(_ vibe: String) {
        if preferredVibes.contains(vibe) { preferredVibes.remove(vibe) } else { preferredVibes.insert(vibe) }
        defaults.set(Array(preferredVibes), forKey: Key.vibes)
    }

    func savePartnerName() { defaults.set(partnerName, forKey: Key.partnerName) }

    func loadDemoPartnerPicks() {
        partnerLikes = ["Luna", "Ezra", "Kai", "Elsa", "Noah", "Sage", "Milo"]
        defaults.set(Array(partnerLikes), forKey: Key.partnerLikes)
    }
}
