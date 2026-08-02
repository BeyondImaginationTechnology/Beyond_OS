import Foundation

enum APIError: LocalizedError {
    case invalidResponse
    case server(Int)
    case noNativeStream
    case webPlaybackOnly

    var errorDescription: String? {
        switch self {
        case .invalidResponse:
            "Beyond TV returned an unreadable response."
        case .server(let status):
            "Beyond TV is temporarily unavailable (HTTP \(status))."
        case .noNativeStream:
            "This channel is temporarily unavailable. Try another channel from the guide."
        case .webPlaybackOnly:
            "This web channel is available in Beyond TV on iPhone, iPad, and the web."
        }
    }
}

struct BeyondTVAPI: Sendable {
    static let production = BeyondTVAPI(
        baseURL: URL(string: "https://beyondimagination.co.technology")!
    )

    let baseURL: URL
    private let decoder = JSONDecoder()

    func channels() async throws -> [Channel] {
        let url = baseURL.appending(path: "beyond-tv/data/featured-channels.json")
        let data = try await data(from: url)
        return try decoder.decode([Channel].self, from: data).sorted { $0.number < $1.number }
    }

    func schedule(for channel: Channel) async throws -> ScheduleResponse {
        guard let url = URL(string: channel.endpoint, relativeTo: baseURL)?.absoluteURL else {
            throw APIError.invalidResponse
        }
        let data = try await data(from: url)
        return try decoder.decode(ScheduleResponse.self, from: data)
    }

    func guideItem(for channel: Channel) async throws -> GuideItem {
        let response = try await schedule(for: channel)
        let current = response.state?.current
        let next = response.state?.next
        let nativeSources = response.sources.filter(\.isNativelyPlayable)
        let status = ChannelStatus(
            now: current?.title ?? nativeSources.first?.title ?? "Live now",
            next: next?.title ?? "More on Beyond TV",
            label: response.state?.label ?? "LIVE · VANCOUVER",
            sourceKey: response.state?.sourceKey ?? ""
        )
        return GuideItem(
            channel: channel,
            status: status,
            currentIcon: current?.icon,
            currentLineup: current?.lineup,
            nextLineup: next?.lineup,
            loadedAt: Date()
        )
    }

    private func data(from url: URL) async throws -> Data {
        var request = URLRequest(url: url)
        request.timeoutInterval = 15
        request.cachePolicy = .reloadRevalidatingCacheData
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("BeyondTV-Apple/1.0", forHTTPHeaderField: "User-Agent")
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }
        guard (200..<300).contains(http.statusCode) else {
            throw APIError.server(http.statusCode)
        }
        return data
    }
}
