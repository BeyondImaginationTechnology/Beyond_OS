import Foundation

struct TodayResponse: Decodable {
    let ok: Bool
    let date: String
    let lesson: FrenchLesson
}

struct FrenchLesson: Codable, Identifiable, Hashable {
    let id: Int
    let date: String?
    let english: String
    let french: String
    let frenchPronunciation: String
    let patois: String
    let kreyol: String
    let spanish: String
    let meaning: String
    let cultureNote: String
    let challenge: String
    let answer: String

    enum CodingKeys: String, CodingKey {
        case id, date, english, french, patois, kreyol, spanish, meaning, challenge, answer
        case frenchPronunciation = "french_pronunciation"
        case cultureNote = "culture_note"
    }

    static let fallback = FrenchLesson(
        id: 1, date: nil, english: "Keep going.", french: "Continue.",
        frenchPronunciation: "Kohn-tee-new", patois: "Keep on gwaan.",
        kreyol: "Kontinye.", spanish: "Sigue adelante.",
        meaning: "A way to encourage someone to continue.",
        cultureNote: "A little encouragement can go a long way.",
        challenge: "How would you say “Keep going.” in French?", answer: "Continue."
    )
}

struct DictionaryWord: Codable, Identifiable, Hashable {
    var id: String { english + french }
    let english: String
    let french: String
    let pronunciation: String
    let spanish: String
    let kreyol: String
    let patois: String
    let type: String
}
