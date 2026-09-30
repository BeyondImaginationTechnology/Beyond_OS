import Foundation

enum Mood: String, CaseIterable, Codable, Identifiable {
    case grounded = "Grounded"
    case tired = "Tired"
    case stretched = "Stretched"
    case hopeful = "Hopeful"
    case heavy = "Heavy"

    var id: String { rawValue }

    var symbol: String {
        switch self {
        case .grounded: "sun.max"
        case .tired: "moon"
        case .stretched: "wind"
        case .hopeful: "sparkles"
        case .heavy: "heart"
        }
    }

    var suggestion: String {
        switch self {
        case .grounded: "Keep the rhythm. Notice one good thing."
        case .tired: "Make the next thing smaller. Try a brief movement reset."
        case .stretched: "Pause before you push. Take one quiet breath."
        case .hopeful: "Build on what feels possible. Capture one thought."
        case .heavy: "Be gentle with the next hour. One step is enough."
        }
    }

    var suggestedPractice: PracticeKind {
        switch self {
        case .grounded, .hopeful: .nameTheGood
        case .tired: .bodyReset
        case .stretched, .heavy: .boxBreathing
        }
    }
}

struct CheckIn: Codable, Identifiable {
    var id: UUID = UUID()
    let date: Date
    let mood: Mood
    let energy: Int
    let stress: Int
    let sleep: Int
}

struct JournalNote: Codable, Identifiable {
    var id: UUID = UUID()
    let date: Date
    let text: String
}

enum PracticeKind: String, CaseIterable, Identifiable {
    case boxBreathing = "Box breathing"
    case bodyReset = "Reset your body"
    case nameTheGood = "Name the good"

    var id: String { rawValue }
    var seconds: Int {
        switch self {
        case .boxBreathing, .nameTheGood: 60
        case .bodyReset: 120
        }
    }
    var symbol: String {
        switch self {
        case .boxBreathing: "circle.dotted"
        case .bodyReset: "figure.walk"
        case .nameTheGood: "square.and.pencil"
        }
    }
    var detail: String {
        switch self {
        case .boxBreathing: "Breathe in, hold, breathe out, and hold for four counts each."
        case .bodyReset: "Look away from the screen, roll your shoulders, and walk if you can."
        case .nameTheGood: "Write one sentence about something steady, kind, or possible today."
        }
    }
}
