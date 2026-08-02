import AVFoundation
import AuthenticationServices
import Foundation
import UIKit

@MainActor
final class MusicStore: ObservableObject {
    @Published private(set) var tracks: [MusicTrack] = []
    @Published private(set) var searchResults: [MusicTrack] = []
    @Published private(set) var currentTrack: MusicTrack?
    @Published private(set) var isPlaying = false
    @Published private(set) var isSearching = false
    @Published private(set) var isImporting = false
    @Published private(set) var statusMessage = "Import audio files or search open catalogs"
    @Published private(set) var currentSearchPage = 1
    @Published private(set) var downloadStates: [MusicTrack.ID: DownloadState] = [:]
    @Published private(set) var beyondIDSession: BeyondIDSession = .signedOut
    @Published private(set) var isAuthenticatingBeyondID = false
    @Published var selectedMood: MusicMood?
    @Published var searchText = ""
    @Published var libraryFilter: LibraryFilter = .all {
        didSet { savePreferences() }
    }
    @Published var librarySort: LibrarySort = .recentlyAdded {
        didSet { savePreferences() }
    }

    private let player = AudioPlayer()
    private let searchService = OpenMusicSearchService()
    private let youtubeAudioService = YouTubeAudioConverterService()
    private let beyondIDService = BeyondIDService()
    private let beyondIDWebAuthenticator = BeyondIDWebAuthenticator()
    private let fileManager = FileManager.default

    init() {
        player.configureForBackgroundPlayback()
        loadPreferences()
        loadLibrary()
    }

    var localTracks: [MusicTrack] {
        tracks.filter { localURL(for: $0) != nil }
    }

    var downloadedTracks: [MusicTrack] {
        localTracks.filter { $0.sourceKind == .downloaded }
    }

    var importedTracks: [MusicTrack] {
        localTracks.filter { $0.sourceKind == .imported }
    }

    var favoriteTracks: [MusicTrack] {
        tracks.filter(\.isFavorite)
    }

    var recentlyPlayedTracks: [MusicTrack] {
        tracks
            .filter { $0.lastPlayedAt != nil }
            .sorted { ($0.lastPlayedAt ?? .distantPast) > ($1.lastPlayedAt ?? .distantPast) }
    }

    var mostPlayedTracks: [MusicTrack] {
        tracks
            .filter { $0.playCount > 0 }
            .sorted {
                if $0.playCount == $1.playCount {
                    return ($0.lastPlayedAt ?? .distantPast) > ($1.lastPlayedAt ?? .distantPast)
                }
                return $0.playCount > $1.playCount
            }
    }

    var filteredTracks: [MusicTrack] {
        let filtered = tracks.filter { track in
            let matchesMood = selectedMood == nil || track.mood == selectedMood
            let matchesLibraryFilter: Bool = switch libraryFilter {
            case .all: true
            case .imported: track.sourceKind == .imported
            case .downloaded: track.sourceKind == .downloaded
            case .favorites: track.isFavorite
            }
            let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
            let searchable = [track.title, track.artist ?? "", track.album ?? "", track.originalFileName ?? ""]
            let matchesSearch = query.isEmpty || searchable.contains { $0.lowercased().contains(query) }
            return matchesMood && matchesLibraryFilter && matchesSearch
        }

        return switch librarySort {
        case .recentlyAdded:
            filtered.sorted { ($0.importedAt ?? .distantPast) > ($1.importedAt ?? .distantPast) }
        case .recentlyPlayed:
            filtered.sorted { ($0.lastPlayedAt ?? .distantPast) > ($1.lastPlayedAt ?? .distantPast) }
        case .mostPlayed:
            filtered.sorted { $0.playCount == $1.playCount ? $0.title.localizedCaseInsensitiveCompare($1.title) == .orderedAscending : $0.playCount > $1.playCount }
        case .title:
            filtered.sorted { $0.title.localizedCaseInsensitiveCompare($1.title) == .orderedAscending }
        }
    }

    var totalLibraryMinutes: Int {
        tracks.compactMap(\.durationSeconds).reduce(0, +) / 60
    }

