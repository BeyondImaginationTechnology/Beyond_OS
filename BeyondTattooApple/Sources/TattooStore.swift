import Foundation
import CoreLocation
import Combine

@MainActor
final class TattooStore: ObservableObject {
    @Published var activeRole: UserRole = .collector
    @Published var savedStencilIDs: Set<String>
    @Published var healingPhotoCount = 2
    @Published var hasBeyondID = false
    @Published var userLocation: CLLocation?
    @Published private(set) var dailyDrop: StencilDrop
    @Published private(set) var collections: [TattooCollection]
    @Published private(set) var libraryVersion: String

    @Published private(set) var studios = StudioLead.seed

    init() {
        let manifest = TattooLibraryManifest.bundled()
        let daily = manifest.dailyAsset.dailyDrop
        dailyDrop = daily
        collections = manifest.tattooCollections
        libraryVersion = manifest.version
        savedStencilIDs = [daily.id]
        Task {
            await refreshLibrary()
            await refreshStudios()
        }
    }

    func refreshLibrary() async {
        do {
            let (data, response) = try await URLSession.shared.data(from: TattooLibraryManifest.endpoint)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200 else { return }
            let manifest = try JSONDecoder().decode(TattooLibraryManifest.self, from: data)
            guard manifest.version == "1.2", !manifest.assets.isEmpty else { return }
            let updatedCollections = manifest.tattooCollections
            guard !updatedCollections.isEmpty else { return }
            dailyDrop = manifest.dailyAsset.dailyDrop
            collections = updatedCollections
            libraryVersion = manifest.version
        } catch {
            // The bundled 1.2 manifest remains the offline source of truth.
        }
    }

    func refreshStudios() async {
        do {
            let (data, response) = try await URLSession.shared.data(from: StudioDirectoryResponse.endpoint)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200 else { return }
            let directory = try JSONDecoder().decode(StudioDirectoryResponse.self, from: data)
            let updatedStudios = directory.studios.compactMap(\.studioLead)
            guard directory.version == "1.2", !updatedStudios.isEmpty else { return }
            studios = updatedStudios
        } catch {
            // The verified Canadian seed remains available offline.
        }
    }

    var totalStencilCount: Int {
        collections.reduce(0) { $0 + $1.stencils.count }
    }

    var availableStencilCount: Int {
        collections.reduce(0) { $0 + $1.availableCount }
    }

    var nearestStudios: [StudioLead] {
        studios
            .sorted { left, right in
                switch (left.distanceKilometres(from: userLocation), right.distanceKilometres(from: userLocation)) {
                case let (leftDistance?, rightDistance?):
                    leftDistance < rightDistance
                case (_?, nil):
                    true
                case (nil, _?):
                    false
                case (nil, nil):
                    left.name < right.name
                }
            }
            .prefix(10)
            .map { $0 }
    }

    var healingMilestones: [HealingMilestone] {
        [
            HealingMilestone(day: 0, title: "Fresh wrap", instruction: "Log first photo, artist notes, and placement.", isComplete: true),
            HealingMilestone(day: 2, title: "Gentle wash", instruction: "Check redness, dryness, and clean product routine.", isComplete: true),
            HealingMilestone(day: 7, title: "Peel window", instruction: "Capture texture changes and avoid picking.", isComplete: false),
            HealingMilestone(day: 14, title: "Settled review", instruction: "Compare clarity and plan a touch-up if needed.", isComplete: false)
        ]
    }

    func toggleSaved(_ stencil: StencilDrop) {
        if savedStencilIDs.contains(stencil.id) {
            savedStencilIDs.remove(stencil.id)
        } else {
            savedStencilIDs.insert(stencil.id)
        }
    }
}

enum UserRole: String, CaseIterable, Identifiable {
    case collector = "Collector"
    case artist = "Artist"
    case studio = "Studio"

    var id: String { rawValue }

    var symbolName: String {
        switch self {
        case .collector: "person.crop.circle.fill"
        case .artist: "paintbrush.pointed.fill"
        case .studio: "building.2.crop.circle.fill"
        }
    }
}

