import Foundation
import CoreLocation

struct StencilDrop: Identifiable, Hashable {
    let id: String
    let title: String
    let collection: String
    let displayDate: String
    let isoDate: String
    let summary: String
    let placement: String
    let rewardBits: Int
    let packageURL: URL
    let previewURL: URL
    let stencilURL: URL
    let transferURL: URL?
    let transferPDFURL: URL?
    let referenceURL: URL?
    let placementImageURL: URL?
    let packURL: URL?
    let loreURL: URL?
    let styleCardURL: URL?
}

struct TattooCollection: Identifiable, Hashable {
    let id: String
    let name: String
    let dates: String
    let description: String
    let dropCount: Int
    let stencils: [ScheduledStencil]

    var availableCount: Int {
        stencils.filter(\.isAvailable).count
    }
}

struct ScheduledStencil: Identifiable, Hashable {
    let name: String
    let isoDate: String
    let style: String
    let placement: String
    let difficulty: String
    let hasTransferAsset: Bool
    let hasEditableAsset: Bool
    let previewURL: URL
    let stencilURL: URL
    let transferURL: URL?
    var id: String { name + isoDate }

    var isAvailable: Bool {
        guard let date = Self.releaseDateFormatter.date(from: isoDate) else { return false }
        return date <= Date()
    }

    private static let releaseDateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

struct TattooLibraryManifest: Decodable {
    let version: String
    let seasonTotal: Int
    let assetCount: Int
    let dailyID: String
    let collections: [ManifestCollection]
    let assets: [LibraryAsset]

    enum CodingKeys: String, CodingKey {
        case version, collections, assets
        case seasonTotal = "season_total"
        case assetCount = "asset_count"
        case dailyID = "daily_id"
    }

    static let endpoint = URL(string: "https://beyondimagination.co.technology/beyond-tattoo/api/library.php")!

    static func bundled() -> TattooLibraryManifest {
        guard let url = Bundle.main.url(forResource: "tattoo-library-v1.2", withExtension: "json") else {
            preconditionFailure("Beyond Tattoo 1.2 asset manifest is missing.")
        }
        do { return try JSONDecoder().decode(TattooLibraryManifest.self, from: Data(contentsOf: url)) }
        catch { preconditionFailure("Beyond Tattoo 1.2 asset manifest is invalid: \(error)") }
    }

    var dailyAsset: LibraryAsset {
        assets.first { $0.id == dailyID } ?? assets.max { $0.sequence < $1.sequence }!
    }

    var tattooCollections: [TattooCollection] {
        collections.compactMap { collection in
            let collectionAssets = collection.assets.compactMap { id in assets.first { $0.id == id } }.sorted { $0.sequence < $1.sequence }
            guard !collectionAssets.isEmpty else { return nil }
            let dates = collectionAssets.count == 1
                ? collectionAssets[0].displayDate
                : "\(collectionAssets.first!.releaseDate) – \(collectionAssets.last!.releaseDate)"
            return TattooCollection(
                id: collection.id,
                name: collection.name,
                dates: dates,
                description: collection.description,
                dropCount: collectionAssets.count,
                stencils: collectionAssets.map(\.scheduledStencil)
            )
        }
    }
}

struct ManifestCollection: Decodable {
    let id: String
    let name: String
    let description: String
    let assets: [String]
}

struct LibraryAsset: Decodable, Identifiable, Hashable {
    let id: String
    let title: String
    let sequence: Int
    let collectionID: String
    let collection: String
    let releaseDate: String
    let displayDate: String
    let summary: String
    let style: String
    let placement: String
    let difficulty: String
    let rewardBits: Int
    let isReleased: Bool
    let previewURL: URL
    let stencilURL: URL
    let transferURL: URL?
    let pdfURL: URL?
    let referenceURL: URL?
    let placementImageURL: URL?
    let packURL: URL?
    let loreURL: URL?
    let styleCardURL: URL?