    var playlists: [MusicPlaylist] {
        [
            MusicPlaylist(
                id: "downloaded",
                title: "Downloaded",
                subtitle: "\(downloadedTracks.count) files stored on this device",
                tracks: downloadedTracks,
                systemImage: "arrow.down.circle.fill"
            ),
            MusicPlaylist(
                id: "imported",
                title: "Imported Files",
                subtitle: "\(importedTracks.count) files copied from Files",
                tracks: importedTracks,
                systemImage: "folder.fill"
            ),
            MusicPlaylist(
                id: "favorites",
                title: "Favorites",
                subtitle: "\(favoriteTracks.count) saved choices",
                tracks: favoriteTracks,
                systemImage: "heart.fill"
            ),
            MusicPlaylist(
                id: "recent",
                title: "Recently Added",
                subtitle: "Latest imported and downloaded songs",
                tracks: tracks.sorted { ($0.importedAt ?? .distantPast) > ($1.importedAt ?? .distantPast) },
                systemImage: "clock.fill"
            )
        ]
    }

    var hasBeyondID: Bool {
        beyondIDSession.isConnected
    }

    func searchOpenMusic(resetPage: Bool = true) async {
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard query.count >= 2 else {
            statusMessage = "Type at least two characters to search"
            return
        }

        if resetPage {
            currentSearchPage = 1
        }

        isSearching = true
        defer { isSearching = false }

        do {
            let page = try await searchService.search(query: query, page: currentSearchPage)
            searchResults = resetPage ? page.tracks : searchResults + page.tracks.filter { incoming in !searchResults.contains { $0.id == incoming.id } }
            statusMessage = page.tracks.isEmpty ? "No tracks found on page \(currentSearchPage)" : "Page \(currentSearchPage): \(page.summaryText)"
        } catch {
            statusMessage = "Search failed: \(error.localizedDescription)"
        }
    }

    func loadNextSearchPage() async {
        currentSearchPage += 1
        await searchOpenMusic(resetPage: false)
    }

    func play(_ track: MusicTrack) {
        currentTrack = track
        guard let url = localURL(for: track) ?? track.streamURL else {
            statusMessage = "\(track.title) is not playable yet"
            isPlaying = false
            return
        }
        player.play(url: url)
        isPlaying = true
        recordPlayback(for: track)
        statusMessage = localURL(for: track) == nil ? "Streaming \(track.title)" : "Playing \(track.title)"
    }

    func togglePlayback() {
        guard let currentTrack else {
            statusMessage = tracks.isEmpty ? "Import or download a song first" : "Choose a track to play"
            return
        }

        if isPlaying {
            player.pause()
            isPlaying = false
        } else {
            play(currentTrack)
        }
    }

    func download(_ track: MusicTrack) async {
        if track.providerName == "YouTube" {
            await downloadYouTubeTrack(track)
            return
        }

        guard let downloadURL = track.downloadURL else {
            statusMessage = "No downloadable file is available for this track"
            return
        }

        downloadStates[track.id] = .downloading
        do {
            try ensureStorage()
            let (temporaryURL, _) = try await URLSession.shared.download(from: downloadURL)
            let fileName = uniqueStoredFileName(for: track.downloadFileName)
            let destinationURL = audioDirectory.appendingPathComponent(fileName)
            if fileManager.fileExists(atPath: destinationURL.path) {
                try fileManager.removeItem(at: destinationURL)
            }
            try fileManager.moveItem(at: temporaryURL, to: destinationURL)
            let enrichedTrack = try await trackWithFileMetadata(track.savedLocally(as: fileName, originalName: downloadURL.lastPathComponent), fileURL: destinationURL)
            upsertLibraryTrack(enrichedTrack)
            downloadStates[track.id] = .downloaded
            statusMessage = "Saved \(enrichedTrack.title)"
        } catch {
            downloadStates[track.id] = .failed(error.localizedDescription)
            statusMessage = "Download failed: \(error.localizedDescription)"
        }
    }

