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
    let editableURL: URL
    let transferPDFURL: URL

    static let daily = StencilDrop(
        id: "eye-of-horus-anubis-july-27-2026",
        title: "Eye of Horus Anubis",
        collection: "Beyond Ancient Collection",
        displayDate: "Monday, July 27, 2026",
        isoDate: "2026-07-27",
        summary: "Premium Egyptian-inspired realism with clean, transfer-ready line work.",
        placement: "Outer forearm, 6.5-8.5 inches tall",
        rewardBits: 25,
        packageURL: URL(string: "https://beyondimagination.co.technology/beyond-tattoo/api/stencil-download.php?type=package")!,
        previewURL: URL(string: "https://beyondimagination.co.technology/beyond-tattoo/assets/stencils/eye-of-horus-anubis-preview.webp")!,
        editableURL: URL(string: "https://beyondimagination.co.technology/beyond-tattoo/api/stencil-download.php?type=editable")!,
        transferPDFURL: URL(string: "https://beyondimagination.co.technology/beyond-tattoo/api/stencil-download.php?type=pdf")!
    )
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

    func distanceMiles(from location: CLLocation?) -> Double? {
        guard let location else { return nil }
        let studioLocation = CLLocation(latitude: latitude, longitude: longitude)
        return location.distance(from: studioLocation) / 1_609.344
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
