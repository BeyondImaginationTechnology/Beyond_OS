import Foundation

struct FamilyMember: Identifiable, Hashable {
    let id: String
    let name: String
    let relationship: String
    let ageSummary: String
    let accent: HealthAccent

    static let seed: [FamilyMember] = [
        FamilyMember(id: "mia", name: "Mia", relationship: "You", ageSummary: "Adult", accent: .teal),
        FamilyMember(id: "zak", name: "Zak", relationship: "Child", ageSummary: "7 years", accent: .gold),
        FamilyMember(id: "nana", name: "Nana", relationship: "Parent", ageSummary: "64 years", accent: .rose)
    ]
}

enum HealthAccent: String, Hashable {
    case teal
    case gold
    case rose
    case sky
}

enum HealthCategory: String, CaseIterable, Identifiable, Hashable {
    case body = "Body"
    case food = "Food"
    case sleep = "Sleep"
    case medication = "Meds"
    case smoke = "Smoke"
    case workout = "Workout"
    case hygiene = "Care"

    var id: String { rawValue }

    var systemImage: String {
        switch self {
        case .body: "heart.text.square.fill"
        case .food: "camera.macro"
        case .sleep: "moon.zzz.fill"
        case .medication: "pills.fill"
        case .smoke: "smoke.fill"
        case .workout: "figure.strengthtraining.traditional"
        case .hygiene: "shower.fill"
        }
    }
}

struct HealthLogEntry: Identifiable, Hashable {
    let id: UUID
    let memberID: String
    let date: Date
    let category: HealthCategory
    let title: String
    let detail: String
    let attachmentLabel: String?

    init(id: UUID = UUID(), memberID: String, date: Date, category: HealthCategory, title: String, detail: String, attachmentLabel: String? = nil) {
        self.id = id
        self.memberID = memberID
        self.date = date
        self.category = category
        self.title = title
        self.detail = detail
        self.attachmentLabel = attachmentLabel
    }
}

struct RoutineItem: Identifiable, Hashable {
    let id: String
    let memberID: String
    let title: String
    let category: HealthCategory
    let dueTime: String
    var isComplete: Bool
}

struct WorkoutRecommendation: Identifiable, Hashable {
    let id: String
    let memberID: String
    let title: String
    let durationMinutes: Int
    let intensity: String
    let reason: String
    let moves: [String]
}