    private func downloadYouTubeTrack(_ track: MusicTrack) async {
        guard let sourceURL = track.sourceURL else {
            statusMessage = "No YouTube URL is available for this result"
            return
        }

        downloadStates[track.id] = .downloading
        do {
            try ensureStorage()
            let mp3URL = try await youtubeAudioService.mp3URL(for: sourceURL)
            let (temporaryURL, _) = try await URLSession.shared.download(from: mp3URL)
            let fileName = uniqueStoredFileName(for: "\(track.id).mp3")
            let destinationURL = audioDirectory.appendingPathComponent(fileName)
            if fileManager.fileExists(atPath: destinationURL.path) {
                try fileManager.removeItem(at: destinationURL)
            }
            try fileManager.moveItem(at: temporaryURL, to: destinationURL)
            let localTrack = MusicTrack(
                id: track.id,
                title: track.title,
                artist: track.artist,
                album: track.album,
                durationSeconds: track.durationSeconds,
                mood: track.mood,
                streamURL: nil,
                downloadURL: mp3URL,
                artworkURL: track.artworkURL,
                sourceURL: track.sourceURL,
                licenseNote: track.licenseNote,
                providerName: track.providerName,
                localFileName: fileName,
                originalFileName: sourceURL.lastPathComponent.isEmpty ? sourceURL.absoluteString : sourceURL.lastPathComponent,
                importedAt: .now,
                playCount: track.playCount,
                lastPlayedAt: track.lastPlayedAt,
                isFavorite: track.isFavorite
            )
            let enrichedTrack = try await trackWithFileMetadata(localTrack, fileURL: destinationURL)
            upsertLibraryTrack(enrichedTrack)
            downloadStates[track.id] = .downloaded
            statusMessage = "Saved \(enrichedTrack.title)"
        } catch {
            downloadStates[track.id] = .failed(error.localizedDescription)
            statusMessage = "YouTube download failed: \(error.localizedDescription)"
        }
    }

    func importAudioFiles(from urls: [URL]) async {
        guard !urls.isEmpty else { return }
        isImporting = true
        defer { isImporting = false }

        var importedCount = 0
        var failedCount = 0

        for url in urls {
            do {
                let track = try await importAudioFile(from: url)
                upsertLibraryTrack(track)
                importedCount += 1
            } catch {
                failedCount += 1
            }
        }

        if importedCount > 0 {
            statusMessage = failedCount == 0 ? "Imported \(importedCount) audio file\(importedCount == 1 ? "" : "s")" : "Imported \(importedCount), skipped \(failedCount)"
        } else {
            statusMessage = "No supported audio files were imported"
        }
    }

    func tracks(for playlist: MusicPlaylist) -> [MusicTrack] {
        playlist.tracks
    }

    func downloadState(for track: MusicTrack) -> DownloadState {
        if localURL(for: track) != nil { return .downloaded }
        return downloadStates[track.id] ?? .idle
    }

    func isAvailableOffline(_ track: MusicTrack) -> Bool {
        localURL(for: track) != nil
    }

    func remove(_ track: MusicTrack) {
        if let url = localURL(for: track), fileManager.fileExists(atPath: url.path) {
            try? fileManager.removeItem(at: url)
        }
        tracks.removeAll { $0.id == track.id }
        if currentTrack?.id == track.id {
            player.pause()
            currentTrack = nil
            isPlaying = false
        }
        saveLibrary()
    }

    func toggleFavorite(_ track: MusicTrack) {
        guard let index = tracks.firstIndex(where: { $0.id == track.id }) else { return }
        tracks[index].isFavorite.toggle()
        if currentTrack?.id == track.id {
            currentTrack = tracks[index]
        }
        saveLibrary()
    }

    func refreshBeyondIDSession() async {
        isAuthenticatingBeyondID = true
        defer { isAuthenticatingBeyondID = false }

        do {
            beyondIDSession = try await beyondIDService.currentSession()
            statusMessage = "Beyond ID session verified"
            savePreferences()
        } catch BeyondIDError.unauthorized {
            if let mobileToken = beyondIDSession.mobileToken {
                do {
                    beyondIDSession = try await beyondIDService.mobileSession(token: mobileToken)
                    statusMessage = "Beyond ID session verified"
                    savePreferences()
                } catch {
                    beyondIDSession = .signedOut
                    savePreferences()
                }
            } else {
                beyondIDSession = .signedOut
                savePreferences()
            }
        } catch {
            statusMessage = "Beyond ID check failed: \(error.localizedDescription)"
        }
    }

    func signInBeyondID(email: String, password: String) async {
        isAuthenticatingBeyondID = true
        defer { isAuthenticatingBeyondID = false }

        do {
            beyondIDSession = try await beyondIDService.signIn(email: email, password: password)
            statusMessage = "Signed in with Beyond ID"
            savePreferences()
        } catch {
            statusMessage = "Beyond ID sign in failed: \(error.localizedDescription)"
        }
    }

