import Foundation

struct MusicTrack: Identifiable, Hashable, Codable {
    let id: String
    var title: String
    var artist: String?
    var album: String?
    var durationSeconds: Int?
    let mood: MusicMood
    let streamURL: URL?
    let downloadURL: URL?
    let artworkURL: URL?
    let sourceURL: URL?
    let licenseNote: String?
    let providerName: String
    let localFileName: String?
    let originalFileName: String?
    let importedAt: Date?

    var displayArtist: String {
        artist?.isEmpty == false ? artist! : "Unknown Artist"
    }

    var displayAlbum: String {
        album?.isEmpty == false ? album! : "Unknown Album"
    }

    var durationText: String {
        guard let durationSeconds else { return "--:--" }
        return "\(durationSeconds / 60):\(String(format: "%02d", durationSeconds % 60))"
    }

    var downloadFileName: String {
        let preferredExtension = downloadURL?.pathExtension.isEmpty == false ? downloadURL?.pathExtension : "mp3"
        return "\(id).\(preferredExtension ?? "mp3")"
    }

    var isLocal: Bool {
        localFileName != nil
    }

    var provenanceText: String {
        if let localFileName {
            return originalFileName ?? localFileName
        }
        if let licenseNote, !licenseNote.isEmpty {
            return "\(providerName) · \(licenseNote)"
        }
        return providerName
    }

    func savedLocally(as fileName: String, originalName: String? = nil) -> MusicTrack {
        MusicTrack(
            id: id,
            title: title,
            artist: artist,
            album: album,
            durationSeconds: durationSeconds,
            mood: mood,
            streamURL: streamURL,
            downloadURL: downloadURL,
            artworkURL: artworkURL,
            sourceURL: sourceURL,
            licenseNote: licenseNote,
            providerName: providerName,
            localFileName: fileName,
            originalFileName: originalName ?? originalFileName,
            importedAt: importedAt ?? .now
        )
    }
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

enum MusicMood: String, CaseIterable, Identifiable, Hashable, Codable {
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

struct MusicPlaylist: Identifiable, Hashable {
    let id: String
    let title: String
    let subtitle: String
    let tracks: [MusicTrack]
    let systemImage: String
}

enum DownloadState: Equatable {
    case idle
    case downloading
    case downloaded
    case failed(String)
}
