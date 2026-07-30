import Foundation

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

enum NumberSenseContent {
    static let lessons: [MathLesson] = [
        .init(id: 1, title: "Build Numbers with Place Value", focus: "Place value", teaching: "A digit’s position tells how much it is worth. In 347, the 3 means three hundreds, the 4 means four tens, and the 7 means seven ones.", example: "582 = 500 + 80 + 2", game: .init(question: "Which expanded form builds 472?", choices: ["400 + 70 + 2", "40 + 70 + 2", "400 + 7 + 2"], answer: "400 + 70 + 2")),
        .init(id: 2, title: "Read and Write Whole Numbers", focus: "Number names", teaching: "Read the hundreds first, then the tens and ones. A zero holds an empty place.", example: "406 is four hundred six.", game: .init(question: "Which name matches 406?", choices: ["four hundred sixty", "four hundred six", "forty-six"], answer: "four hundred six")),
        .init(id: 3, title: "Compare Numbers", focus: "Compare", teaching: "Compare digits from left to right. The first place that differs tells which number is greater.", example: "563 is greater than 536.", game: .init(question: "563 __ 536", choices: ["<", ">", "="], answer: ">")),
        .init(id: 4, title: "Order Numbers on a Number Line", focus: "Number line", teaching: "Numbers increase as you move right and decrease as you move left.", example: "70 is the seventh ten after zero.", game: .init(question: "Which number lies between 60 and 80?", choices: ["50", "70", "90"], answer: "70")),
        .init(id: 5, title: "Round to the Nearest Ten", focus: "Rounding", teaching: "Zero through four rounds down. Five through nine rounds up.", example: "67 rounds to 70.", game: .init(question: "Round 67 to the nearest ten.", choices: ["60", "65", "70"], answer: "70")),
        .init(id: 6, title: "Find Number Patterns", focus: "Patterns", teaching: "A number pattern follows a repeatable rule.", example: "4, 8, 12, 16 adds four each time.", game: .init(question: "4, 8, 12, 16, __", choices: ["18", "20", "24"], answer: "20")),
        .init(id: 7, title: "Break Numbers Apart", focus: "Expanded form", teaching: "Expanded form shows the value of every nonzero digit.", example: "729 = 700 + 20 + 9.", game: .init(question: "Expand 729.", choices: ["700 + 20 + 9", "70 + 20 + 9", "700 + 29 + 9"], answer: "700 + 20 + 9")),
        .init(id: 8, title: "Estimate Quantities", focus: "Estimation", teaching: "An estimate is a sensible answer close to the exact amount.", example: "Five rows of about ten is about fifty.", game: .init(question: "Five groups of about 10 is closest to…", choices: ["15", "50", "500"], answer: "50")),
        .init(id: 9, title: "Solve Number Sense Stories", focus: "Reasoning", teaching: "Decide whether the story needs an exact answer or an estimate.", example: "198 guests is about 200 chairs.", game: .init(question: "Best estimate for 198 guests?", choices: ["20", "200", "2,000"], answer: "200")),
        .init(id: 10, title: "Number Sense Challenge Lab", focus: "Mastery", teaching: "Combine place value, comparison, rounding, patterns, and estimation.", example: "398 + 403 is about 400 + 400.", game: .init(question: "Estimate 398 + 403.", choices: ["400", "800", "1,200"], answer: "800"))
    ]
}

@MainActor
final class LearningProgress: ObservableObject {
    @Published private(set) var completed: Set<Int>
    private let key = "beyondMath.completed.numberSense"

    init() {
        completed = Set(UserDefaults.standard.array(forKey: key) as? [Int] ?? [])
    }

    func complete(_ lesson: Int) {
        completed.insert(lesson)
        UserDefaults.standard.set(Array(completed), forKey: key)
    }
}