    func registerBeyondID(firstName: String, lastName: String, email: String, password: String) async {
        isAuthenticatingBeyondID = true
        defer { isAuthenticatingBeyondID = false }

        do {
            try await beyondIDService.register(firstName: firstName, lastName: lastName, email: email, password: password)
            statusMessage = "Beyond ID created. Check your email to verify, then sign in."
        } catch {
            statusMessage = "Beyond ID registration failed: \(error.localizedDescription)"
        }
    }

    func signInBeyondIDWithGoogle() async {
        isAuthenticatingBeyondID = true
        defer { isAuthenticatingBeyondID = false }

        do {
            let url = beyondIDService.googleSignInURL()
            let callbackURL = try await beyondIDWebAuthenticator.authenticate(url: url, callbackScheme: "beyondmusic")
            beyondIDSession = try await beyondIDService.completeMobileSignIn(callbackURL: callbackURL)
            statusMessage = "Signed in with Google"
            savePreferences()
        } catch {
            statusMessage = "Google sign in failed: \(error.localizedDescription)"
        }
    }

    func signOutBeyondID() async {
        isAuthenticatingBeyondID = true
        defer { isAuthenticatingBeyondID = false }

        do {
            try await beyondIDService.signOut()
        } catch {
            statusMessage = "Beyond ID sign out could not reach the server"
        }
        beyondIDSession = .signedOut
        savePreferences()
    }

    private func importAudioFile(from sourceURL: URL) async throws -> MusicTrack {
        try ensureStorage()
        let accessed = sourceURL.startAccessingSecurityScopedResource()
        defer {
            if accessed {
                sourceURL.stopAccessingSecurityScopedResource()
            }
        }

        let fileName = uniqueStoredFileName(for: sourceURL.lastPathComponent)
        let destinationURL = audioDirectory.appendingPathComponent(fileName)
        if fileManager.fileExists(atPath: destinationURL.path) {
            try fileManager.removeItem(at: destinationURL)
        }
        try fileManager.copyItem(at: sourceURL, to: destinationURL)

        let baseTrack = MusicTrack(
            id: stableID(for: destinationURL),
            title: sourceURL.deletingPathExtension().lastPathComponent,
            artist: nil,
            album: nil,
            durationSeconds: nil,
            mood: .focus,
            streamURL: nil,
            downloadURL: nil,
            artworkURL: nil,
            sourceURL: nil,
            licenseNote: nil,
            providerName: "Imported File",
            localFileName: fileName,
            originalFileName: sourceURL.lastPathComponent,
            importedAt: .now,
            playCount: 0,
            lastPlayedAt: nil,
            isFavorite: false
        )
        return try await trackWithFileMetadata(baseTrack, fileURL: destinationURL)
    }

    private func trackWithFileMetadata(_ track: MusicTrack, fileURL: URL) async throws -> MusicTrack {
        let asset = AVURLAsset(url: fileURL)
        let duration = try? await asset.load(.duration)
        let metadata = try? await asset.load(.commonMetadata)
        var title = track.title
        var artist = track.artist
        var album = track.album

        if let metadata {
            for item in metadata {
                guard let key = item.commonKey?.rawValue else { continue }
                let value = try? await item.load(.stringValue)
                switch key {
                case AVMetadataKey.commonKeyTitle.rawValue:
                    if let value, !value.isEmpty { title = value }
                case AVMetadataKey.commonKeyArtist.rawValue:
                    if let value, !value.isEmpty { artist = value }
                case AVMetadataKey.commonKeyAlbumName.rawValue:
                    if let value, !value.isEmpty { album = value }
                default:
                    break
                }
            }
        }

        let seconds = duration.map { Int(CMTimeGetSeconds($0).rounded()) }
        return MusicTrack(
            id: track.id,
            title: title,
            artist: artist,
            album: album,
            durationSeconds: seconds ?? track.durationSeconds,
            mood: track.mood,
            streamURL: track.streamURL,
            downloadURL: track.downloadURL,
            artworkURL: track.artworkURL,
            sourceURL: track.sourceURL,
            licenseNote: track.licenseNote,
            providerName: track.providerName,
            localFileName: track.localFileName,
            originalFileName: track.originalFileName,
            importedAt: track.importedAt,
            playCount: track.playCount,
            lastPlayedAt: track.lastPlayedAt,
            isFavorite: track.isFavorite
        )
    }

