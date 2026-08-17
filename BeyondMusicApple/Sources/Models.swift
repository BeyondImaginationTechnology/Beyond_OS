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
    var playCount: Int
    var lastPlayedAt: Date?
    var isFavorite: Bool

    init(
        id: String,
        title: String,
        artist: String?,
        album: String?,
        durationSeconds: Int?,
        mood: MusicMood,
        streamURL: URL?,
        downloadURL: URL?,
        artworkURL: URL?,
        sourceURL: URL?,
        licenseNote: String?,
        providerName: String,
        localFileName: String?,
        originalFileName: String?,
        importedAt: Date?,
        playCount: Int = 0,
        lastPlayedAt: Date? = nil,
        isFavorite: Bool = false
    ) {
        self.id = id
        self.title = title
        self.artist = artist
        self.album = album
        self.durationSeconds = durationSeconds
        self.mood = mood
        self.streamURL = streamURL
        self.downloadURL = downloadURL
        self.artworkURL = artworkURL
        self.sourceURL = sourceURL
        self.licenseNote = licenseNote
        self.providerName = providerName
        self.localFileName = localFileName
        self.originalFileName = originalFileName
        self.importedAt = importedAt
        self.playCount = playCount
        self.lastPlayedAt = lastPlayedAt
        self.isFavorite = isFavorite
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        id = try container.decode(String.self, forKey: .id)
        title = try container.decode(String.self, forKey: .title)
        artist = try container.decodeIfPresent(String.self, forKey: .artist)
        album = try container.decodeIfPresent(String.self, forKey: .album)
        durationSeconds = try container.decodeIfPresent(Int.self, forKey: .durationSeconds)
        mood = try container.decode(MusicMood.self, forKey: .mood)
        streamURL = try container.decodeIfPresent(URL.self, forKey: .streamURL)
        downloadURL = try container.decodeIfPresent(URL.self, forKey: .downloadURL)
        artworkURL = try container.decodeIfPresent(URL.self, forKey: .artworkURL)
        sourceURL = try container.decodeIfPresent(URL.self, forKey: .sourceURL)
        licenseNote = try container.decodeIfPresent(String.self, forKey: .licenseNote)
        providerName = try container.decode(String.self, forKey: .providerName)
        localFileName = try container.decodeIfPresent(String.self, forKey: .localFileName)
        originalFileName = try container.decodeIfPresent(String.self, forKey: .originalFileName)
        importedAt = try container.decodeIfPresent(Date.self, forKey: .importedAt)
        playCount = try container.decodeIfPresent(Int.self, forKey: .playCount) ?? 0
        lastPlayedAt = try container.decodeIfPresent(Date.self, forKey: .lastPlayedAt)
        isFavorite = try container.decodeIfPresent(Bool.self, forKey: .isFavorite) ?? false
    }

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

    var sourceKind: MusicSourceKind {
        providerName == "Imported File" ? .imported : .downloaded
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
            importedAt: importedAt ?? .now,
            playCount: playCount,
            lastPlayedAt: lastPlayedAt,
            isFavorite: isFavorite
        )
    }
}

enum MusicSourceKind: String, Codable, Hashable {
    case imported = "Imported"
    case downloaded = "Downloaded"
}

enum LibraryFilter: String, CaseIterable, Identifiable, Codable {
    case all = "All"
    case imported = "Imported"
    case downloaded = "Downloaded"
    case favorites = "Favorites"

    var id: String { rawValue }
}

enum LibrarySort: String, CaseIterable, Identifiable, Codable {
    case recentlyAdded = "Recently Added"
    case recentlyPlayed = "Recently Played"
    case mostPlayed = "Most Played"
    case title = "Title"

    var id: String { rawValue }
}

struct BeyondIDSession: Codable, Hashable {
    var isConnected: Bool
    var userID: Int?
    var displayName: String
    var email: String
    var role: String?
    var locale: String?
    var walletBalance: String?
    var walletCurrency: String?
    var connectedAt: Date?
    var mobileToken: String?

