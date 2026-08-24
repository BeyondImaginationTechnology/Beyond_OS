import Foundation

struct SpaceFact: Identifiable, Hashable {
    let id: Int
    let title: String
    let summary: String
    let detail: String
    let sourceName: String
    let sourceURL: URL
    let symbol: String
}

struct Horoscope: Identifiable, Hashable {
    let sign: ZodiacSign
    let reading: String
    let mood: String
    var id: String { sign.rawValue }
}

enum ZodiacSign: String, CaseIterable, Identifiable {
    case aries = "Aries", taurus = "Taurus", gemini = "Gemini", cancer = "Cancer"
    case leo = "Leo", virgo = "Virgo", libra = "Libra", scorpio = "Scorpio"
    case sagittarius = "Sagittarius", capricorn = "Capricorn", aquarius = "Aquarius", pisces = "Pisces"

    var id: String { rawValue }

    var symbol: String {
        switch self {
        case .aries: "♈︎"; case .taurus: "♉︎"; case .gemini: "♊︎"; case .cancer: "♋︎"
        case .leo: "♌︎"; case .virgo: "♍︎"; case .libra: "♎︎"; case .scorpio: "♏︎"
        case .sagittarius: "♐︎"; case .capricorn: "♑︎"; case .aquarius: "♒︎"; case .pisces: "♓︎"
        }
    }

    var dates: String {
        switch self {
        case .aries: "Mar 21–Apr 19"; case .taurus: "Apr 20–May 20"; case .gemini: "May 21–Jun 20"
        case .cancer: "Jun 21–Jul 22"; case .leo: "Jul 23–Aug 22"; case .virgo: "Aug 23–Sep 22"
        case .libra: "Sep 23–Oct 22"; case .scorpio: "Oct 23–Nov 21"; case .sagittarius: "Nov 22–Dec 21"
        case .capricorn: "Dec 22–Jan 19"; case .aquarius: "Jan 20–Feb 18"; case .pisces: "Feb 19–Mar 20"
        }
    }
}

enum SampleContent {
    static let facts = [
        SpaceFact(id: 4, title: "Mars sunsets glow blue", summary: "Fine dust makes the sky near the setting Sun appear blue.", detail: "Martian dust scatters red light across the sky but lets more blue light travel toward an observer near the Sun. The result reverses the familiar colors of many sunsets on Earth.", sourceName: "NASA Science", sourceURL: URL(string: "https://science.nasa.gov/mars/")!, symbol: "sun.horizon.fill"),
        SpaceFact(id: 5, title: "A day on Venus outlasts its year", summary: "Venus turns so slowly that one rotation takes longer than one orbit.", detail: "Venus needs about 243 Earth days to rotate once, yet it completes an orbit around the Sun in about 225 Earth days.", sourceName: "NASA Science", sourceURL: URL(string: "https://science.nasa.gov/venus/facts/")!, symbol: "sparkles"),
        SpaceFact(id: 6, title: "Neutron stars are astonishingly dense", summary: "A teaspoon of neutron-star material would weigh billions of tons on Earth.", detail: "When a massive star collapses, matter can be compressed into a sphere roughly the size of a city while retaining more mass than the Sun.", sourceName: "NASA Science", sourceURL: URL(string: "https://science.nasa.gov/universe/neutron-stars/")!, symbol: "circle.hexagongrid.fill")
    ]

    static let readings: [ZodiacSign: String] = Dictionary(uniqueKeysWithValues: ZodiacSign.allCases.enumerated().map { index, sign in
        let messages = [
            "Choose one clear priority today. A smaller promise kept will carry more energy than a long list left unfinished.",
            "A patient conversation can reveal the detail you were missing. Listen once more before deciding what comes next.",
            "Your curiosity is useful today. Follow the question that feels alive, then give yourself time to shape the answer.",
            "Protect a quiet pocket of time. What first looks like a pause may become the reset that brings your direction back."
        ]
        return (sign, messages[index % messages.count])
    })

    static let moods = ["Focused", "Grounded", "Curious", "Restorative"]
}