    private func upsertLibraryTrack(_ track: MusicTrack) {
        if let index = tracks.firstIndex(where: { $0.id == track.id }) {
            tracks[index] = track
        } else {
            tracks.insert(track, at: 0)
        }
        currentTrack = currentTrack ?? track
        saveLibrary()
    }

    private func recordPlayback(for track: MusicTrack) {
        guard let index = tracks.firstIndex(where: { $0.id == track.id }) else { return }
        tracks[index].playCount += 1
        tracks[index].lastPlayedAt = .now
        currentTrack = tracks[index]
        saveLibrary()
    }

    private func loadLibrary() {
        do {
            let data = try Data(contentsOf: libraryIndexURL)
            tracks = try JSONDecoder().decode([MusicTrack].self, from: data).filter { localURL(for: $0) != nil }
            currentTrack = tracks.first
            for track in tracks {
                downloadStates[track.id] = .downloaded
            }
        } catch {
            tracks = []
            currentTrack = nil
        }
    }

    private func saveLibrary() {
        do {
            try ensureStorage()
            let data = try JSONEncoder().encode(tracks)
            try data.write(to: libraryIndexURL, options: [.atomic])
        } catch {
            statusMessage = "Could not save library index"
        }
    }

    private func loadPreferences() {
        do {
            let data = try Data(contentsOf: preferencesURL)
            let preferences = try JSONDecoder().decode(MusicPreferences.self, from: data)
            libraryFilter = preferences.libraryFilter
            librarySort = preferences.librarySort
            beyondIDSession = preferences.beyondIDSession
        } catch {
            libraryFilter = .all
            librarySort = .recentlyAdded
            beyondIDSession = .signedOut
        }
    }

    private func savePreferences() {
        do {
            try ensureStorage()
            let preferences = MusicPreferences(
                libraryFilter: libraryFilter,
                librarySort: librarySort,
                beyondIDSession: beyondIDSession
            )
            let data = try JSONEncoder().encode(preferences)
            try data.write(to: preferencesURL, options: [.atomic])
        } catch {
            statusMessage = "Could not save music preferences"
        }
    }

    private func localURL(for track: MusicTrack) -> URL? {
        guard let fileName = track.localFileName else { return nil }
        let url = audioDirectory.appendingPathComponent(fileName)
        return fileManager.fileExists(atPath: url.path) ? url : nil
    }

    private func uniqueStoredFileName(for proposedName: String) -> String {
        let cleanName = proposedName.sanitizedFileName
        let base = URL(fileURLWithPath: cleanName).deletingPathExtension().lastPathComponent
        let ext = URL(fileURLWithPath: cleanName).pathExtension.isEmpty ? "mp3" : URL(fileURLWithPath: cleanName).pathExtension
        var candidate = "\(base).\(ext)"
        var counter = 2
        while fileManager.fileExists(atPath: audioDirectory.appendingPathComponent(candidate).path) {
            candidate = "\(base)-\(counter).\(ext)"
            counter += 1
        }
        return candidate
    }

    private func stableID(for url: URL) -> String {
        "\(url.deletingPathExtension().lastPathComponent)-\(UUID().uuidString)".stableMusicID
    }

    private func ensureStorage() throws {
        try fileManager.createDirectory(at: audioDirectory, withIntermediateDirectories: true)
        try fileManager.createDirectory(at: libraryIndexURL.deletingLastPathComponent(), withIntermediateDirectories: true)
    }

    private var audioDirectory: URL {
        fileManager.urls(for: .documentDirectory, in: .userDomainMask)[0].appendingPathComponent("BeyondMusicLibrary", isDirectory: true)
    }

    private var libraryIndexURL: URL {
        fileManager.urls(for: .applicationSupportDirectory, in: .userDomainMask)[0]
            .appendingPathComponent("BeyondMusic", isDirectory: true)
            .appendingPathComponent("Library.json")
    }

    private var preferencesURL: URL {
        fileManager.urls(for: .applicationSupportDirectory, in: .userDomainMask)[0]
            .appendingPathComponent("BeyondMusic", isDirectory: true)
            .appendingPathComponent("Preferences.json")
    }
}

extension Int {
    var compactCountText: String {
        if self >= 1000 {
            return String(format: "%.1fk", Double(self) / 1000)
        }
        return "\(self)"
    }
}

private struct YouTubeAudioConverterService {
    private let session: URLSession
    private let decoder = JSONDecoder()

