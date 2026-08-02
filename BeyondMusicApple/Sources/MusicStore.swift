import AVFoundation
import Foundation

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
    @Published var selectedMood: MusicMood?
    @Published var searchText = ""

    private let player = AudioPlayer()
    private let searchService = OpenMusicSearchService()
    private let fileManager = FileManager.default

    init() {
        player.configureForBackgroundPlayback()
        loadLibrary()
    }

    var downloadedTracks: [MusicTrack] {
        tracks.filter { localURL(for: $0) != nil }
    }

    var filteredTracks: [MusicTrack] {
        tracks.filter { track in
            let matchesMood = selectedMood == nil || track.mood == selectedMood
            let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
            let searchable = [track.title, track.artist ?? "", track.album ?? "", track.originalFileName ?? ""]
            let matchesSearch = query.isEmpty || searchable.contains { $0.lowercased().contains(query) }
            return matchesMood && matchesSearch
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
                id: "recent",
                title: "Recently Added",
                subtitle: "Latest imported and downloaded songs",
                tracks: tracks.sorted { ($0.importedAt ?? .distantPast) > ($1.importedAt ?? .distantPast) },
                systemImage: "clock.fill"
            )
        ]
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
            statusMessage = "\(track.title) is not playable yet"
            isPlaying = false
            return
        }
        player.play(url: url)
        isPlaying = true
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
            importedAt: .now
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
            importedAt: track.importedAt
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
}

extension Int {
    var compactCountText: String {
        if self >= 1000 {
            return String(format: "%.1fk", Double(self) / 1000)
        }
        return "\(self)"
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
