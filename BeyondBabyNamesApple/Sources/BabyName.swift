import Foundation

enum NameStyle: String, CaseIterable, Codable, Identifiable, Sendable {
    case girl = "Girl"
    case boy = "Boy"
    case unisex = "Unisex"

    var id: String { rawValue }
    var symbol: String {
        switch self {
        case .girl: "sparkles"
        case .boy: "star.fill"
        case .unisex: "circle.hexagongrid.fill"
        }
    }
}

struct BabyName: Identifiable, Hashable, Codable, Sendable {
    let name: String
    let style: NameStyle
    let origin: String
    let meaning: String
    let pronunciation: String
    let vibe: [String]
    let popularity: Int

    var id: String { name }
}

enum SwipeChoice: String, Codable, Sendable {
    case pass, maybe, love
}

struct TwinPair: Identifiable, Hashable, Sendable {
    let first: BabyName
    let second: BabyName
    var id: String { first.id + "-" + second.id }
}