    init(session: URLSession = .shared) {
        self.session = session
    }

    func mp3URL(for youtubeURL: URL) async throws -> URL {
        let baseURL = try configuredBaseURL()
        var conversionComponents = URLComponents(url: baseURL, resolvingAgainstBaseURL: false)
        conversionComponents?.queryItems = [URLQueryItem(name: "url", value: youtubeURL.absoluteString)]
        guard let conversionURL = conversionComponents?.url else { throw URLError(.badURL) }

        let (data, response) = try await session.data(from: conversionURL)
        try validate(response: response, data: data)
        let payload = try decoder.decode(YouTubeAudioConversionResponse.self, from: data)
        guard !payload.token.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty else {
            throw YouTubeAudioError.server("Converter did not return a token")
        }
        if let downloadURL = payload.downloadURL {
            return downloadURL
        }

        var downloadComponents = URLComponents(url: baseURL.appending(path: "/download"), resolvingAgainstBaseURL: false)
        downloadComponents?.queryItems = [URLQueryItem(name: "token", value: payload.token)]
        guard let downloadURL = downloadComponents?.url else { throw URLError(.badURL) }
        return downloadURL
    }

    private func configuredBaseURL() throws -> URL {
        guard let rawValue = Bundle.main.object(forInfoDictionaryKey: "YouTubeAudioAPIBaseURL") as? String,
              let url = URL(string: rawValue.trimmingCharacters(in: .whitespacesAndNewlines)),
              ["http", "https"].contains(url.scheme?.lowercased())
        else {
            throw YouTubeAudioError.notConfigured
        }
        return url
    }

    private func validate(response: URLResponse, data: Data) throws {
        guard let httpResponse = response as? HTTPURLResponse else { return }
        guard (200...299).contains(httpResponse.statusCode) else {
            let message = String(data: data, encoding: .utf8) ?? "HTTP \(httpResponse.statusCode)"
            throw YouTubeAudioError.server(message)
        }
    }
}

private struct YouTubeAudioConversionResponse: Decodable {
    let token: String
    let downloadURL: URL?

    enum CodingKeys: String, CodingKey {
        case token
        case downloadURL = "download_url"
    }
}

private enum YouTubeAudioError: LocalizedError {
    case notConfigured
    case server(String)

    var errorDescription: String? {
        switch self {
        case .notConfigured:
            "Set YouTubeAudioAPIBaseURL to the yt-audio-api endpoint"
        case .server(let message):
            message
        }
    }
}

private struct BeyondIDService {
    private let session: URLSession
    private let decoder = JSONDecoder()

    init(session: URLSession = .shared) {
        self.session = session
    }

    func currentSession() async throws -> BeyondIDSession {
        let url = baseURL.appending(path: "/beyond-id/api/me.php")
        let (data, response) = try await session.data(from: url)
        try validate(response: response, data: data)
        let payload = try decoder.decode(BeyondIDMeResponse.self, from: data)
        guard payload.ok, payload.authenticated, let user = payload.user else {
            throw BeyondIDError.unauthorized
        }
        return BeyondIDSession(user: user, wallet: payload.wallet, connectedAt: .now)
    }

    func signIn(email: String, password: String) async throws -> BeyondIDSession {
        let body = [
            "email": email.trimmingCharacters(in: .whitespacesAndNewlines),
            "password": password
        ]
        let data = try await postJSON(path: "/beyond-id/api/login.php", body: body)
        let payload = try decoder.decode(BeyondIDLoginResponse.self, from: data)
        guard payload.ok, let user = payload.user else {
            throw BeyondIDError.server(payload.error ?? "Sign in failed")
        }
        do {
            return try await currentSession()
        } catch {
            return BeyondIDSession(user: user, wallet: nil, connectedAt: .now)
        }
    }

    func register(firstName: String, lastName: String, email: String, password: String) async throws {
        let body = [
            "first_name": firstName.trimmingCharacters(in: .whitespacesAndNewlines),
            "last_name": lastName.trimmingCharacters(in: .whitespacesAndNewlines),
            "email": email.trimmingCharacters(in: .whitespacesAndNewlines),
            "password": password,
            "locale": Locale.current.language.languageCode?.identifier ?? "en"
        ]
        let data = try await postJSON(path: "/beyond-id/api/register.php", body: body)
        let payload = try decoder.decode(BeyondIDRegisterResponse.self, from: data)
        guard payload.ok else {
            throw BeyondIDError.server(payload.error ?? "Registration failed")
        }
    }

