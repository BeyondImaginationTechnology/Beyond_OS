import Foundation

@MainActor
final class MusicStore: ObservableObject {
    @Published private(set) var tracks = MusicTrack.seed
    @Published private(set) var searchResults: [MusicTrack] = []
    @Published private(set) var stations = MusicStation.seed
    @Published private(set) var playlists = MusicPlaylist.seed
    @Published private(set) var artists = ArtistSpotlight.seed
    @Published private(set) var currentTrack = MusicTrack.seed[0]
    @Published private(set) var isPlaying = false
    @Published private(set) var isSearching = false
    @Published private(set) var statusMessage = "Search and play authorized music"
    @Published private(set) var currentSearchPage = 1
    @Published private(set) var downloadStates: [MusicTrack.ID: DownloadState] = [:]
    @Published var selectedMood: MusicMood?
    @Published var searchText = ""

    private let player = AudioPlayer()
    private let searchService = OpenMusicSearchService()

    init() {
        player.configureForBackgroundPlayback()
        for track in tracks where track.isSeedDownloaded {
            downloadStates[track.id] = .downloaded
        }
    }

    var downloadedTracks: [MusicTrack] {
        allTracks.filter { isAvailableOffline($0) }
    }

    var allTracks: [MusicTrack] {
        tracks + searchResults.filter { result in !tracks.contains { $0.id == result.id } }
    }

    var filteredTracks: [MusicTrack] {
        allTracks.filter { track in
            let matchesMood = selectedMood == nil || track.mood == selectedMood
            let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
            let matchesSearch = query.isEmpty || [track.title, track.artist, track.album].contains { $0.lowercased().contains(query) }
            return matchesMood && matchesSearch
        }
    }

    var totalLibraryMinutes: Int {
        allTracks.compactMap(\.durationSeconds).reduce(0, +) / 60
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
            statusMessage = page.tracks.isEmpty ? "No authorized tracks found on page \(currentSearchPage)" : "Page \(currentSearchPage): \(page.summaryText)"
        } catch {
            statusMessage = "Search failed: \(error.localizedDescription)"
        }
    }

    func loadNextSearchPage() async {
        currentSearchPage += 1
        await searchOpenMusic(resetPage: false)
    }

    func surpriseMe() async {
        searchText = searchService.randomDiscoveryQuery()
        currentSearchPage = Int.random(in: 1...4)
        await searchOpenMusic(resetPage: true)
    }

    func play(_ track: MusicTrack) {
        currentTrack = track
        guard let url = localURL(for: track) ?? track.streamURL else {
            statusMessage = "\(track.title) needs a stream or download URL"
            isPlaying = false
            return
        }
        player.play(url: url)
        isPlaying = true
        statusMessage = isAvailableOffline(track) ? "Playing downloaded audio" : "Streaming from source"
    }

    func togglePlayback() {
        guard currentTrack.streamURL != nil || localURL(for: currentTrack) != nil else {
            statusMessage = "Choose a playable track from search first"
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
        guard let downloadURL = track.downloadURL else {
            statusMessage = "No authorized download file is available"
            return
        }

        downloadStates[track.id] = .downloading
        do {
            let (temporaryURL, _) = try await URLSession.shared.download(from: downloadURL)
            let destinationURL = localDownloadURL(for: track)
            try FileManager.default.createDirectory(at: destinationURL.deletingLastPathComponent(), withIntermediateDirectories: true)
            if FileManager.default.fileExists(atPath: destinationURL.path) {
                try FileManager.default.removeItem(at: destinationURL)
            }
            try FileManager.default.moveItem(at: temporaryURL, to: destinationURL)
            downloadStates[track.id] = .downloaded
            statusMessage = "Downloaded \(track.title)"
        } catch {
            downloadStates[track.id] = .failed(error.localizedDescription)
            statusMessage = "Download failed: \(error.localizedDescription)"
        }
    }

    func tracks(for playlist: MusicPlaylist) -> [MusicTrack] {
        playlist.trackIDs.compactMap { id in allTracks.first { $0.id == id } }
    }

    func downloadState(for track: MusicTrack) -> DownloadState {
        if isAvailableOffline(track) { return .downloaded }
        return downloadStates[track.id] ?? .idle
    }

    func isAvailableOffline(_ track: MusicTrack) -> Bool {
        track.isSeedDownloaded || FileManager.default.fileExists(atPath: localDownloadURL(for: track).path)
    }

    private func localURL(for track: MusicTrack) -> URL? {
        let url = localDownloadURL(for: track)
        return FileManager.default.fileExists(atPath: url.path) ? url : nil
    }

    private func localDownloadURL(for track: MusicTrack) -> URL {
        let baseURL = FileManager.default.urls(for: .documentDirectory, in: .userDomainMask)[0]
        return baseURL.appending(path: "BeyondMusicDownloads").appending(path: track.downloadFileName)
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
