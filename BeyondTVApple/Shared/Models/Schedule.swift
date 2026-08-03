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

struct GuideBlock: Decodable, Identifiable, Hashable, Sendable {
    let start: Int
    let end: Int
    let icon: String?
    let title: String
    let lineup: String?

    var id: String { "\(start)-\(end)-\(title)-\(lineup ?? "")" }

    var timeLabel: String {
        "\(hourLabel(start))-\(hourLabel(end == 24 ? 0 : end))"
    }

    func contains(hour: Int) -> Bool {
        end > start ? (hour >= start && hour < end) : (hour >= start || hour < end)
    }

    private func hourLabel(_ hour: Int) -> String {
        let value = ((hour % 24) + 24) % 24
        if value == 0 { return "12a" }
        if value < 12 { return "\(value)a" }
        if value == 12 { return "12p" }
        return "\(value - 12)p"
    }
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

struct CatalogItem: Decodable, Identifiable, Hashable, Sendable {
    let slug: String
    let type: String?
    let title: String
    let subtitle: String?
    let description: String?
    let icon: String?
    let rating: String?
    let year: String?
    let genre: String?
    let runtime: String?
    let sourceType: String?
    let videoURL: URL?
    let archiveID: String?
    let thumbnail: URL?
    let sourceLabel: String?
    let candidateURL: URL?
    let channelSlug: String?
    let episodeCount: Int?
    let seasons: Int?

    var id: String { slug }

    var categoryLabel: String {
        if let type, !type.isEmpty { return type.capitalized }
        return "Stream"
    }

    var detailLine: String {
        [year, rating, genre].compactMap { value -> String? in
            guard let value else { return nil }
            let trimmed = value.trimmingCharacters(in: .whitespacesAndNewlines)
            return trimmed.isEmpty ? nil : trimmed
        }.joined(separator: " · ")
    }

    var playbackURL: URL? {
        if let videoURL { return videoURL }
        if let archiveID, !archiveID.isEmpty {
            return URL(string: "https://archive.org/embed/\(archiveID)")
        }
        return candidateURL
    }

    enum CodingKeys: String, CodingKey {
        case slug, type, title, subtitle, description, icon, rating, year, genre, runtime, thumbnail
        case sourceType = "source_type"
        case videoURL = "video_url"
        case archiveID = "archive_id"
        case sourceLabel = "source_label"
        case candidateURL = "candidate_url"
        case channelSlug = "channel_slug"
        case episodeCount = "episode_count"
        case seasons
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        slug = try container.decodeIfPresent(String.self, forKey: .slug) ?? UUID().uuidString
        type = try container.decodeIfPresent(String.self, forKey: .type)
        title = try container.decodeIfPresent(String.self, forKey: .title) ?? "Untitled"
        subtitle = try container.decodeIfPresent(String.self, forKey: .subtitle)
        description = try container.decodeIfPresent(String.self, forKey: .description)
        icon = try container.decodeIfPresent(String.self, forKey: .icon)
        rating = try container.decodeIfPresent(String.self, forKey: .rating)
        year = try container.decodeIfPresent(String.self, forKey: .year)
        genre = try container.decodeIfPresent(String.self, forKey: .genre)
        runtime = try container.decodeIfPresent(String.self, forKey: .runtime)
        sourceType = try container.decodeIfPresent(String.self, forKey: .sourceType)
        videoURL = Self.decodeURL(from: container, forKey: .videoURL)
        archiveID = try container.decodeIfPresent(String.self, forKey: .archiveID)
        thumbnail = Self.decodeURL(from: container, forKey: .thumbnail)
        sourceLabel = try container.decodeIfPresent(String.self, forKey: .sourceLabel)
        candidateURL = Self.decodeURL(from: container, forKey: .candidateURL)
        channelSlug = try container.decodeIfPresent(String.self, forKey: .channelSlug)
        episodeCount = try container.decodeIfPresent(Int.self, forKey: .episodeCount)
        seasons = try container.decodeIfPresent(Int.self, forKey: .seasons)
    }

    private static func decodeURL(from container: KeyedDecodingContainer<CodingKeys>, forKey key: CodingKeys) -> URL? {
        guard let value = (try? container.decodeIfPresent(String.self, forKey: key)) ?? nil else { return nil }
        let trimmed = value.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return nil }
        return URL(string: trimmed)
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
