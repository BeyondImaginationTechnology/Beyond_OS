import Foundation
import Security

@MainActor
final class BabyNameStore: ObservableObject {
    @Published private(set) var favorites: Set<String>
    @Published private(set) var partnerLikes: Set<String>
    @Published private(set) var choices: [String: SwipeChoice]
    @Published var preferredVibes: Set<String>
    @Published var partnerName: String
    @Published var familyName: String
    @Published private(set) var inviteCode: String
    @Published private(set) var coupleMemberCount = 0
    @Published private(set) var coupleStatus = "Create a private space or join your partner."
    @Published private(set) var isCoupleSyncing = false

    private let defaults: UserDefaults
    private static let isScreenshotMode = ProcessInfo.processInfo.arguments.contains("-captureScreenshots")
    private var memberToken: String? { Keychain.value(for: "beyond.baby.coupleMemberToken") }
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
        if Self.isScreenshotMode {
            favorites = ["Caleb", "Elio"]
            partnerLikes = ["Caleb", "Elio", "Ezra", "Kai", "Luca", "Noah", "Sage"]
            choices = ["Caleb": .love, "Elio": .love]
            preferredVibes = ["Rare", "Gentle"]
            partnerName = "My partner"
            familyName = "Carter"
            inviteCode = "BBN-DEMO"
            coupleMemberCount = 2
            coupleStatus = "Connected — shared loves are ready."
        } else {
            favorites = Set(defaults.stringArray(forKey: Key.favorites) ?? [])
            partnerLikes = Set(defaults.stringArray(forKey: Key.partnerLikes) ?? [])
            preferredVibes = Set(defaults.stringArray(forKey: Key.vibes) ?? ["Rare", "Gentle"])
            partnerName = defaults.string(forKey: Key.partnerName) ?? "My partner"
            familyName = defaults.string(forKey: Key.familyName) ?? ""
            inviteCode = defaults.string(forKey: Key.inviteCode) ?? ""
            if let data = defaults.data(forKey: Key.choices),
               let savedChoices = try? JSONDecoder().decode([String: SwipeChoice].self, from: data) {
                choices = savedChoices
            } else {
                choices = [:]
            }
            if memberToken != nil { coupleStatus = "Private space connected." }
        }
    }

    var favoriteNames: [BabyName] { NameLibrary.all.filter { favorites.contains($0.id) } }
    var matches: [BabyName] { NameLibrary.all.filter { favorites.contains($0.id) && partnerLikes.contains($0.id) } }
    var isCoupleConnected: Bool { Self.isScreenshotMode || memberToken != nil }

    func toggleFavorite(_ name: BabyName) {
        if favorites.contains(name.id) { favorites.remove(name.id) } else { favorites.insert(name.id) }
        defaults.set(Array(favorites), forKey: Key.favorites)
    }

    func choose(_ choice: SwipeChoice, for name: BabyName) {
        choices[name.id] = choice
        if choice == .love { favorites.insert(name.id) }
        defaults.set(Array(favorites), forKey: Key.favorites)
        if let data = try? JSONEncoder().encode(choices) { defaults.set(data, forKey: Key.choices) }
        guard isCoupleConnected else { return }
        Task { await saveCouplePick(name: name.name, decision: choice) }
    }

    func toggleVibe(_ vibe: String) {
        if preferredVibes.contains(vibe) { preferredVibes.remove(vibe) } else { preferredVibes.insert(vibe) }
        defaults.set(Array(preferredVibes), forKey: Key.vibes)
    }

    func savePartnerName() { defaults.set(partnerName, forKey: Key.partnerName) }
    func saveFamilyName() { familyName = familyName.trimmingCharacters(in: .whitespacesAndNewlines); defaults.set(familyName, forKey: Key.familyName) }

    func createCoupleSpace() async {
        await performCoupleRequest(action: "create", payload: ["displayName": partnerName]) { response in
            guard let token = response["memberToken"] as? String, let invite = response["inviteCode"] as? String else { throw CoupleError.invalidResponse }
            try Keychain.save(token, for: "beyond.baby.coupleMemberToken")
            self.inviteCode = invite
            self.defaults.set(invite, forKey: Key.inviteCode)
            self.coupleMemberCount = 1
            self.coupleStatus = "Invite your partner with this code."
        }
    }

    func joinCoupleSpace(code: String) async {
        let cleaned = code.trimmingCharacters(in: .whitespacesAndNewlines)
        await performCoupleRequest(action: "join", payload: ["inviteCode": cleaned, "displayName": partnerName]) { response in
            guard let token = response["memberToken"] as? String else { throw CoupleError.invalidResponse }
            try Keychain.save(token, for: "beyond.baby.coupleMemberToken")
            self.inviteCode = cleaned.uppercased()
            self.defaults.set(self.inviteCode, forKey: Key.inviteCode)
            self.coupleStatus = "Connected. Syncing your private picks."
        }
        if isCoupleConnected { await refreshCoupleState() }
    }

    func refreshCoupleState() async {
        guard memberToken != nil else { return }
        await performCoupleRequest(action: "state", payload: [:]) { response in
            self.coupleMemberCount = response["memberCount"] as? Int ?? 0
            let names = Set((response["matches"] as? [String] ?? []).map { $0.lowercased() })
            self.partnerLikes = Set(NameLibrary.all.filter { names.contains($0.name.lowercased()) }.map(\.id))
            self.defaults.set(Array(self.partnerLikes), forKey: Key.partnerLikes)
            self.coupleStatus = self.coupleMemberCount == 2 ? "Connected — shared loves are ready." : "Waiting for your partner to join."
        }
    }

    private func saveCouplePick(name: String, decision: SwipeChoice) async {
        await performCoupleRequest(action: "savePick", payload: ["name": name, "decision": decision.rawValue]) { _ in }
        await refreshCoupleState()
    }

    private func performCoupleRequest(action: String, payload: [String: String], success: @escaping ([String: Any]) throws -> Void) async {
        isCoupleSyncing = true
        defer { isCoupleSyncing = false }
        do {
            var body = payload
            body["action"] = action
            var request = URLRequest(url: URL(string: "https://beyondimagination.co.technology/beyond-baby-names/api/couples.php")!)
            request.httpMethod = "POST"
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
            if let token = memberToken { request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization") }
            request.httpBody = try JSONSerialization.data(withJSONObject: body)
            let (data, urlResponse) = try await URLSession.shared.data(for: request)
            let response = try JSONSerialization.jsonObject(with: data) as? [String: Any] ?? [:]
            guard let http = urlResponse as? HTTPURLResponse, (200...299).contains(http.statusCode) else {
                throw CoupleError.server(response["error"] as? String ?? "Could not sync Couple Mode")
            }
            try success(response)
        } catch {
            coupleStatus = error.localizedDescription
        }
    }
}

private enum CoupleError: LocalizedError {
    case invalidResponse
    case server(String)
    var errorDescription: String? {
        switch self { case .invalidResponse: "The Couple Mode response was incomplete."; case .server(let message): message.replacingOccurrences(of: "_", with: " ").capitalized }
    }
}

private enum Keychain {
    static func value(for key: String) -> String? {
        let query: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrAccount as String: key, kSecReturnData as String: true, kSecMatchLimit as String: kSecMatchLimitOne]
        var result: CFTypeRef?
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess, let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }
    static func save(_ value: String, for key: String) throws {
        let data = Data(value.utf8)
        let query: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrAccount as String: key]
        SecItemDelete(query as CFDictionary)
        let attributes: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrAccount as String: key, kSecValueData as String: data, kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly]
        guard SecItemAdd(attributes as CFDictionary, nil) == errSecSuccess else { throw CoupleError.server("Unable to secure your Couple Mode credential") }
    }
}
