import Foundation

enum BeyondTrack: String, CaseIterable, Identifiable {
    case math = "Math"
    case coding = "Coding"
    case stats = "Stats"
    case ai = "AI Tutor"

    var id: String { rawValue }

    var symbol: String {
        switch self {
        case .math: "function"
        case .coding: "chevron.left.forwardslash.chevron.right"
        case .stats: "chart.line.uptrend.xyaxis"
        case .ai: "brain.head.profile"
        }
    }

    var accentName: String {
        switch self {
        case .math: "mathBlue"
        case .coding: "mathGreen"
        case .stats: "mathGold"
        case .ai: "mathPurple"
        }
    }
}

struct MathLesson: Identifiable, Hashable {
    let id: Int
    let title: String
    let focus: String
    let teaching: String
    let example: String
    let game: MathGame
}

struct MathGame: Hashable {
    let question: String
    let choices: [String]
    let answer: String
}

struct LearningCourse: Identifiable, Hashable {
    let id: String
    let title: String
    let subtitle: String
    let track: BeyondTrack
    let progress: Double
    let reward: Int
    let lessons: [MathLesson]
}

struct ScannerStep: Identifiable, Hashable {
    let id: Int
    let title: String
    let expression: String
}

struct Achievement: Identifiable, Hashable {
    let id: String
    let title: String
    let detail: String
    let symbol: String
    let reward: Int
}

enum BeyondMathContent {
    static let algebraLessons: [MathLesson] = [
        .init(id: 1, title: "Balance an Equation", focus: "Algebra Basics", teaching: "An equation is balanced when both sides have the same value. Whatever move you make on one side, make the same move on the other side.", example: "2x + 5 = 15 becomes 2x = 10 after subtracting 5 from both sides.", game: .init(question: "Solve for x: 2x + 5 = 15", choices: ["x = 5", "x = 10", "x = 20"], answer: "x = 5")),
        .init(id: 2, title: "Undo Addition First", focus: "Inverse operations", teaching: "Use inverse operations to peel away everything around the variable. Addition is undone with subtraction.", example: "3x - 7 = 14 becomes 3x = 21 after adding 7 to both sides.", game: .init(question: "First move for 3x - 7 = 14?", choices: ["Add 7", "Divide by 7", "Subtract 3"], answer: "Add 7")),
        .init(id: 3, title: "Divide To Isolate x", focus: "Solving equations", teaching: "When a variable is multiplied by a number, divide both sides by that number to isolate it.", example: "3x = 21 becomes x = 7.", game: .init(question: "Solve for x: 3x = 21", choices: ["x = 7", "x = 18", "x = 24"], answer: "x = 7")),
        .init(id: 4, title: "Check Your Answer", focus: "Verification", teaching: "Substitute your answer back into the original equation. If both sides match, the answer works.", example: "For x = 5, 2(5) + 5 = 15.", game: .init(question: "Does x = 4 solve 2x + 5 = 15?", choices: ["Yes", "No", "Only sometimes"], answer: "No"))
    ]

    static let codingLessons: [MathLesson] = [
        .init(id: 11, title: "Variables in Python", focus: "Python Basics", teaching: "A variable stores a value so your program can reuse it and change it later.", example: "score = 2450 stores the learner's Bits total.", game: .init(question: "Which line stores a value?", choices: ["bits = 2450", "print()", "if bits"], answer: "bits = 2450")),
        .init(id: 12, title: "Loops Build Fluency", focus: "Iteration", teaching: "Loops repeat a block of code. They are perfect for practice generators and simulations.", example: "for problem in challenge: solve(problem)", game: .init(question: "What does a loop do?", choices: ["Repeats work", "Deletes code", "Changes colors"], answer: "Repeats work"))
    ]

    static let statsLessons: [MathLesson] = [
        .init(id: 21, title: "Read a Trend", focus: "Statistics", teaching: "A trend shows the direction data is moving over time.", example: "If quiz scores rise each week, the trend is improving.", game: .init(question: "Scores 60, 70, 84 show a trend that is…", choices: ["Rising", "Falling", "Flat"], answer: "Rising"))
    ]

    static let courses: [LearningCourse] = [
        .init(id: "algebra-basics", title: "Algebra Basics", subtitle: "Solve equations with confidence", track: .math, progress: 0.75, reward: 25, lessons: algebraLessons),
        .init(id: "python-basics", title: "Python Basics", subtitle: "Code from beginner to builder", track: .coding, progress: 0.2, reward: 30, lessons: codingLessons),
        .init(id: "stats-foundations", title: "Statistics Foundations", subtitle: "Understand charts, trends, and data", track: .stats, progress: 0.1, reward: 20, lessons: statsLessons),
        .init(id: "ai-study-skills", title: "AI Study Skills", subtitle: "Ask better questions and learn faster", track: .ai, progress: 0.0, reward: 40, lessons: algebraLessons)
    ]

    static let scannerSteps: [ScannerStep] = [
        .init(id: 1, title: "Subtract 5 from both sides", expression: "2x + 5 - 5 = 15 - 5"),
        .init(id: 2, title: "Simplify", expression: "2x = 10"),
        .init(id: 3, title: "Divide both sides by 2", expression: "x = 5")
    ]

    static let achievements: [Achievement] = [
        .init(id: "first-scan", title: "First Scan", detail: "Solve one scanned problem", symbol: "camera.viewfinder", reward: 10),
        .init(id: "algebra-run", title: "Algebra Run", detail: "Finish four equation lessons", symbol: "function", reward: 50),
        .init(id: "builder", title: "Builder", detail: "Complete a coding mini-project", symbol: "hammer.fill", reward: 100),
        .init(id: "explain-back", title: "Explain Back", detail: "Teach a concept in your own words", symbol: "text.bubble.fill", reward: 25)
    ]
}

@MainActor
final class LearningProgress: ObservableObject {
    @Published private(set) var completed: Set<Int>
    @Published private(set) var bits: Int
    @Published private(set) var xp: Int
    private let key = "beyondMath.completed.numberSense"
    private let bitsKey = "beyondMath.bits"
    private let xpKey = "beyondMath.xp"

    init() {
        completed = Set(UserDefaults.standard.array(forKey: key) as? [Int] ?? [])
        bits = UserDefaults.standard.object(forKey: bitsKey) as? Int ?? 2450
        xp = UserDefaults.standard.object(forKey: xpKey) as? Int ?? 3250
    }

    func complete(_ lesson: Int) {
        let inserted = completed.insert(lesson).inserted
        UserDefaults.standard.set(Array(completed), forKey: key)
        if inserted {
            bits += 25
            xp += 250
            UserDefaults.standard.set(bits, forKey: bitsKey)
            UserDefaults.standard.set(xp, forKey: xpKey)
        }
    }
}
