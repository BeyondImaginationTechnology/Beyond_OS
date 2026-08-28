import Foundation

enum NameLibrary {
    static let all: [BabyName] = [
        .init(name: "Aaliyah", style: .girl, origin: "Arabic", meaning: "Exalted and noble", pronunciation: "ah-LEE-yah", vibe: ["Graceful", "Modern"], popularity: 86),
        .init(name: "Amara", style: .girl, origin: "Igbo", meaning: "Grace", pronunciation: "ah-MAR-ah", vibe: ["Global", "Gentle"], popularity: 72),
        .init(name: "Ari", style: .unisex, origin: "Hebrew", meaning: "Lion", pronunciation: "AR-ee", vibe: ["Short", "Bold"], popularity: 69),
        .init(name: "Aurora", style: .girl, origin: "Latin", meaning: "Dawn", pronunciation: "uh-ROAR-uh", vibe: ["Celestial", "Romantic"], popularity: 91),
        .init(name: "Caleb", style: .boy, origin: "Hebrew", meaning: "Faithful", pronunciation: "KAY-leb", vibe: ["Biblical", "Classic"], popularity: 82),
        .init(name: "Cassian", style: .boy, origin: "Latin", meaning: "Hollow", pronunciation: "CASH-un", vibe: ["Rare", "Literary"], popularity: 34),
        .init(name: "Celeste", style: .girl, origin: "Latin", meaning: "Heavenly", pronunciation: "seh-LEST", vibe: ["Celestial", "Elegant"], popularity: 53),
        .init(name: "Elio", style: .boy, origin: "Italian", meaning: "Sun", pronunciation: "EH-lee-oh", vibe: ["Bright", "Rare"], popularity: 42),
        .init(name: "Elodie", style: .girl, origin: "French", meaning: "Foreign riches", pronunciation: "EL-oh-dee", vibe: ["French", "Musical"], popularity: 38),
        .init(name: "Elsa", style: .girl, origin: "German", meaning: "Pledged to God", pronunciation: "EL-sah", vibe: ["Vintage", "Brave"], popularity: 48),
        .init(name: "Ezra", style: .unisex, origin: "Hebrew", meaning: "Helper", pronunciation: "EZ-rah", vibe: ["Biblical", "Gentle"], popularity: 93),
        .init(name: "Imani", style: .unisex, origin: "Swahili", meaning: "Faith", pronunciation: "ee-MAH-nee", vibe: ["Spiritual", "Global"], popularity: 45),
        .init(name: "Isla", style: .girl, origin: "Scottish", meaning: "Island", pronunciation: "EYE-lah", vibe: ["Nature", "Soft"], popularity: 96),
        .init(name: "Jude", style: .unisex, origin: "Latin", meaning: "Praised", pronunciation: "JOOD", vibe: ["Classic", "Cool"], popularity: 77),
        .init(name: "Kai", style: .unisex, origin: "Hawaiian", meaning: "Sea", pronunciation: "KYE", vibe: ["Nature", "Short"], popularity: 89),
        .init(name: "Levi", style: .boy, origin: "Hebrew", meaning: "Joined in harmony", pronunciation: "LEE-vye", vibe: ["Biblical", "Warm"], popularity: 97),
        .init(name: "Lina", style: .girl, origin: "Arabic", meaning: "Tender", pronunciation: "LEE-nah", vibe: ["Soft", "International"], popularity: 61),
        .init(name: "Luca", style: .unisex, origin: "Italian", meaning: "Bringer of light", pronunciation: "LOO-kah", vibe: ["Bright", "Modern"], popularity: 94),
        .init(name: "Luna", style: .girl, origin: "Latin", meaning: "Moon", pronunciation: "LOO-nah", vibe: ["Celestial", "Dreamy"], popularity: 98),
        .init(name: "Maeve", style: .girl, origin: "Irish", meaning: "She who intoxicates", pronunciation: "MAYV", vibe: ["Mythic", "Strong"], popularity: 84),
        .init(name: "Maelle", style: .girl, origin: "Breton", meaning: "Princess", pronunciation: "mah-EL", vibe: ["French", "Rare"], popularity: 31),
        .init(name: "Malia", style: .girl, origin: "Hawaiian", meaning: "Beloved", pronunciation: "mah-LEE-ah", vibe: ["Warm", "Global"], popularity: 58),
        .init(name: "Mika", style: .unisex, origin: "Japanese", meaning: "Beautiful fragrance", pronunciation: "MEE-kah", vibe: ["Modern", "Gentle"], popularity: 43),
        .init(name: "Milo", style: .boy, origin: "German", meaning: "Merciful", pronunciation: "MY-loh", vibe: ["Playful", "Vintage"], popularity: 88),
        .init(name: "Nadia", style: .girl, origin: "Slavic", meaning: "Hope", pronunciation: "NAH-dee-ah", vibe: ["Hopeful", "Classic"], popularity: 55),
        .init(name: "Noah", style: .unisex, origin: "Hebrew", meaning: "Rest and comfort", pronunciation: "NOH-ah", vibe: ["Biblical", "Peaceful"], popularity: 100),
        .init(name: "Nova", style: .unisex, origin: "Latin", meaning: "New", pronunciation: "NOH-vah", vibe: ["Celestial", "Bold"], popularity: 75),
        .init(name: "Orion", style: .boy, origin: "Greek", meaning: "Rising in the sky", pronunciation: "oh-RYE-un", vibe: ["Celestial", "Mythic"], popularity: 51),
        .init(name: "Sacha", style: .unisex, origin: "French", meaning: "Defender", pronunciation: "SAH-shah", vibe: ["French", "Artistic"], popularity: 36),
        .init(name: "Sage", style: .unisex, origin: "Latin", meaning: "Wise", pronunciation: "SAYJ", vibe: ["Nature", "Calm"], popularity: 79),
        .init(name: "Selah", style: .girl, origin: "Hebrew", meaning: "Pause and reflect", pronunciation: "SEE-lah", vibe: ["Biblical", "Peaceful"], popularity: 47),
        .init(name: "Sol", style: .unisex, origin: "Spanish", meaning: "Sun", pronunciation: "SOHL", vibe: ["Bright", "Short"], popularity: 28),
        .init(name: "Theo", style: .boy, origin: "Greek", meaning: "Gift of God", pronunciation: "THEE-oh", vibe: ["Classic", "Warm"], popularity: 92),
        .init(name: "Zaylen", style: .boy, origin: "Modern", meaning: "Calm strength", pronunciation: "ZAY-len", vibe: ["Modern", "Rare"], popularity: 25),
        .init(name: "Zuri", style: .girl, origin: "Swahili", meaning: "Beautiful", pronunciation: "ZOO-ree", vibe: ["Bright", "Global"], popularity: 67)
    ]

