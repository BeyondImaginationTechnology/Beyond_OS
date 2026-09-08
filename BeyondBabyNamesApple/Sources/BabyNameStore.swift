import Foundation

@MainActor
final class BabyNameStore: ObservableObject {
    @Published private(set) var favorites: Set<String>
    @Published private(set) var partnerLikes: Set<String>
    @Published private(set) var choices: [String: SwipeChoice]
    @Published var preferredVibes: Set<String>
    @Published var partnerName: String
    @Published var familyName: String
    @Published private(set) var inviteCode: String

    private let defaults: UserDefaults
    private enum Key {
        static let favorites = "beyond.baby.favorites"
        static let partnerLikes = "beyond.baby.partnerLikes"
        static let choices = "beyond.baby.choices"
        static let vibes = "beyond.baby.vibes"
        static let partnerName = "beyond.baby.partnerName"
        static let familyName = "beyond.baby.familyName"
        static let inviteCode = "beyond.baby.inviteCode"
    }

    init(defaults: UserDefaults = .standard) {
        self.defaults = defaults
        favorites = Set(defaults.stringArray(forKey: Key.favorites) ?? [])
        partnerLikes = Set(defaults.stringArray(forKey: Key.partnerLikes) ?? ["Luna", "Ezra", "Kai", "Elsa"])
        preferredVibes = Set(defaults.stringArray(forKey: Key.vibes) ?? ["Rare", "Gentle"])
        partnerName = defaults.string(forKey: Key.partnerName) ?? "My partner"
        familyName = defaults.string(forKey: Key.familyName) ?? ""
        if let storedCode = defaults.string(forKey: Key.inviteCode), !storedCode.isEmpty {
            inviteCode = storedCode
        } else {
            let code = Self.makeInviteCode()
            inviteCode = code
            defaults.set(code, forKey: Key.inviteCode)
        }
        if let data = defaults.data(forKey: Key.choices),
           let value = try? JSONDecoder().decode([String: SwipeChoice].self, from: data) {
            choices = value
        } else {
            choices = [:]
        }
    }

    var favoriteNames: [BabyName] { NameLibrary.all.filter { favorites.contains($0.id) } }
    var matches: [BabyName] { NameLibrary.all.filter { favorites.contains($0.id) && partnerLikes.contains($0.id) } }
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

    func saveFamilyName() {
        familyName = familyName.trimmingCharacters(in: .whitespacesAndNewlines)
        defaults.set(familyName, forKey: Key.familyName)
    }

    func loadDemoPartnerPicks() {
        partnerLikes = ["Luna", "Ezra", "Kai", "Elsa", "Noah", "Sage", "Milo"]
        defaults.set(Array(partnerLikes), forKey: Key.partnerLikes)
    }

    func regenerateInviteCode() {
        inviteCode = Self.makeInviteCode()
        defaults.set(inviteCode, forKey: Key.inviteCode)
    }

    private static func makeInviteCode() -> String {
        let random = UUID().uuidString.replacingOccurrences(of: "-", with: "").prefix(16).uppercased()
        return "BBN-" + stride(from: 0, to: random.count, by: 4)
            .map { offset in
                let start = random.index(random.startIndex, offsetBy: offset)
                let end = random.index(start, offsetBy: min(4, random.count - offset))
                return String(random[start..<end])
            }
            .joined(separator: "-")
    }
}
