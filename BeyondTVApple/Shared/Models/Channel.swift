import Foundation
import SwiftUI

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

    var displayNumber: String {
        String(format: "%02d", number)
    }

    var symbolName: String {
        switch slug {
        case "beyond-after-dark":
            "moon.stars.fill"
        case "beyond-cartoons", "classic-cartoon-theater":
            "sparkles.tv.fill"
        case "yugioh-tv":
            "sparkles.rectangle.stack.fill"
        case "classic-cinema":
            "movieclapper.fill"
        case "beyond-comedy":
            "theatermasks.fill"
        case "beyond-family":
            "wand.and.stars"
        case "bubble-guppies", "preschool-francais":
            "figure.and.child.holdinghands"
        case "space-tv":
            "sparkles"
        case "beyond-ancient":
            "building.columns.fill"
        case "beyond-french":
            "textformat.abc"
        case "beyond-health":
            "heart.text.square.fill"
        case "beyond-trailers":
            "play.rectangle.on.rectangle.fill"
        case "beyond-sports":
            "trophy.fill"
        case "beyond-mystery":
            "magnifyingglass.circle.fill"
        default:
            "play.tv.fill"
        }
    }

    var gradientColors: [Color] {
        switch slug {
        case "beyond-after-dark":
            [Color(red: 0.31, green: 0.10, blue: 0.37), Color(red: 0.07, green: 0.06, blue: 0.12)]
        case "beyond-cartoons":
            [Color(red: 0.09, green: 0.18, blue: 0.44), Color(red: 0.07, green: 0.04, blue: 0.15)]
        case "yugioh-tv":
            [Color(red: 0.03, green: 0.46, blue: 0.63), Color(red: 0.95, green: 0.37, blue: 0.67)]
        case "classic-cinema":
            [Color(red: 0.31, green: 0.20, blue: 0.07), Color(red: 0.72, green: 0.50, blue: 0.15)]
        case "beyond-comedy":
            [Color(red: 0.36, green: 0.17, blue: 0.03), Color(red: 0.84, green: 0.61, blue: 0.20)]
        case "beyond-family":
            [Color(red: 0.08, green: 0.15, blue: 0.40), Color(red: 0.70, green: 0.16, blue: 0.34)]
        case "classic-cartoon-theater":
            [Color(red: 0.07, green: 0.18, blue: 0.40), Color(red: 0.68, green: 0.14, blue: 0.32)]
        case "bubble-guppies":
            [Color(red: 0.04, green: 0.27, blue: 0.35), Color(red: 0.11, green: 0.64, blue: 0.72)]
        case "preschool-francais":
            [Color(red: 0.42, green: 0.15, blue: 0.38), Color(red: 0.76, green: 0.30, blue: 0.47)]
        case "space-tv":
            [Color(red: 0.08, green: 0.18, blue: 0.29), Color(red: 0.28, green: 0.47, blue: 0.61)]
        case "beyond-ancient":
            [Color(red: 0.09, green: 0.08, blue: 0.13), Color(red: 0.32, green: 0.22, blue: 0.37)]
        case "beyond-french":
            [Color(red: 0.31, green: 0.24, blue: 0.10), Color(red: 0.75, green: 0.54, blue: 0.21)]
        case "beyond-health":
            [Color(red: 0.07, green: 0.25, blue: 0.18), Color(red: 0.15, green: 0.64, blue: 0.41)]
        case "beyond-trailers":
            [Color(red: 0.20, green: 0.06, blue: 0.31), Color(red: 0.64, green: 0.11, blue: 0.69), Color(red: 0.98, green: 0.45, blue: 0.09)]
        case "beyond-sports":
            [Color(red: 0.03, green: 0.11, blue: 0.24), Color(red: 0.03, green: 0.34, blue: 0.65), Color(red: 0.09, green: 0.64, blue: 0.84)]
        case "beyond-mystery":
            [Color(red: 0.06, green: 0.05, blue: 0.12), Color(red: 0.25, green: 0.16, blue: 0.30)]
        default:
            [Color(red: 0.46, green: 0.35, blue: 0.94), Color(red: 0.70, green: 0.27, blue: 0.85)]
        }
    }

    var statusLabel: String {
        isWebPlaybackChannel ? "WEB" : "LIVE"
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
