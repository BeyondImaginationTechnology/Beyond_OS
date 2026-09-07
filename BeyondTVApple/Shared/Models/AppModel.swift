import AVFoundation
import Combine
import Foundation

@MainActor
final class AppModel: ObservableObject {
    @Published var selectedTab: BeyondTVTab = .watch
    @Published private(set) var watchPlayerRequest = 0
    @Published private(set) var channels: [Channel] = []
    @Published var selectedChannel: Channel?
    @Published private(set) var status = ChannelStatus.loading
    @Published private(set) var isLoading = false
    @Published private(set) var errorMessage: String?
    @Published private(set) var currentSource: StreamSource?
    @Published private(set) var webPlaybackURL: URL?
    @Published private(set) var guideItems: [GuideItem] = []
    @Published private(set) var isGuideLoading = false
    @Published private(set) var guideSchedule: [String: [GuideBlock]] = [:]
    @Published private(set) var catalogItems: [CatalogItem] = []
    @Published private(set) var isCatalogLoading = false
    let player = AVPlayer()
    private let api: BeyondTVAPI
    private var refreshTask: Task<Void, Never>?
    private var statusTask: Task<Void, Never>?
    private var guideTask: Task<Void, Never>?
    private var catalogTask: Task<Void, Never>?
    private var tuneSequence = 0

    init(api: BeyondTVAPI = .production) {
        self.api = api
        player.automaticallyWaitsToMinimizeStalling = true
        player.preventsDisplaySleepDuringVideoPlayback = true
    }

    deinit {
        refreshTask?.cancel()
        statusTask?.cancel()
        guideTask?.cancel()
        catalogTask?.cancel()
    }

