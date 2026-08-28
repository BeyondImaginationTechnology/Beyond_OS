import AVFoundation
import Combine
import Foundation
import Security
#if os(iOS)
import AuthenticationServices
#endif

private struct SecureTokenStore {
    let service = "technology.co.beyondimagination.beyondtv"
    let account = "BeyondID.mobileToken"

    func save(_ token: String) -> Bool {
        guard let data = token.data(using: .utf8) else { return false }
        delete()
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly,
            kSecValueData as String: data
        ]
        return SecItemAdd(query as CFDictionary, nil) == errSecSuccess
    }

    func load() -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]
        var result: CFTypeRef?
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess,
              let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    func delete() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account
        ]
        SecItemDelete(query as CFDictionary)
    }
}

@MainActor
final class AppModel: ObservableObject {
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
    @Published private(set) var beyondIDUser: BeyondIDUser?
    @Published private(set) var beyondIDWallet: BeyondIDWallet?
    @Published private(set) var isSigningIn = false
    @Published private(set) var authErrorMessage: String?

    let player = AVPlayer()
    private let api: BeyondTVAPI
    private let beyondID: BeyondIDService
    private var refreshTask: Task<Void, Never>?
    private var guideTask: Task<Void, Never>?
    private var catalogTask: Task<Void, Never>?
    private var tuneSequence = 0
    private let tokenStore = SecureTokenStore()
    #if os(iOS)
    private var webAuthSession: ASWebAuthenticationSession?
    private let webAuthPresentationProvider = WebAuthPresentationContextProvider()
    #endif

    init(api: BeyondTVAPI = .production, beyondID: BeyondIDService = .production) {
        self.api = api
        self.beyondID = beyondID
        player.automaticallyWaitsToMinimizeStalling = true
        player.preventsDisplaySleepDuringVideoPlayback = true
    }

    deinit {
        refreshTask?.cancel()
        guideTask?.cancel()
        catalogTask?.cancel()
    }

    func start() async {
        guard channels.isEmpty else { return }
        do {
            await restoreBeyondIDSession()
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

    func signInWithGoogle() async {
        #if os(iOS)
        guard !isSigningIn else { return }
        isSigningIn = true
        authErrorMessage = nil

        do {
            let callbackURL = try await authenticate(url: beyondID.googleSignInURL())
            let token = try mobileToken(from: callbackURL)
            try await loadBeyondIDSession(token: token)
            guard tokenStore.save(token) else {
                throw BeyondIDError.server("Beyond ID could not securely save this session.")
            }
        } catch {
            authErrorMessage = error.localizedDescription
        }

        isSigningIn = false
        #else
        authErrorMessage = BeyondIDError.unavailableOnPlatform.localizedDescription
        #endif
    }

    func restoreBeyondIDSession() async {
        guard let token = tokenStore.load(), !token.isEmpty else { return }
        do {
            try await loadBeyondIDSession(token: token)
        } catch {
            tokenStore.delete()
            beyondIDUser = nil
            beyondIDWallet = nil
        }
    }

    func signOutBeyondID() {
        tokenStore.delete()
        beyondIDUser = nil
        beyondIDWallet = nil
        authErrorMessage = nil
    }

    func tune(to channel: Channel) async {
        tuneSequence += 1
        let sequence = tuneSequence
        refreshTask?.cancel()
        selectedChannel = channel
        status = .loading
        isLoading = true
        errorMessage = nil
        currentSource = nil
        webPlaybackURL = nil
        player.pause()
        player.replaceCurrentItem(with: nil)

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

    func play(catalog item: CatalogItem) async {
        tuneSequence += 1
        refreshTask?.cancel()
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

    private func updateGuideItem(_ item: GuideItem) {
        if let index = guideItems.firstIndex(where: { $0.channel == item.channel }) {
            guideItems[index] = item
        } else {
            guideItems.append(item)
            guideItems.sort { $0.channel.number < $1.channel.number }
        }
    }

    private func loadBeyondIDSession(token: String) async throws {
        let session = try await beyondID.session(for: token)
        beyondIDUser = session.user
        beyondIDWallet = session.wallet
        authErrorMessage = nil
    }

    #if os(iOS)
    private func authenticate(url: URL) async throws -> URL {
        try await withCheckedThrowingContinuation { continuation in
            let session = ASWebAuthenticationSession(
                url: url,
                callbackURLScheme: "beyondtv"
            ) { callbackURL, error in
                if let error {
                    continuation.resume(throwing: error)
                    return
                }
                guard let callbackURL else {
                    continuation.resume(throwing: BeyondIDError.missingCallbackToken)
                    return
                }
                continuation.resume(returning: callbackURL)
            }
            session.presentationContextProvider = webAuthPresentationProvider
            session.prefersEphemeralWebBrowserSession = false
            webAuthSession = session
            if !session.start() {
                continuation.resume(throwing: BeyondIDError.server("Could not open Google sign-in."))
            }
        }
    }

    private func mobileToken(from callbackURL: URL) throws -> String {
        let components = URLComponents(url: callbackURL, resolvingAgainstBaseURL: false)
        if let error = components?.queryItems?.first(where: { $0.name == "error" })?.value, !error.isEmpty {
            throw BeyondIDError.server(error)
        }
        guard let token = components?.queryItems?.first(where: { $0.name == "token" })?.value, !token.isEmpty else {
            throw BeyondIDError.missingCallbackToken
        }
        return token
    }
    #endif
}
