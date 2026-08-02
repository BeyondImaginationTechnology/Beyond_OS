import Foundation

struct ScheduleResponse: Decodable, Sendable {
    let ok: Bool
    let state: ScheduleState?
    let sources: [StreamSource]
    let startOffset: Double
    let timezone: String?
    let embedURL: String?
    let playerURL: String?

    enum CodingKeys: String, CodingKey {
        case ok, state, sources, timezone
        case startOffset = "start_offset"
        case embedURL = "embed_url"
        case playerURL = "player_url"
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        ok = try container.decodeIfPresent(Bool.self, forKey: .ok) ?? false
        state = try container.decodeIfPresent(ScheduleState.self, forKey: .state)
        sources = try container.decodeIfPresent([StreamSource].self, forKey: .sources) ?? []
        startOffset = try container.decodeIfPresent(Double.self, forKey: .startOffset) ?? 0
        timezone = try container.decodeIfPresent(String.self, forKey: .timezone)
        embedURL = try container.decodeIfPresent(String.self, forKey: .embedURL)
        playerURL = try container.decodeIfPresent(String.self, forKey: .playerURL)
    }

    var webPlaybackLocation: String? {
        state?.embedURL ?? state?.playerURL ?? embedURL ?? playerURL
    }
}

struct ScheduleState: Decodable, Sendable {
    let current: Program?
    let next: Program?
    let label: String?
    let sourceKey: String?
    let embedURL: String?
    let playerURL: String?

    enum CodingKeys: String, CodingKey {
        case current, next, label
        case sourceKey = "source_key"
        case embedURL = "embed_url"
        case playerURL = "player_url"
    }
}

struct Program: Decodable, Sendable {
    let title: String?
    let lineup: String?
    let icon: String?
    let provider: String?
    let year: String?
    let genre: String?
}

struct StreamSource: Decodable, Identifiable, Hashable, Sendable {
    let provider: String?
    let title: String
    let url: URL
    let duration: Double?
    let type: String?
    let license: String?
    let rightsURL: URL?

    var id: URL { url }
    var isNativelyPlayable: Bool {
        let value = url.absoluteString.lowercased()
        return value.contains(".mp4") || value.contains(".m4v") || value.contains(".mov") || value.contains(".m3u8")
    }

    enum CodingKeys: String, CodingKey {
        case provider, title, url, duration, type, license
        case rightsURL = "rights_url"
    }
}

struct ChannelStatus: Sendable {
    let now: String
    let next: String
    let label: String
    let sourceKey: String

    static let loading = ChannelStatus(
        now: "Loading live schedule…",
        next: "Up next",
        label: "LIVE",
        sourceKey: ""
    )
}

struct GuideItem: Identifiable, Sendable {
    let channel: Channel
    let status: ChannelStatus
    let currentIcon: String?
    let currentLineup: String?
    let nextLineup: String?
    let loadedAt: Date

    var id: String { channel.id }
}
