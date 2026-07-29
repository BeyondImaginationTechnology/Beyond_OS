import Foundation

struct Channel: Codable, Identifiable, Hashable, Sendable {
    let number: Int
    let slug: String
    let name: String
    let icon: String
    let description: String
    let category: String

    var id: String { slug }

    var isWebPlaybackChannel: Bool {
        [
            "beyond-cartoons",
            "classic-cartoon-theater",
            "space-tv",
            "beyond-ancient",
            "beyond-french",
            "beyond-health"
        ].contains(slug)
    }

    var endpoint: String {
        switch slug {
        case "classic-cinema":
            "/beyond-tv/api/movies-live.php"
        case "beyond-cartoons":
            "/beyond-tv/api/beyond-cartoons-live.php"
        case "yugioh-tv":
            "/beyond-tv/api/anime-live.php"
        case "classic-cartoon-theater":
            "/beyond-tv/api/classic-live.php"
        case "space-tv":
            "/beyond-tv/api/space-live.php"
        case "beyond-ancient", "beyond-french", "beyond-health":
            "/beyond-tv/api/schedule-live.php?slug=\(slug)"
        default:
            "/beyond-tv/api/channel-stream.php?slug=\(slug)"
        }
    }

    static let preview = Channel(
        number: 4,
        slug: "classic-cinema",
        name: "Beyond Movies",
        icon: "🎬",
        description: "Features, double bills, and weekend marathons.",
        category: "Movies"
    )
}
