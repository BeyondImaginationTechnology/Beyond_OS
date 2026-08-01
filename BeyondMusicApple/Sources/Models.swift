import Foundation

struct MusicTrack: Identifiable, Hashable {
    let id: String
    let title: String
    let artist: String
    let album: String
    let durationSeconds: Int?
    let mood: MusicMood
    let streamURL: URL?
    let downloadURL: URL?
    let artworkURL: URL?
    let sourceURL: URL?
    let licenseNote: String
    let providerName: String
    let isSeedDownloaded: Bool

    var durationText: String {
        guard let durationSeconds else { return "--:--" }
        return "\(durationSeconds / 60):\(String(format: "%02d", durationSeconds % 60))"
    }

    var downloadFileName: String {
        "\(id).\(downloadURL?.pathExtension.isEmpty == false ? downloadURL?.pathExtension ?? "mp3" : "mp3")"
    }

    static let seed: [MusicTrack] = [
        MusicTrack(
            id: "midnight-frequency",
            title: "Midnight Frequency",
            artist: "Beyond Studio",
            album: "Signal Bloom",
            durationSeconds: 214,
            mood: .focus,
            streamURL: nil,
            downloadURL: nil,
            artworkURL: nil,
            sourceURL: nil,
            licenseNote: "Local demo cue",
            providerName: "Beyond Music",
            isSeedDownloaded: true
        ),
        MusicTrack(
            id: "neon-devotional",
            title: "Neon Devotional",
            artist: "Daily Breath",
            album: "Quiet Sparks",
            durationSeconds: 188,
            mood: .calm,
            streamURL: nil,
            downloadURL: nil,
            artworkURL: nil,
            sourceURL: nil,
            licenseNote: "Local demo cue",
            providerName: "Beyond Music",
            isSeedDownloaded: true
        )
    ]
}

struct MusicSearchPage: Hashable {
    let query: String
    let page: Int
    let tracks: [MusicTrack]
    let providerSummaries: [String]

    var summaryText: String {
        providerSummaries.isEmpty ? "No providers returned tracks" : providerSummaries.joined(separator: " · ")
    }
}

enum MusicMood: String, CaseIterable, Identifiable, Hashable {
    case focus = "Focus"
    case calm = "Calm"
    case energy = "Energy"
    case kids = "Kids"

    var id: String { rawValue }

    var systemImage: String {
        switch self {
        case .focus: "sparkle.magnifyingglass"
        case .calm: "moon.stars.fill"
        case .energy: "bolt.fill"
        case .kids: "figure.2.and.child.holdinghands"
        }
    }
}

struct MusicStation: Identifiable, Hashable {
    let id: String
    let name: String
    let tagline: String
    let mood: MusicMood
    let listenerCount: Int
    let streamURL: URL?

    static let seed: [MusicStation] = [
        MusicStation(id: "open-discovery", name: "Open Discovery", tagline: "Fresh authorized finds from public catalogues", mood: .focus, listenerCount: 128, streamURL: nil),
        MusicStation(id: "daily-breath-audio", name: "Daily Breath Audio", tagline: "Scripture, reflection, and soft instrumental beds", mood: .calm, listenerCount: 72, streamURL: nil),
        MusicStation(id: "family-clean", name: "Family Clean", tagline: "Kid-safe queues for shared spaces", mood: .kids, listenerCount: 41, streamURL: nil)
    ]
}

struct MusicPlaylist: Identifiable, Hashable {
    let id: String
    let title: String
    let subtitle: String
    let trackIDs: [MusicTrack.ID]
    let accentName: String

    static let seed: [MusicPlaylist] = [
        MusicPlaylist(id: "downloaded", title: "Downloaded", subtitle: "Tracks ready without service coverage", trackIDs: ["midnight-frequency", "neon-devotional"], accentName: "Aqua"),
        MusicPlaylist(id: "deep-work", title: "Deep Work", subtitle: "Instrumental momentum for shipping", trackIDs: ["midnight-frequency"], accentName: "Rose"),
        MusicPlaylist(id: "quiet-morning", title: "Quiet Morning", subtitle: "Low-friction starts and devotional pauses", trackIDs: ["neon-devotional"], accentName: "Gold")
    ]
}

struct ArtistSpotlight: Identifiable, Hashable {
    let id: String
    let name: String
    let note: String
    let monthlyListeners: Int

    static let seed: [ArtistSpotlight] = [
        ArtistSpotlight(id: "beyond-studio", name: "Beyond Studio", note: "Original house cues and release-ready loops", monthlyListeners: 3600),
        ArtistSpotlight(id: "open-archive", name: "Open Archive Artists", note: "Creative Commons and public-domain audio sources", monthlyListeners: 2100)
    ]
}

enum DownloadState: Equatable {
    case idle
    case downloading
    case downloaded
    case failed(String)
}