extension StudioLead {
    static let seed: [StudioLead] = [
        .studio("StonerInkk", "Ottawa", "Ontario", "234 Dalhousie Street", ["Realism", "Fine line", "Piercing"], "Call ahead", true, true, 45.4294, -75.6904, "stonerinkk-ottawa"),
        .studio("Obscura Tattoo", "Ottawa", "Ontario", "320 Catherine Street, Suite 22", ["Custom", "Blackwork", "Fine line"], "Book online", true, false, 45.4097, -75.6949, "obscura-tattoo-ottawa"),
        .studio("The 18th Ink", "Ottawa", "Ontario", "250 Greenbank Road, Unit 14", ["Colour realism", "Japanese", "Anime"], "Book online", true, false, 45.3340, -75.7790, "the-18th-ink-ottawa"),
        .studio("Sting Studio", "Ottawa", "Ontario", "1076 Ogilvie Road, Unit 2", ["Blackwork", "Neo-traditional", "Piercing"], "Book online", true, false, 45.4340, -75.6220, "sting-studio-ottawa"),
        .studio("Sunken Star Tattoo", "Ottawa", "Ontario", "288 Dalhousie Street, Unit 100", ["Custom", "Black-and-grey", "Colour"], "Call ahead", true, true, 45.4291, -75.6914, "sunken-star-tattoo-ottawa"),
        .studio("Two 0 Six Tattoo", "Ottawa", "Ontario", "1066 Somerset Street West, Unit 206", ["Fine line", "Realism", "Japanese"], "Appointment", true, false, 45.4045, -75.7240, "two-0-six-tattoo-ottawa"),
        .studio("Relic Piercing and Tattoo", "Ottawa", "Ontario", "379 Danforth Avenue", ["Neo-traditional", "Illustrative", "Piercing"], "Appointment", true, false, 45.3910, -75.7530, "relic-ottawa"),
        .studio("Sage Tattoo and Art Gallery", "Ottawa", "Ontario", "2286 Carling Avenue", ["Realism", "Colour", "Piercing"], "Book online", true, false, 45.3610, -75.7830, "sage-tattoo-ottawa"),
        .studio("Barnstormer Studio", "Ottawa", "Ontario", "591 Bank Street", ["Traditional", "Japanese", "Black-and-grey"], "Call ahead", true, true, 45.4080, -75.6920, "barnstormer-studio-ottawa"),
        .studio("Adrenaline Toronto — Queen West", "Toronto", "Ontario", "239 Queen Street West", ["Custom", "Walk-ins", "Piercing"], "Walk-ins", true, true, 43.6508, -79.3898, "adrenaline-toronto-queen-west"),
        .studio("Adrenaline Montréal", "Montréal", "Quebec", "1541 Sherbrooke Street West", ["Custom", "Walk-ins", "Piercing"], "Walk-ins", true, true, 45.4960, -73.5790, "adrenaline-montreal"),
        .studio("Prana Tattoo and Piercing", "Montréal", "Quebec", "17 Sainte-Catherine Street East", ["Fine line", "Realism", "Traditional"], "Walk-ins", true, true, 45.5116, -73.5614, "prana-tattoo-montreal"),
        .studio("Adrenaline Vancouver — Granville", "Vancouver", "British Columbia", "1014 Granville Street", ["Custom", "Walk-ins", "Piercing"], "Walk-ins", true, true, 49.2785, -123.1225, "adrenaline-vancouver-granville"),
        .studio("Ambassador Tattoo", "Calgary", "Alberta", "908 17 Avenue SW, Suite 312", ["Custom", "Traditional", "Neo-traditional"], "Book online", true, false, 51.0373, -114.0820, "ambassador-tattoo-calgary"),
        .studio("Calgary Tattoo Company", "Calgary", "Alberta", "7144 Fisher Street SE", ["Custom", "Black-and-grey", "Colour"], "Call ahead", true, false, 50.9900, -114.0700, "calgary-tattoo-company"),
        .studio("Got Ink? Tattoo Studio", "Edmonton", "Alberta", "14716 Stony Plain Road", ["Black-and-grey", "Custom", "Realism"], "Walk-ins", true, true, 53.5410, -113.5740, "got-ink-edmonton"),
        .studio("Tattoos for the Individual", "Winnipeg", "Manitoba", "1767 Portage Avenue, Unit 1", ["Custom", "Consultation", "Appointment"], "Call ahead", true, true, 49.8810, -97.2120, "winnipeg-tattoo"),
        .studio("Rites of Passage Tattoo", "Saskatoon", "Saskatchewan", "634 10th Street East", ["Custom", "Walk-ins", "Piercing"], "Walk-ins", true, true, 52.1200, -106.6500, "rites-of-passage-saskatoon"),
        .studio("Sin on Skin Tattoo Studio", "Halifax", "Nova Scotia", "5239 Blowers Street, 3rd Floor", ["Custom", "Black-and-grey", "Cover-ups"], "Call ahead", true, true, 44.6446, -63.5750, "sin-on-skin-halifax")
    ]
}

private extension StudioLead {
    static func studio(
        _ name: String,
        _ city: String,
        _ region: String,
        _ address: String,
        _ specialties: [String],
        _ responseTime: String,
        _ isVerified: Bool,
        _ acceptsWalkIns: Bool,
        _ latitude: Double,
        _ longitude: Double,
        _ slug: String
    ) -> StudioLead {
        StudioLead(
            name: name,
            city: city,
            region: region,
            address: address,
            specialties: specialties,
            responseTime: responseTime,
            isVerified: isVerified,
            acceptsWalkIns: acceptsWalkIns,
            latitude: latitude,
            longitude: longitude,
            profileURL: URL(string: "https://beyondimagination.co.technology/beyond-tattoo/studio-profile.php?slug=\(slug)")!
        )
    }
}