    func start() async {
        guard channels.isEmpty else { return }
        do {
            channels = try await api.channels()
            let initial = Channel.defaultChannel(in: channels)
            if let initial {
                await tune(to: initial)
            }
            Task { await refreshGuide() }
            Task { await refreshCatalog() }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func tune(to channel: Channel) async {
        tuneSequence += 1
        let sequence = tuneSequence
        refreshTask?.cancel()
        statusTask?.cancel()
        selectedChannel = channel
        status = guideItems.first(where: { $0.channel == channel })?.status
            ?? .updating(channel: channel)
        isLoading = true
        errorMessage = nil
        currentSource = nil
        webPlaybackURL = nil
        player.pause()
        player.replaceCurrentItem(with: nil)

        refreshCurrentStatus(for: channel, sequence: sequence)

        #if os(iOS)
        if let embedURL = URL(string: channel.embedPath, relativeTo: api.baseURL)?.absoluteURL {
            webPlaybackURL = embedURL
        }
        #endif

        do {
            let response = try await api.schedule(for: channel)
            guard sequence == tuneSequence else { return }
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
            updateGuideItem(
                GuideItem(
                    channel: channel,
                    status: status,
                    currentIcon: response.state?.current?.icon,
                    currentLineup: response.state?.current?.lineup,
                    nextLineup: response.state?.next?.lineup,
                    loadedAt: Date()
                )
            )

            #if os(iOS)
            if channel.isWebPlaybackChannel {
                player.pause()
                player.replaceCurrentItem(with: nil)
                currentSource = nil

                let channelPlayerURL = URL(string: channel.embedPath, relativeTo: api.baseURL)?.absoluteURL
                let isEmbedFallback = fallbackURL?.host()?.contains("youtube") == true
                webPlaybackURL = isEmbedFallback ? fallbackURL : channelPlayerURL
                scheduleRefresh(for: channel)
                isLoading = false
                return
            }
            #endif

            guard let source = nativeSources.first ?? fallbackNativeSource else {
                player.pause()
                player.replaceCurrentItem(with: nil)
                currentSource = nil

                #if os(iOS)
                let channelPageURL = URL(string: channel.embedPath, relativeTo: api.baseURL)?.absoluteURL
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
                guard sequence == tuneSequence else { return }
            }
            player.play()
            scheduleRefresh(for: channel)
        } catch {
            guard sequence == tuneSequence else { return }
            errorMessage = error.localizedDescription
        }

        isLoading = false
    }

    /// Opens the Watch tab and places the player in view before tuning a channel.
    /// Keep all user-initiated channel changes on this route so touch and Siri Remote
    /// selections have the same landing place.
    func watch(channel: Channel) async {
        guard channel.isAvailableOnCurrentPlatform else { return }
        showWatchPlayer()
        await tune(to: channel)
    }

    func play(catalog item: CatalogItem) async {
        tuneSequence += 1
        refreshTask?.cancel()
        statusTask?.cancel()
        selectedChannel = item.channelSlug.flatMap { slug in channels.first(where: { $0.slug == slug }) }
        status = ChannelStatus(
            now: item.title,
            next: item.genre ?? item.subtitle ?? "Browse library",
            label: item.categoryLabel.uppercased(),
            sourceKey: item.slug
        )
        isLoading = false
        errorMessage = nil
        currentSource = nil
        webPlaybackURL = nil
        player.pause()
        player.replaceCurrentItem(with: nil)

        guard let playbackURL = item.playbackURL else {
            errorMessage = APIError.noNativeStream.localizedDescription
            return
        }

        let value = playbackURL.absoluteString.lowercased()
        let isNative = value.contains(".mp4") || value.contains(".m4v")
            || value.contains(".mov") || value.contains(".m3u8")
        if isNative {
            let source = StreamSource(
                provider: item.sourceLabel ?? "Beyond TV",
                title: item.title,
                url: playbackURL,
                duration: nil,
                type: item.sourceType,
                license: nil,
                rightsURL: nil
            )
            currentSource = source
            player.replaceCurrentItem(with: AVPlayerItem(url: playbackURL))
            player.play()
            return
        }

        #if os(iOS)
        webPlaybackURL = playbackURL
        #else
        errorMessage = APIError.webPlaybackOnly.localizedDescription
        #endif
    }

    /// Opens the Watch tab and centers the player before playing a catalog title.
    func watch(catalog item: CatalogItem) async {
        #if os(tvOS)
        guard item.isNativelyPlayable else { return }
        #endif
        showWatchPlayer()
        await play(catalog: item)
    }

    func refreshGuide() async {
        guard !channels.isEmpty else { return }
        guideTask?.cancel()
        isGuideLoading = true

        let channelList = channels
        let api = api
        guideTask = Task {
            async let scheduleMap = try? api.guideSchedule()
            let loadedItems = await withTaskGroup(of: GuideItem?.self) { group in
                for channel in channelList {
                    group.addTask {
                        try? await api.guideItem(for: channel)
                    }
                }

                var items: [GuideItem] = []
                for await item in group {
                    if let item { items.append(item) }
                }
                return items.sorted { $0.channel.number < $1.channel.number }
            }
            let loadedSchedule = await scheduleMap

            guard !Task.isCancelled else { return }
            await MainActor.run {
                self.guideSchedule = loadedSchedule ?? self.guideSchedule
                self.guideItems = loadedItems
                if self.isLoading,
                   let selectedChannel = self.selectedChannel,
                   let selectedGuideItem = loadedItems.first(where: { $0.channel == selectedChannel }) {
                    self.status = selectedGuideItem.status
                }
                self.isGuideLoading = false
            }
        }

        await guideTask?.value
    }

    func refreshCatalog() async {
        guard catalogItems.isEmpty else { return }
        catalogTask?.cancel()
        isCatalogLoading = true

        let api = api
        catalogTask = Task {
            let items = (try? await api.catalog()) ?? []
            guard !Task.isCancelled else { return }
            await MainActor.run {
                self.catalogItems = items
                self.isCatalogLoading = false
            }
        }

        await catalogTask?.value
    }

    func retry() async {
        guard let selectedChannel else {
            await start()
            return
        }
        await tune(to: selectedChannel)
    }

    private func showWatchPlayer() {
        selectedTab = .watch
        watchPlayerRequest &+= 1
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

    /// The full playback endpoint can take longer than a web player needs to start.
    /// Load the lightweight guide in parallel so the card stays useful while that
    /// endpoint resolves sources.
    private func refreshCurrentStatus(for channel: Channel, sequence: Int) {
        let api = api
        statusTask = Task { [weak self, api] in
            guard let guideItem = try? await api.guideItem(for: channel) else { return }
            guard !Task.isCancelled,
                  let self,
                  sequence == self.tuneSequence,
                  self.selectedChannel == channel else { return }
            self.updateGuideItem(guideItem)
            self.status = guideItem.status
        }
    }

    private func updateGuideItem(_ item: GuideItem) {
        if let index = guideItems.firstIndex(where: { $0.channel == item.channel }) {
            guideItems[index] = item
        } else {
            guideItems.append(item)
            guideItems.sort { $0.channel.number < $1.channel.number }
        }
    }

}
