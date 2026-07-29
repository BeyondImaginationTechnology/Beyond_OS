import AVFoundation
import Combine
import Foundation

@MainActor
final class AppModel: ObservableObject {
    @Published private(set) var channels: [Channel] = []
    @Published var selectedChannel: Channel?
    @Published private(set) var status = ChannelStatus.loading
    @Published private(set) var isLoading = false
    @Published private(set) var errorMessage: String?
    @Published private(set) var currentSource: StreamSource?
    @Published private(set) var webPlaybackURL: URL?

    let player = AVPlayer()
    private let api: BeyondTVAPI
    private var refreshTask: Task<Void, Never>?

    init(api: BeyondTVAPI = .production) {
        self.api = api
        player.automaticallyWaitsToMinimizeStalling = true
        player.preventsDisplaySleepDuringVideoPlayback = true
    }

    deinit {
        refreshTask?.cancel()
    }

    func start() async {
        guard channels.isEmpty else { return }
        do {
            channels = try await api.channels()
            let initial = channels.first(where: { $0.slug == "classic-cinema" }) ?? channels.first
            if let initial {
                await tune(to: initial)
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func tune(to channel: Channel) async {
        refreshTask?.cancel()
        selectedChannel = channel
        status = .loading
        isLoading = true
        errorMessage = nil
        webPlaybackURL = nil

        do {
            let response = try await api.schedule(for: channel)
            let nativeSources = response.sources.filter(\.isNativelyPlayable)
            let fallbackURL = response.webPlaybackLocation.flatMap {
                URL(string: $0, relativeTo: api.baseURL)?.absoluteURL
            }
            let fallbackNativeSource = fallbackURL.flatMap { url -> StreamSource? in
                let value = url.absoluteString.lowercased()
                guard value.contains(".mp4") || value.contains(".m4v")
                    || value.contains(".mov") || value.contains(".m3u8") else {
                    return nil
                }
                return StreamSource(
                    provider: "Beyond TV",
                    title: response.state?.current?.title ?? channel.name,
                    url: url,
                    duration: nil,
                    type: "video",
                    license: nil,
                    rightsURL: nil
                )
            }
            status = ChannelStatus(
                now: response.state?.current?.title
                    ?? nativeSources.first?.title
                    ?? fallbackNativeSource?.title
                    ?? "Live now",
                next: response.state?.next?.title ?? "More on Beyond TV",
                label: response.state?.label ?? "LIVE · VANCOUVER",
                sourceKey: response.state?.sourceKey ?? ""
            )

            guard let source = nativeSources.first ?? fallbackNativeSource else {
                player.pause()
                player.replaceCurrentItem(with: nil)
                currentSource = nil

                #if os(iOS)
                let channelPageURL = URL(
                    string: "/beyond-tv/channel.php?slug=\(channel.slug)",
                    relativeTo: api.baseURL
                )?.absoluteURL
                if let webURL = fallbackURL ?? channelPageURL {
                    webPlaybackURL = webURL
                    scheduleRefresh(for: channel)
                    isLoading = false
                    return
                }
                #else
                if fallbackURL != nil || channel.isWebPlaybackChannel {
                    throw APIError.webPlaybackOnly
                }
                #endif

                throw APIError.noNativeStream
            }

            let item = AVPlayerItem(url: source.url)
            player.replaceCurrentItem(with: item)
            currentSource = source
            webPlaybackURL = nil
            if response.startOffset > 0 {
                let time = CMTime(seconds: response.startOffset, preferredTimescale: 600)
                await player.seek(to: time, toleranceBefore: .zero, toleranceAfter: .zero)
            }
            player.play()
            scheduleRefresh(for: channel)
        } catch {
            errorMessage = error.localizedDescription
        }

        isLoading = false
    }

    func retry() async {
        guard let selectedChannel else {
            await start()
            return
        }
        await tune(to: selectedChannel)
    }

    func togglePlayback() {
        if player.timeControlStatus == .playing {
            player.pause()
        } else {
            player.play()
        }
    }

    private func scheduleRefresh(for channel: Channel) {
        refreshTask = Task { [weak self] in
            try? await Task.sleep(for: .seconds(300))
            guard !Task.isCancelled, let self, self.selectedChannel == channel else { return }
            await self.tune(to: channel)
        }
    }
}