    func googleSignInURL() -> URL {
        var components = URLComponents(url: baseURL.appending(path: "/beyond-id/auth/oauth-start.php"), resolvingAgainstBaseURL: false)
        components?.queryItems = [
            URLQueryItem(name: "provider", value: "google"),
            URLQueryItem(name: "app", value: "beyond-music"),
            URLQueryItem(name: "return", value: "/beyond-id/auth/mobile-complete.php")
        ]
        return components?.url ?? baseURL.appending(path: "/beyond-id/auth/login.php")
    }

    func completeMobileSignIn(callbackURL: URL) async throws -> BeyondIDSession {
        guard let components = URLComponents(url: callbackURL, resolvingAgainstBaseURL: false),
              let token = components.queryItems?.first(where: { $0.name == "token" })?.value,
              !token.isEmpty
        else {
            let message = URLComponents(url: callbackURL, resolvingAgainstBaseURL: false)?
                .queryItems?
                .first(where: { $0.name == "error" })?
                .value ?? "Google sign in did not return a mobile token"
            throw BeyondIDError.server(message)
        }
        return try await mobileSession(token: token)
    }

    func mobileSession(token: String) async throws -> BeyondIDSession {
        var components = URLComponents(url: baseURL.appending(path: "/beyond-id/api/mobile-session.php"), resolvingAgainstBaseURL: false)
        components?.queryItems = [URLQueryItem(name: "token", value: token)]
        guard let url = components?.url else { throw URLError(.badURL) }
        let (data, response) = try await session.data(from: url)
        try validate(response: response, data: data)
        let payload = try decoder.decode(BeyondIDMeResponse.self, from: data)
        guard payload.ok, payload.authenticated, let user = payload.user else {
            throw BeyondIDError.unauthorized
        }
        return BeyondIDSession(user: user, wallet: payload.wallet, connectedAt: .now, mobileToken: token)
    }

    func signOut() async throws {
        let url = baseURL.appending(path: "/beyond-id/auth/logout.php")
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        _ = try await session.data(for: request)
    }

    private func postJSON(path: String, body: [String: String]) async throws -> Data {
        let url = baseURL.appending(path: path)
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.httpBody = try JSONEncoder().encode(body)
        let (data, response) = try await session.data(for: request)
        try validate(response: response, data: data)
        return data
    }

    private func validate(response: URLResponse, data: Data) throws {
        guard let httpResponse = response as? HTTPURLResponse else { return }
        guard (200..<300).contains(httpResponse.statusCode) else {
            if httpResponse.statusCode == 401 {
                throw BeyondIDError.unauthorized
            }
            if let apiError = try? decoder.decode(BeyondIDAPIError.self, from: data),
               let message = apiError.error {
                throw BeyondIDError.server(message)
            }
            throw BeyondIDError.server("Beyond ID returned HTTP \(httpResponse.statusCode)")
        }
    }

    private var baseURL: URL {
        if let rawValue = Bundle.main.object(forInfoDictionaryKey: "BeyondIDBaseURL") as? String,
           let url = URL(string: rawValue.trimmingCharacters(in: .whitespacesAndNewlines)),
           !rawValue.isEmpty {
            return url
        }
        return URL(string: "https://beyondimagination.co.technology")!
    }
}

private enum BeyondIDError: LocalizedError, Equatable {
    case unauthorized
    case server(String)

    var errorDescription: String? {
        switch self {
        case .unauthorized:
            "No active Beyond ID session"
        case .server(let message):
            message
        }
    }
}

@MainActor
private final class BeyondIDWebAuthenticator: NSObject, ASWebAuthenticationPresentationContextProviding {
    private var currentSession: ASWebAuthenticationSession?

    func authenticate(url: URL, callbackScheme: String) async throws -> URL {
        try await withCheckedThrowingContinuation { continuation in
            let session = ASWebAuthenticationSession(url: url, callbackURLScheme: callbackScheme) { [weak self] callbackURL, error in
                self?.currentSession = nil
                if let callbackURL {
                    continuation.resume(returning: callbackURL)
                } else {
                    continuation.resume(throwing: error ?? BeyondIDError.server("Google sign in was cancelled"))
                }
            }
            session.presentationContextProvider = self
            session.prefersEphemeralWebBrowserSession = false
            currentSession = session
            if !session.start() {
                currentSession = nil
                continuation.resume(throwing: BeyondIDError.server("Could not start Google sign in"))
            }
        }
    }

    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        UIApplication.shared.connectedScenes
            .compactMap { $0 as? UIWindowScene }
            .flatMap { $0.windows }
            .first { $0.isKeyWindow } ?? ASPresentationAnchor()
    }
}