    static var origins: [String] { Array(Set(all.map(\.origin))).sorted() }

    static func search(_ query: String, style: NameStyle?, origin: String?, vibes: Set<String>) -> [BabyName] {
        let needle = query.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        return all.filter { item in
            let searchable = ([item.name, item.origin, item.meaning] + item.vibe).joined(separator: " ").lowercased()
            return (needle.isEmpty || searchable.contains(needle))
                && (style == nil || item.style == style)
                && (origin == nil || item.origin == origin)
                && (vibes.isEmpty || !Set(item.vibe).isDisjoint(with: vibes))
        }
    }

    static func recommendations(vibes: Set<String>, favorites: Set<String>) -> [BabyName] {
        all.sorted { lhs, rhs in
            score(lhs, vibes: vibes, favorites: favorites) > score(rhs, vibes: vibes, favorites: favorites)
        }
    }

    static func twinPairs(from names: [BabyName]) -> [TwinPair] {
        guard names.count > 1 else { return [] }
        var pairs: [TwinPair] = []
        for firstIndex in names.indices {
            for secondIndex in names.indices where secondIndex > firstIndex {
                let first = names[firstIndex], second = names[secondIndex]
                let sharedVibe = !Set(first.vibe).isDisjoint(with: second.vibe)
                let balancedLength = abs(first.name.count - second.name.count) <= 2
                if sharedVibe || balancedLength { pairs.append(.init(first: first, second: second)) }
            }
        }
        return Array(pairs.prefix(6))
    }

    private static func score(_ item: BabyName, vibes: Set<String>, favorites: Set<String>) -> Int {
        let vibeScore = Set(item.vibe).intersection(vibes).count * 30
        let favoriteOrigins = Set(all.filter { favorites.contains($0.id) }.map(\.origin))
        return vibeScore + (favoriteOrigins.contains(item.origin) ? 18 : 0) + item.popularity / 5
    }
}