    enum CodingKeys: String, CodingKey {
        case id, title, sequence, collection, summary, style, placement, difficulty
        case collectionID = "collection_id"
        case releaseDate = "release_date"
        case displayDate = "display_date"
        case rewardBits = "reward_bits"
        case isReleased = "is_released"
        case previewURL = "preview_url"
        case stencilURL = "stencil_url"
        case transferURL = "transfer_url"
        case pdfURL = "pdf_url"
        case referenceURL = "reference_url"
        case placementImageURL = "placement_image_url"
        case packURL = "pack_url"
        case loreURL = "lore_url"
        case styleCardURL = "style_card_url"
    }

    var dailyDrop: StencilDrop {
        StencilDrop(
            id: id,
            title: title,
            collection: collection,
            displayDate: displayDate,
            isoDate: releaseDate,
            summary: summary,
            placement: placement,
            rewardBits: rewardBits,
            packageURL: URL(string: "https://beyondimagination.co.technology/beyond-tattoo/api/stencil-download.php?type=package")!,
            previewURL: previewURL,
            stencilURL: stencilURL,
            transferURL: transferURL,
            transferPDFURL: pdfURL,
            referenceURL: referenceURL,
            placementImageURL: placementImageURL,
            packURL: packURL,
            loreURL: loreURL,
            styleCardURL: styleCardURL
        )
    }

    var scheduledStencil: ScheduledStencil {
        ScheduledStencil(
            name: title,
            isoDate: releaseDate,
            style: style,
            placement: placement,
            difficulty: difficulty,
            hasTransferAsset: transferURL != nil,
            hasEditableAsset: false,
            previewURL: previewURL,
            stencilURL: stencilURL,
            transferURL: transferURL
        )
    }
}

struct HealingMilestone: Identifiable, Hashable {
    let day: Int
    let title: String
    let instruction: String
    let isComplete: Bool
    var id: Int { day }
}

struct StudioLead: Identifiable, Hashable {
    let name: String
    let city: String
    let region: String
    let address: String
    let specialties: [String]
    let responseTime: String
    let isVerified: Bool
    let acceptsWalkIns: Bool
    let latitude: Double
    let longitude: Double
    let profileURL: URL
    var id: String { name + city }

    func distanceKilometres(from location: CLLocation?) -> Double? {
        guard let location else { return nil }
        let studioLocation = CLLocation(latitude: latitude, longitude: longitude)
        return location.distance(from: studioLocation) / 1_000
    }
}

struct StudioDirectoryResponse: Decodable {
    let version: String
    let studios: [StudioDirectoryItem]

    static let endpoint = URL(string: "https://beyondimagination.co.technology/beyond-tattoo/api/studios/nearby.php")!
}

struct StudioDirectoryItem: Decodable {
    let slug: String
    let name: String
    let city: String
    let province: String?
    let address: String
    let services: [String]
    let walkIns: Bool
    let isVerified: Bool
    let latitude: Double?
    let longitude: Double?
    let profileURL: URL

    enum CodingKeys: String, CodingKey {
        case slug, name, city, province, address, services, latitude, longitude
        case walkIns = "walk_ins"
        case isVerified = "is_verified"
        case profileURL = "profile_url"
    }

    var studioLead: StudioLead? {
        guard let latitude, let longitude else { return nil }
        return StudioLead(
            name: name,
            city: city,
            region: province ?? "Canada",
            address: address,
            specialties: Array(services.prefix(3)),
            responseTime: walkIns ? "Walk-ins" : "Book online",
            isVerified: isVerified,
            acceptsWalkIns: walkIns,
            latitude: latitude,
            longitude: longitude,
            profileURL: profileURL
        )
    }
}

enum WebDestination: String, Identifiable, CaseIterable {
    case signup = "Join beta"
    case dashboard = "Dashboard"
    case studios = "Studios"
    case stencil = "Stencil of day"

    var id: String { rawValue }

    var url: URL {
        switch self {
        case .signup:
            URL(string: "https://beyondimagination.co.technology/beyond-tattoo/beta-signup.php")!
        case .dashboard:
            URL(string: "https://beyondimagination.co.technology/beyond-tattoo/dashboard.php")!
        case .studios:
            URL(string: "https://beyondimagination.co.technology/beyond-tattoo/studios.php")!
        case .stencil:
            URL(string: "https://beyondimagination.co.technology/beyond-tattoo/stencil-of-day.php")!
        }
    }
}