private struct BeyondIDAPIError: Decodable {
    let error: String?
}

private struct BeyondIDLoginResponse: Decodable {
    let ok: Bool
    let error: String?
    let user: BeyondIDUser?
}

private struct BeyondIDRegisterResponse: Decodable {
    let ok: Bool
    let error: String?
    let verificationRequired: Bool?

    private enum CodingKeys: String, CodingKey {
        case ok
        case error
        case verificationRequired = "verification_required"
    }
}

private struct BeyondIDMeResponse: Decodable {
    let ok: Bool
    let authenticated: Bool
    let user: BeyondIDUser?
    let wallet: BeyondIDWallet?
}

private struct BeyondIDUser: Decodable {
    let id: FlexibleInt?
    let email: String?
    let name: String?
    let firstName: String?
    let lastName: String?
    let displayName: String?
    let role: String?
    let locale: String?
    let preferredLocale: String?

    private enum CodingKeys: String, CodingKey {
        case id
        case email
        case name
        case firstName = "first_name"
        case lastName = "last_name"
        case displayName = "display_name"
        case role
        case locale
        case preferredLocale = "preferred_locale"
    }

    var bestDisplayName: String {
        let fullName = [firstName, lastName]
            .compactMap { $0?.trimmingCharacters(in: .whitespacesAndNewlines) }
            .filter { !$0.isEmpty }
            .joined(separator: " ")
        return [displayName, name, fullName, email]
            .compactMap { $0?.trimmingCharacters(in: .whitespacesAndNewlines) }
            .first { !$0.isEmpty } ?? "Beyond ID"
    }

    var bestLocale: String? {
        locale ?? preferredLocale
    }
}

private struct BeyondIDWallet: Decodable {
    let balance: FlexibleString?
    let currency: String?
}

private struct FlexibleInt: Decodable {
    let value: Int

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()
        if let intValue = try? container.decode(Int.self) {
            value = intValue
        } else if let stringValue = try? container.decode(String.self), let intValue = Int(stringValue) {
            value = intValue
        } else {
            throw DecodingError.typeMismatch(Int.self, DecodingError.Context(codingPath: decoder.codingPath, debugDescription: "Expected int or numeric string"))
        }
    }
}

private struct FlexibleString: Decodable {
    let value: String

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()
        if let stringValue = try? container.decode(String.self) {
            value = stringValue
        } else if let doubleValue = try? container.decode(Double.self) {
            value = String(format: "%.2f", doubleValue)
        } else if let intValue = try? container.decode(Int.self) {
            value = "\(intValue)"
        } else {
            throw DecodingError.typeMismatch(String.self, DecodingError.Context(codingPath: decoder.codingPath, debugDescription: "Expected string or number"))
        }
    }
}

private extension BeyondIDSession {
    init(user: BeyondIDUser, wallet: BeyondIDWallet?, connectedAt: Date, mobileToken: String? = nil) {
        self.init(
            isConnected: true,
            userID: user.id?.value,
            displayName: user.bestDisplayName,
            email: user.email ?? "",
            role: user.role,
            locale: user.bestLocale,
            walletBalance: wallet?.balance?.value,
            walletCurrency: wallet?.currency,
            connectedAt: connectedAt,
            mobileToken: mobileToken
        )
    }
}

private extension String {
    var sanitizedFileName: String {
        let allowed = CharacterSet(charactersIn: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789._- ")
        let filtered = unicodeScalars.map { allowed.contains($0) ? Character($0) : "-" }
        let name = String(filtered).trimmingCharacters(in: .whitespacesAndNewlines)
        return name.isEmpty ? "audio-file.mp3" : name
    }

    var stableMusicID: String {
        lowercased()
            .map { character in character.isLetter || character.isNumber ? character : "-" }
            .reduce(into: "") { result, character in
                if character != "-" || result.last != "-" {
                    result.append(character)
                }
            }
            .trimmingCharacters(in: CharacterSet(charactersIn: "-"))
    }
}