    static let signedOut = BeyondIDSession(
        isConnected: false,
        userID: nil,
        displayName: "",
        email: "",
        role: nil,
        locale: nil,
        walletBalance: nil,
        walletCurrency: nil,
        connectedAt: nil,
        mobileToken: nil
    )

    init(
        isConnected: Bool,
        userID: Int? = nil,
        displayName: String,
        email: String,
        role: String? = nil,
        locale: String? = nil,
        walletBalance: String? = nil,
        walletCurrency: String? = nil,
        connectedAt: Date?,
        mobileToken: String? = nil
    ) {
        self.isConnected = isConnected
        self.userID = userID
        self.displayName = displayName
        self.email = email
        self.role = role
        self.locale = locale
        self.walletBalance = walletBalance
        self.walletCurrency = walletCurrency
        self.connectedAt = connectedAt
        self.mobileToken = mobileToken
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        isConnected = try container.decode(Bool.self, forKey: .isConnected)
        userID = try container.decodeIfPresent(Int.self, forKey: .userID)
        displayName = try container.decodeIfPresent(String.self, forKey: .displayName) ?? ""
        email = try container.decodeIfPresent(String.self, forKey: .email) ?? ""
        role = try container.decodeIfPresent(String.self, forKey: .role)
        locale = try container.decodeIfPresent(String.self, forKey: .locale)
        walletBalance = try container.decodeIfPresent(String.self, forKey: .walletBalance)
        walletCurrency = try container.decodeIfPresent(String.self, forKey: .walletCurrency)
        connectedAt = try container.decodeIfPresent(Date.self, forKey: .connectedAt)
        mobileToken = try container.decodeIfPresent(String.self, forKey: .mobileToken)
    }

    var label: String {
        if isConnected {
            return displayName.isEmpty ? "Beyond ID connected" : displayName
        }
        return "Not connected"
    }

    var walletText: String {
        guard let walletBalance, let walletCurrency else { return "Unavailable" }
        return "\(walletBalance) \(walletCurrency)"
    }
}

struct MusicPreferences: Codable, Hashable {
    var libraryFilter: LibraryFilter = .all
    var librarySort: LibrarySort = .recentlyAdded
    var beyondIDSession: BeyondIDSession = .signedOut
    var searchProviderFilter: MusicProviderFilter = .all

    init(
        libraryFilter: LibraryFilter = .all,
        librarySort: LibrarySort = .recentlyAdded,
        beyondIDSession: BeyondIDSession = .signedOut,
        searchProviderFilter: MusicProviderFilter = .all
    ) {
        self.libraryFilter = libraryFilter
        self.librarySort = librarySort
        self.beyondIDSession = beyondIDSession
        self.searchProviderFilter = searchProviderFilter
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        libraryFilter = try container.decodeIfPresent(LibraryFilter.self, forKey: .libraryFilter) ?? .all
        librarySort = try container.decodeIfPresent(LibrarySort.self, forKey: .librarySort) ?? .recentlyAdded
        beyondIDSession = try container.decodeIfPresent(BeyondIDSession.self, forKey: .beyondIDSession) ?? .signedOut
        searchProviderFilter = try container.decodeIfPresent(MusicProviderFilter.self, forKey: .searchProviderFilter) ?? .all
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

enum MusicProviderFilter: String, CaseIterable, Identifiable, Codable, Hashable {
    case all = "All"
    case youtube = "YouTube"
    case mixtapes = "Mixtapes"
    case audius = "Audius"
    case ccMixter = "ccMixter"
    case internetArchive = "Archive"

    static var allCases: [MusicProviderFilter] {
        [.all, .mixtapes, .audius, .ccMixter, .internetArchive]
    }

    var id: String { rawValue }

    func matches(_ track: MusicTrack) -> Bool {
        switch self {
        case .all:
            true
        case .youtube:
            track.providerName == "YouTube"
        case .mixtapes:
            track.providerName == "Mixtape Archive"
        case .audius:
            track.providerName == "Audius"
        case .ccMixter:
            track.providerName == "ccMixter"
        case .internetArchive:
            track.providerName == "Internet Archive"
        }
    }
}
