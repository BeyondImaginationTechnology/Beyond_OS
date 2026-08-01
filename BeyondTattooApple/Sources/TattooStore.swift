import Foundation

@MainActor
final class TattooStore: ObservableObject {
    @Published var activeRole: UserRole = .collector
    @Published var savedStencilIDs: Set<String> = [StencilDrop.daily.id]
    @Published var healingPhotoCount = 2
    @Published var hasBeyondID = false

    let dailyDrop = StencilDrop.daily
    let collections = TattooCollection.seed
    let studios = StudioLead.seed

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
                ScheduledStencil(name: "Biblical Realism", isoDate: "2026-07-17"),
                ScheduledStencil(name: "Archangel Michael", isoDate: "2026-07-18"),
                ScheduledStencil(name: "Sacred Heart", isoDate: "2026-07-19"),
                ScheduledStencil(name: "Guardian Angel", isoDate: "2026-07-21")
            ]
        ),
        TattooCollection(
            id: "beyond-ancient",
            name: "Beyond Ancient",
            dates: "Jul 27-Aug 7, 2026",
            description: "Egyptian gods, royal portraits, and sacred symbols framed with ornamental hieroglyphic detail.",
            dropCount: 12,
            stencils: [
                ScheduledStencil(name: "Anubis", isoDate: "2026-07-27"),
                ScheduledStencil(name: "Eye of Horus", isoDate: "2026-07-28"),
                ScheduledStencil(name: "Pharaoh Portrait", isoDate: "2026-07-29"),
                ScheduledStencil(name: "Sacred Scarab", isoDate: "2026-07-30")
            ]
        ),
        TattooCollection(
            id: "japanese-legends",
            name: "Japanese Legends",
            dates: "Aug 8-22, 2026",
            description: "Masks, warriors, animals, and mythological figures shaped for flowing Japanese compositions.",
            dropCount: 15,
            stencils: [
                ScheduledStencil(name: "Hannya Mask", isoDate: "2026-08-08"),
                ScheduledStencil(name: "Oni Warrior", isoDate: "2026-08-09"),
                ScheduledStencil(name: "Japanese Dragon", isoDate: "2026-08-10"),
                ScheduledStencil(name: "Koi & Lotus", isoDate: "2026-08-11")
            ]
        ),
        TattooCollection(
            id: "dark-realism",
            name: "Dark Realism",
            dates: "Aug 23-Sep 9, 2026",
            description: "Gothic portraiture, skulls, ravens, and dramatic high-contrast compositions.",
            dropCount: 18,
            stencils: [
                ScheduledStencil(name: "Gothic Skull", isoDate: "2026-08-23"),
                ScheduledStencil(name: "Raven & Moon", isoDate: "2026-08-24"),
                ScheduledStencil(name: "Hooded Reaper", isoDate: "2026-08-25"),
                ScheduledStencil(name: "Broken Angel Statue", isoDate: "2026-08-26")
            ]
        )
    ]
}

extension StudioLead {
    static let seed: [StudioLead] = [
        StudioLead(name: "North Star Ink", city: "Toronto", specialties: ["Realism", "Fine line", "Coverups"], responseTime: "2 hr", isVerified: true),
        StudioLead(name: "Crown & Needle", city: "Los Angeles", specialties: ["Blackwork", "Sacred", "Sleeves"], responseTime: "Same day", isVerified: true),
        StudioLead(name: "Harbor Light Studio", city: "Vancouver", specialties: ["Japanese", "Color", "Large scale"], responseTime: "1 day", isVerified: false)
    ]
}
