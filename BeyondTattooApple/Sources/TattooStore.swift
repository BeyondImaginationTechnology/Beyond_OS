import Foundation
import CoreLocation

@MainActor
final class TattooStore: ObservableObject {
    @Published var activeRole: UserRole = .collector
    @Published var savedStencilIDs: Set<String> = [StencilDrop.daily.id]
    @Published var healingPhotoCount = 2
    @Published var hasBeyondID = false
    @Published var userLocation: CLLocation?

    let dailyDrop = StencilDrop.daily
    let collections = TattooCollection.seed
    let studios = StudioLead.seed

    var totalStencilCount: Int {
        collections.reduce(0) { $0 + $1.stencils.count }
    }

    var availableStencilCount: Int {
        collections.reduce(0) { $0 + $1.availableCount }
    }

    var nearestStudios: [StudioLead] {
        studios
            .sorted { left, right in
                switch (left.distanceMiles(from: userLocation), right.distanceMiles(from: userLocation)) {
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

extension TattooCollection {
    static let seed: [TattooCollection] = [
        TattooCollection(
            id: "divine-realism",
            name: "Divine Realism",
            dates: "Jul 17-26, 2026",
            description: "Biblical portraiture, angels, and sacred iconography composed for black-and-grey realism.",
            dropCount: 10,
            stencils: [
                .stencil("Biblical Realism", "2026-07-17", "Black-and-grey realism", "Upper arm portrait", "Advanced", true),
                .stencil("Archangel Michael", "2026-07-18", "Sacred realism", "Chest or shoulder", "Advanced", false),
                .stencil("Sacred Heart", "2026-07-19", "Fine-line sacred", "Sternum or forearm", "Intermediate", false),
                .stencil("Praying Hands & Rosary", "2026-07-20", "Black-and-grey", "Outer forearm", "Intermediate", false),
                .stencil("Guardian Angel", "2026-07-21", "Realism", "Upper arm", "Advanced", false),
                .stencil("Dove & Radiant Cross", "2026-07-22", "Linework", "Inner forearm", "Beginner", false),
                .stencil("Cherub & Clouds", "2026-07-23", "Soft realism", "Shoulder cap", "Intermediate", false),
                .stencil("Gates of Heaven", "2026-07-24", "Architectural realism", "Back panel", "Advanced", false),
                .stencil("Crown & Cross", "2026-07-25", "Ornamental", "Calf or forearm", "Intermediate", false),
                .stencil("Angel of Light", "2026-07-26", "High-contrast realism", "Rib or thigh", "Advanced", false)
            ]
        ),
        TattooCollection(
            id: "beyond-ancient",
            name: "Beyond Ancient",
            dates: "Jul 27-Aug 7, 2026",
            description: "Egyptian gods, royal portraits, and sacred symbols framed with ornamental hieroglyphic detail.",
            dropCount: 12,
            stencils: [
                .stencil("Anubis", "2026-07-27", "Egyptian realism", "Outer forearm", "Advanced", true),
                .stencil("Eye of Horus", "2026-07-28", "Symbolic blackwork", "Inner forearm", "Beginner", true),
                .stencil("Pharaoh Portrait", "2026-07-29", "Portrait realism", "Upper arm", "Advanced", false),
                .stencil("Sacred Scarab", "2026-07-30", "Ornamental blackwork", "Hand or sternum", "Intermediate", false),
                .stencil("Sekhmet", "2026-07-31", "Mythic realism", "Thigh panel", "Advanced", false),
                .stencil("Isis", "2026-08-01", "Fine-line goddess", "Back shoulder", "Intermediate", false),
                .stencil("Pyramid Gateway", "2026-08-02", "Geometric realism", "Back or calf", "Intermediate", false),
                .stencil("Osiris", "2026-08-03", "Sacred portrait", "Upper arm", "Advanced", false),
                .stencil("Bastet", "2026-08-04", "Blackwork deity", "Forearm", "Intermediate", false),
                .stencil("Egyptian Sacred Symbols", "2026-08-05", "Flash set", "Wrist, hand, neck", "Beginner", false),
                .stencil("Hieroglyphic Guardian", "2026-08-06", "Ornamental", "Calf band", "Intermediate", false),
                .stencil("Ornamental Egyptian Frame", "2026-08-07", "Framework", "Chest or back", "Advanced", false)
            ]
        ),
        TattooCollection(
            id: "japanese-legends",
            name: "Japanese Legends",
            dates: "Aug 8-22, 2026",
            description: "Masks, warriors, animals, and mythological figures shaped for flowing Japanese compositions.",
            dropCount: 15,
            stencils: [
                .stencil("Hannya Mask", "2026-08-08", "Japanese traditional", "Shoulder cap", "Intermediate", false),
                .stencil("Oni Warrior", "2026-08-09", "Neo-traditional", "Thigh panel", "Advanced", false),
                .stencil("Japanese Dragon", "2026-08-10", "Large-scale flow", "Sleeve starter", "Advanced", false),
                .stencil("Koi & Lotus", "2026-08-11", "Japanese color", "Forearm wrap", "Intermediate", false),
                .stencil("Samurai Portrait", "2026-08-12", "Portrait realism", "Upper arm", "Advanced", false),
                .stencil("Geisha & Fan", "2026-08-13", "Illustrative", "Outer forearm", "Advanced", false),
                .stencil("Japanese Tiger", "2026-08-14", "Traditional animal", "Chest or thigh", "Advanced", false),
                .stencil("Snake & Chrysanthemum", "2026-08-15", "Botanical flow", "Forearm wrap", "Intermediate", false),
                .stencil("Peony Arrangement", "2026-08-16", "Floral", "Shoulder or calf", "Beginner", false),
                .stencil("Great Wave", "2026-08-17", "Water study", "Forearm", "Intermediate", false),
                .stencil("Temple Guardian", "2026-08-18", "Mythic realism", "Back panel", "Advanced", false),
                .stencil("Kitsune Mask", "2026-08-19", "Mask study", "Upper arm", "Intermediate", false),
                .stencil("Phoenix", "2026-08-20", "Large-scale flow", "Rib or back", "Advanced", false),
                .stencil("Raijin", "2026-08-21", "Mythological", "Thigh panel", "Advanced", false),
                .stencil("Mythical Guardian", "2026-08-22", "Japanese realism", "Sleeve cap", "Advanced", false)
            ]
        ),
        TattooCollection(
            id: "dark-realism",
            name: "Dark Realism",
            dates: "Aug 23-Sep 9, 2026",
            description: "Gothic portraiture, skulls, ravens, and dramatic high-contrast compositions.",
            dropCount: 18,
            stencils: [
                .stencil("Gothic Skull", "2026-08-23", "Dark realism", "Hand or forearm", "Intermediate", false),
                .stencil("Raven & Moon", "2026-08-24", "Blackwork realism", "Upper arm", "Intermediate", false),
                .stencil("Hooded Reaper", "2026-08-25", "High-contrast realism", "Calf or forearm", "Advanced", false),
                .stencil("Broken Angel Statue", "2026-08-26", "Stone realism", "Back shoulder", "Advanced", false),
                .stencil("Demon Portrait", "2026-08-27", "Horror realism", "Thigh panel", "Advanced", false),
                .stencil("Gothic Cathedral", "2026-08-28", "Architectural", "Forearm panel", "Advanced", false),
                .stencil("Skull in Smoke", "2026-08-29", "Soft black-and-grey", "Upper arm", "Intermediate", false),
                .stencil("Chained Soul", "2026-08-30", "Dark illustrative", "Rib panel", "Advanced", false),
                .stencil("Broken Clock", "2026-08-31", "Surreal realism", "Forearm", "Intermediate", false),
                .stencil("Plague Doctor", "2026-09-01", "Gothic portrait", "Calf", "Advanced", false),
                .stencil("Weeping Stone Face", "2026-09-02", "Statue realism", "Upper arm", "Advanced", false),
                .stencil("Grim Knight", "2026-09-03", "Dark fantasy", "Thigh", "Advanced", false),
                .stencil("Raven Skull", "2026-09-04", "Blackwork", "Outer forearm", "Intermediate", false),
                .stencil("Haunted Gate", "2026-09-05", "Gothic frame", "Back panel", "Advanced", false),
                .stencil("Death & Hourglass", "2026-09-06", "Symbolic realism", "Forearm", "Intermediate", false),
                .stencil("Dark Seraph", "2026-09-07", "Sacred horror", "Chest", "Advanced", false),
                .stencil("Possessed Statue", "2026-09-08", "Horror realism", "Upper arm", "Advanced", false),
                .stencil("Final Judgment", "2026-09-09", "Large-scale realism", "Back panel", "Advanced", false)
            ]
        )
    ]
}

extension StudioLead {
    static let seed: [StudioLead] = [
        .studio("StonerInkk", "Ottawa", "Ontario", "234 Dalhousie Street", ["Realism", "Fine line", "Piercing"], "Same day", true, true, 45.4294, -75.6904, "stonerinkk-ottawa"),
        .studio("North Star Ink", "Toronto", "Ontario", "Queen West", ["Realism", "Fine line", "Coverups"], "2 hr", true, false, 43.6487, -79.3980, "north-star-ink"),
        .studio("Crown & Needle", "Los Angeles", "California", "Arts District", ["Blackwork", "Sacred", "Sleeves"], "Same day", true, true, 34.0407, -118.2354, "crown-needle"),
        .studio("Harbor Light Studio", "Vancouver", "British Columbia", "Gastown", ["Japanese", "Color", "Large scale"], "1 day", false, false, 49.2829, -123.1114, "harbor-light-studio"),
        .studio("East River Tattoo Co.", "New York", "New York", "Brooklyn", ["Traditional", "Blackwork", "Flash"], "4 hr", true, true, 40.7162, -73.9557, "east-river-tattoo"),
        .studio("Good Form Tattoo", "Chicago", "Illinois", "West Loop", ["Fine line", "Ornamental", "Florals"], "6 hr", true, false, 41.8836, -87.6485, "good-form-tattoo"),
        .studio("Rose City Electric", "Portland", "Oregon", "Burnside", ["Botanical", "Blackwork", "Custom"], "1 day", true, true, 45.5231, -122.6765, "rose-city-electric"),
        .studio("Mission Needle House", "San Francisco", "California", "Mission District", ["Japanese", "Geometric", "Color"], "3 hr", false, false, 37.7599, -122.4148, "mission-needle-house"),
        .studio("Lone Star Lines", "Austin", "Texas", "East Austin", ["Neo-traditional", "Script", "Blackwork"], "Same day", true, true, 30.2638, -97.7278, "lone-star-lines"),
        .studio("Canal Street Tattoo", "New Orleans", "Louisiana", "French Quarter", ["Ornamental", "Sacred", "Coverups"], "1 day", false, true, 29.9584, -90.0644, "canal-street-tattoo"),
        .studio("Mile High Mark", "Denver", "Colorado", "RiNo", ["Realism", "Nature", "Black-and-grey"], "5 hr", true, false, 39.7589, -104.9864, "mile-high-mark"),
        .studio("Peachtree Ink Lab", "Atlanta", "Georgia", "Old Fourth Ward", ["Portraits", "Fine line", "Lettering"], "2 hr", true, false, 33.7550, -84.3733, "peachtree-ink-lab"),
        .studio("South Beach Stencil", "Miami", "Florida", "Wynwood", ["Color", "Micro realism", "Flash"], "Same day", false, true, 25.8011, -80.1996, "south-beach-stencil"),
        .studio("Capitol Blackwork", "Washington", "District of Columbia", "Shaw", ["Blackwork", "Sacred", "Geometric"], "4 hr", true, false, 38.9121, -77.0219, "capitol-blackwork"),
        .studio("Emerald City Tattoo", "Seattle", "Washington", "Capitol Hill", ["Japanese", "Botanical", "Sleeves"], "1 day", true, false, 47.6230, -122.3207, "emerald-city-tattoo")
    ]
}

private extension ScheduledStencil {
    static func stencil(
        _ name: String,
        _ isoDate: String,
        _ style: String,
        _ placement: String,
        _ difficulty: String,
        _ hasTransferAsset: Bool,
        hasEditableAsset: Bool = false
    ) -> ScheduledStencil {
        ScheduledStencil(
            name: name,
            isoDate: isoDate,
            style: style,
            placement: placement,
            difficulty: difficulty,
            hasTransferAsset: hasTransferAsset,
            hasEditableAsset: hasEditableAsset || hasTransferAsset
        )
    }
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
