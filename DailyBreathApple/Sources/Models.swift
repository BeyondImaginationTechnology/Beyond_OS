import Foundation

struct Verse: Identifiable, Equatable {
    let id: Int
    let text: String
    let reference: String
    let reflection: String
}

struct Devotional: Identifiable, Equatable {
    let id: Int
    let title: String
    let excerpt: String
    let scripture: String
    let minutes: Int
}

struct PrayerPractice: Identifiable, Equatable {
    let id: Int
    let title: String
    let subtitle: String
    let systemImage: String
}

struct AcademyModule: Identifiable, Equatable {
    let id: Int
    let title: String
    let subtitle: String
    let progress: Double
    let isFree: Bool
}

struct JournalEntry: Identifiable, Equatable {
    let id: UUID
    let createdAt: Date
    let text: String
}

extension Verse {
    static let daily = Verse(
        id: 1,
        text: "Be still, and know that I am God.",
        reference: "Psalm 46:10",
        reflection: "Begin slowly. Make room for quiet, notice your breath, and let the next faithful step be enough for today."
    )
}

extension Devotional {
    static let today = Devotional(
        id: 1,
        title: "Walk in Quiet Confidence",
        excerpt: "Make room for stillness and remember that God is present before your next step.",
        scripture: "Psalm 46:10",
        minutes: 5
    )
}
