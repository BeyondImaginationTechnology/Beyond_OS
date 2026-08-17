import Foundation
import SwiftUI

struct QuestRegion: Identifiable {
    let id: String
    let title: String
    let subtitle: String
    let icon: String
    let color: Color
    let reward: Int
    let challenges: [QuestChallenge]

    var lessonCount: Int { challenges.count }
}

struct QuestChallenge: Identifiable, Hashable {
    enum Kind: String, Hashable {
        case translate
        case listen
        case culture
    }

    let id: String
    let kind: Kind
    let prompt: String
    let phrase: String
    let pronunciation: String
    let answer: String
    let options: [String]
    let tip: String

    var audioResourceName: String {
        phrase.audioResourceName
    }
}

extension String {
    var audioResourceName: String {
        folding(options: [.caseInsensitive, .diacriticInsensitive], locale: .current)
            .lowercased()
            .replacingOccurrences(of: "[^a-z0-9]+", with: "-", options: .regularExpression)
            .trimmingCharacters(in: CharacterSet(charactersIn: "-"))
    }
}

struct QuestResult: Equatable {
    let correct: Bool
    let message: String
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

    var audioResourceName: String {
        english.audioResourceName
    }

    func text(for language: DictionaryAudioLanguage) -> String {
        switch language {
        case .french: french
        case .spanish: spanish
        case .kreyol: kreyol
        case .patois: patois
        }
    }
}

enum DictionaryAudioLanguage {
    case french
    case spanish
    case kreyol
    case patois

    var locale: String {
        switch self {
        case .french: "fr-FR"
        case .spanish: "es-ES"
        case .kreyol: "ht-HT"
        case .patois: "en-JM"
        }
    }
}

enum QuestTheme: String, CaseIterable, Identifiable, Codable {
    case night
    case riviera
    case market
    case garden

    var id: String { rawValue }

    var title: String {
        switch self {
        case .night: "Night"
        case .riviera: "Riviera"
        case .market: "Market"
        case .garden: "Garden"
        }
    }

    var symbol: String {
        switch self {
        case .night: "moon.stars.fill"
        case .riviera: "sailboat.fill"
        case .market: "basket.fill"
        case .garden: "leaf.fill"
        }
    }

    var accent: Color {
        switch self {
        case .night: Color(red: 0.36, green: 0.55, blue: 1.0)
        case .riviera: Color(red: 0.00, green: 0.75, blue: 0.86)
        case .market: Color(red: 1.00, green: 0.63, blue: 0.20)
        case .garden: Color(red: 0.18, green: 0.78, blue: 0.42)
        }
    }

    var background: LinearGradient {
        LinearGradient(colors: backgroundColors, startPoint: .topLeading, endPoint: .bottomTrailing)
    }

    var card: LinearGradient {
        LinearGradient(colors: [accent.opacity(0.24), Color.white.opacity(0.06)], startPoint: .topLeading, endPoint: .bottomTrailing)
    }

    private var backgroundColors: [Color] {
        switch self {
        case .night:
            [Color(red: 0.03, green: 0.04, blue: 0.10), Color(red: 0.05, green: 0.12, blue: 0.24), Color(red: 0.02, green: 0.02, blue: 0.05)]
        case .riviera:
            [Color(red: 0.00, green: 0.14, blue: 0.20), Color(red: 0.00, green: 0.34, blue: 0.42), Color(red: 0.02, green: 0.08, blue: 0.14)]
        case .market:
            [Color(red: 0.20, green: 0.08, blue: 0.12), Color(red: 0.45, green: 0.20, blue: 0.12), Color(red: 0.10, green: 0.04, blue: 0.08)]
        case .garden:
            [Color(red: 0.02, green: 0.13, blue: 0.08), Color(red: 0.07, green: 0.28, blue: 0.15), Color(red: 0.01, green: 0.06, blue: 0.05)]
        }
    }
}

enum QuestContent {
    static let regions: [QuestRegion] = [
        QuestRegion(
            id: "bonjour-bay",
            title: "Bonjour Bay",
            subtitle: "Greetings and polite first moves",
            icon: "sun.horizon.fill",
            color: .cyan,
            reward: 80,
            challenges: [
                QuestChallenge(id: "hello", kind: .translate, prompt: "Choose the French greeting for hello.", phrase: "Bonjour !", pronunciation: "bohn-zhoor", answer: "Bonjour !", options: ["Bonjour !", "Merci !", "Bonsoir !"], tip: "Use bonjour during the day when entering shops or greeting someone."),
                QuestChallenge(id: "thanks", kind: .listen, prompt: "What does merci mean?", phrase: "Merci.", pronunciation: "mehr-see", answer: "Thank you.", options: ["Please.", "Thank you.", "Goodbye."], tip: "Merci is short, common, and always useful."),
                QuestChallenge(id: "please", kind: .translate, prompt: "Pick the polite word for please.", phrase: "S'il vous plait.", pronunciation: "seel voo pleh", answer: "S'il vous plait.", options: ["Au revoir.", "Pardon.", "S'il vous plait."], tip: "Use s'il vous plait with strangers or adults.")
            ]
        ),
        QuestRegion(
            id: "cafe-crossing",
            title: "Cafe Crossing",
            subtitle: "Order snacks, water, and the bill",
            icon: "cup.and.saucer.fill",
            color: .orange,
            reward: 110,
            challenges: [
                QuestChallenge(id: "water", kind: .translate, prompt: "How do you ask for water?", phrase: "De l'eau, s'il vous plait.", pronunciation: "duh loh, seel voo pleh", answer: "De l'eau, s'il vous plait.", options: ["De l'eau, s'il vous plait.", "Je suis perdu.", "A demain."], tip: "De l'eau sounds like duh loh."),
                QuestChallenge(id: "hungry", kind: .listen, prompt: "J'ai faim means what?", phrase: "J'ai faim.", pronunciation: "zhay fam", answer: "I am hungry.", options: ["I am hungry.", "I am tired.", "I am ready."], tip: "French uses I have hunger: j'ai faim."),
                QuestChallenge(id: "bill", kind: .culture, prompt: "Which phrase asks for the bill?", phrase: "L'addition, s'il vous plait.", pronunciation: "lah-dee-syohn, seel voo pleh", answer: "L'addition, s'il vous plait.", options: ["L'addition, s'il vous plait.", "Ou est la gare ?", "Je m'appelle Alex."], tip: "In cafes, ask for l'addition when you are ready to pay.")
            ]
        ),
        QuestRegion(
            id: "metro-maze",
            title: "Metro Maze",
            subtitle: "Travel words for moving around",
            icon: "tram.fill",
            color: .indigo,
            reward: 140,
            challenges: [
                QuestChallenge(id: "station", kind: .translate, prompt: "Where is the station?", phrase: "Ou est la gare ?", pronunciation: "oo eh lah gahr", answer: "Ou est la gare ?", options: ["Ou est la gare ?", "Je voudrais une pomme.", "Bonne nuit."], tip: "Ou est means where is."),
                QuestChallenge(id: "ticket", kind: .listen, prompt: "Un billet means what?", phrase: "Un billet.", pronunciation: "uhn bee-yay", answer: "A ticket.", options: ["A ticket.", "A table.", "A beach."], tip: "Billet is the travel ticket word here."),
                QuestChallenge(id: "left", kind: .culture, prompt: "Choose the phrase for to the left.", phrase: "A gauche.", pronunciation: "ah gohsh", answer: "A gauche.", options: ["A droite.", "A gauche.", "Tout droit."], tip: "Gauche is left; droite is right.")
            ]
        )
    ]
}
