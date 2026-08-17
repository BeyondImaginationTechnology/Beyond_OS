import Foundation

enum AppTheme: String, CaseIterable, Identifiable, Codable {
    case classic
    case ocean
    case sunrise
    case garden

    var id: String { rawValue }

    var title: String {
        switch self {
        case .classic: "Classic"
        case .ocean: "Ocean"
        case .sunrise: "Sunrise"
        case .garden: "Garden"
        }
    }
}

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
    let audioUrl: String?

    var audioResourceName: String {
        english
            .folding(options: [.caseInsensitive, .diacriticInsensitive], locale: .current)
            .lowercased()
            .replacingOccurrences(of: "[^a-z0-9]+", with: "-", options: .regularExpression)
            .trimmingCharacters(in: CharacterSet(charactersIn: "-"))
    }

    enum CodingKeys: String, CodingKey {
        case id, date, english, french, patois, kreyol, spanish, meaning, challenge, answer
        case frenchPronunciation = "french_pronunciation"
        case cultureNote = "culture_note"
        case audioUrl = "audio_url"
    }

    static let fallback = FrenchLesson(
        id: 1, date: nil, english: "Keep going.", french: "Continue.",
        frenchPronunciation: "Kohn-tee-new", patois: "Keep on gwaan.",
        kreyol: "Kontinye.", spanish: "Sigue adelante.",
        meaning: "A way to encourage someone to continue.",
        cultureNote: "A little encouragement can go a long way.",
        challenge: "How would you say “Keep going.” in French?", answer: "Continue.",
        audioUrl: nil
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

    var audioResourceName: String {
        english
            .folding(options: [.caseInsensitive, .diacriticInsensitive], locale: .current)
            .lowercased()
            .replacingOccurrences(of: "[^a-z0-9]+", with: "-", options: .regularExpression)
            .trimmingCharacters(in: CharacterSet(charactersIn: "-"))
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

    var title: String {
        switch self {
        case .french: "French"
        case .spanish: "Spanish"
        case .kreyol: "Kreyol"
        case .patois: "Patois"
        }
    }
}

struct AcademyCatalog: Codable {
    let ageGroups: [AgeGroup]
    let modules: [AcademyModule]

    enum CodingKeys: String, CodingKey {
        case ageGroups = "age_groups"
        case modules
    }

    static let fallback = AcademyCatalog(
        ageGroups: [
            AgeGroup(slug: "kids", title: "Kids", ages: "6-9", icon: "palette", guidance: "Learn through short role-play, drawing, matching, and speaking aloud.")
        ],
        modules: [
            AcademyModule(
                slug: "greetings",
                title: "Greetings",
                icon: "hand.wave.fill",
                isFree: true,
                description: "Meet people, introduce yourself, and close conversations with confidence.",
                lessons: [
                    AcademyLesson(
                        title: "Hello and Goodbye",
                        english: "Hello!",
                        french: "Bonjour !",
                        pronunciation: "bohn-zhoor",
                        teaching: "Use bonjour during the day with friends, teachers, coworkers, and new people.",
                        practice: "Greet three imaginary people with a smile and say bonjour clearly.",
                        culture: "A greeting is expected when entering many shops and small businesses in French-speaking places."
                    )
                ]
            )
        ]
    )
}

struct AgeGroup: Codable, Identifiable, Hashable {
    var id: String { slug }
    let slug: String
    let title: String
    let ages: String
    let icon: String
    let guidance: String
}

struct AcademyModule: Codable, Identifiable, Hashable {
    var id: String { slug }
    let slug: String
    let title: String
    let icon: String
    let isFree: Bool
    let description: String
    let lessons: [AcademyLesson]

    enum CodingKeys: String, CodingKey {
        case slug, title, icon, description, lessons
        case isFree = "free"
    }
}

struct AcademyLesson: Codable, Identifiable, Hashable {
    var id: String { title + french }
    let title: String
    let english: String
    let french: String
    let pronunciation: String
    let spanish: String?
    let kreyol: String?
    let patois: String?
    let teaching: String
    let practice: String
    let culture: String

    enum CodingKeys: String, CodingKey {
        case title, english, french, pronunciation, spanish, kreyol, patois, teaching, practice, culture
    }

    init(
        title: String,
        english: String,
        french: String,
        pronunciation: String,
        spanish: String? = nil,
        kreyol: String? = nil,
        patois: String? = nil,
        teaching: String,
        practice: String,
        culture: String
    ) {
        self.title = title
        self.english = english
        self.french = french
        self.pronunciation = pronunciation
        self.spanish = spanish
        self.kreyol = kreyol
        self.patois = patois
        self.teaching = teaching
        self.practice = practice
        self.culture = culture
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        title = try container.decode(String.self, forKey: .title)
        english = try container.decode(String.self, forKey: .english)
        french = try container.decode(String.self, forKey: .french)
        pronunciation = try container.decode(String.self, forKey: .pronunciation)
        spanish = try container.decodeIfPresent(String.self, forKey: .spanish)
        kreyol = try container.decodeIfPresent(String.self, forKey: .kreyol)
        patois = try container.decodeIfPresent(String.self, forKey: .patois)
        teaching = try container.decode(String.self, forKey: .teaching)
        practice = try container.decode(String.self, forKey: .practice)
        culture = try container.decode(String.self, forKey: .culture)
    }

    var beyondPhrase: BeyondPhrase {
        BeyondPhrase(
            french: french,
            pronunciation: pronunciation,
            spanish: spanish ?? Self.translationMap[english]?.spanish ?? "",
            kreyol: kreyol ?? Self.translationMap[english]?.kreyol ?? "",
            patois: patois ?? Self.translationMap[english]?.patois ?? ""
        )
    }
}

extension AcademyLesson {
    static let translationMap: [String: (spanish: String, kreyol: String, patois: String)] = [
        "Hello!": ("Hola!", "Bonjou!", "Wah gwaan!"),
        "My name is Alex.": ("Me llamo Alex.", "Mwen rele Alex.", "Mi name Alex."),
        "How are you?": ("Como estas?", "Kijan ou ye?", "How yuh duh?"),
        "I am well, thank you.": ("Estoy bien, gracias.", "Mwen byen, mesi.", "Mi good, thanks."),
        "Please. Thank you.": ("Por favor. Gracias.", "Souple. Mesi.", "Please. Tanks."),
        "Excuse me.": ("Disculpe.", "Eskize mwen.", "Excuse mi."),
        "Nice to meet you.": ("Mucho gusto.", "Mwen kontan rankontre ou.", "Nice fi meet yuh."),
        "Where are you from?": ("De donde eres?", "Ki kote ou soti?", "Weh yuh come from?"),
        "I come from Canada.": ("Vengo de Canada.", "Mwen soti Kanada.", "Mi come from Canada."),
        "See you soon.": ("Hasta pronto.", "A pita.", "See yuh soon."),
        "I would like some water.": ("Quisiera agua.", "Mwen ta renmen dlo.", "Mi would like some water."),
        "A baguette, please.": ("Una baguette, por favor.", "Yon baget, souple.", "One baguette, please."),
        "May I see the menu?": ("Puedo ver el menu?", "Eske mwen ka we meni an?", "Can mi see the menu?"),
        "I like apples.": ("Me gustan las manzanas.", "Mwen renmen pom.", "Mi like apples."),
        "I do not like onions.": ("No me gustan las cebollas.", "Mwen pa renmen zonyon.", "Mi nuh like onions."),
        "I am hungry.": ("Tengo hambre.", "Mwen grangou.", "Mi hungry."),
        "I am having breakfast.": ("Estoy desayunando.", "M ap pran dejene.", "Mi eating breakfast."),
        "How much does this cost?": ("Cuanto cuesta esto?", "Konbyen sa koute?", "How much dis cost?"),
        "It is delicious!": ("Esta delicioso!", "Li bon anpil!", "It taste good!"),
        "The bill, please.": ("La cuenta, por favor.", "Bodwo a, souple.", "The bill, please."),
        "Where is the bus stop?": ("Donde esta la parada de autobus?", "Ki kote estasyon otobis la ye?", "Weh di bus stop deh?"),
        "A ticket to Paris, please.": ("Un boleto a Paris, por favor.", "Yon biye pou Pari, souple.", "One ticket to Paris, please."),
        "I take the metro.": ("Tomo el metro.", "Mwen pran metro a.", "Mi take di metro."),
        "Call a taxi, please.": ("Llame un taxi, por favor.", "Rele yon taksi, souple.", "Call a taxi, please."),
        "How do I get to the station?": ("Como llego a la estacion?", "Kijan pou mwen rive nan estasyon an?", "How mi get to di station?"),
        "Where is the airport?": ("Donde esta el aeropuerto?", "Ki kote ayewopo a ye?", "Weh di airport deh?"),
        "Here is my passport.": ("Aqui esta mi pasaporte.", "Men paspo mwen.", "Here is mi passport."),
        "I have a reservation.": ("Tengo una reserva.", "Mwen gen yon rezervasyon.", "Mi have a reservation."),
        "What time does the train leave?": ("A que hora sale el tren?", "A kile tren an pati?", "What time di train leave?"),
        "We have arrived.": ("Hemos llegado.", "Nou rive.", "Wi reach."),
        "Let us go to the beach.": ("Vamos a la playa.", "Ann ale nan plaj la.", "Mek wi go beach."),
        "The ocean is beautiful.": ("El oceano es hermoso.", "Oseyan an bel.", "Di ocean beautiful."),
        "I know how to swim.": ("Se nadar.", "Mwen konn naje.", "Mi can swim."),
        "The waves are strong.": ("Las olas son fuertes.", "Vag yo fo.", "Di waves strong."),
        "We board the boat.": ("Subimos al barco.", "Nou monte bato a.", "Wi board di boat."),
        "Put on your life jacket.": ("Ponte el chaleco salvavidas.", "Mete jil sovtaj ou.", "Put on yuh life jacket."),
        "What is the weather like?": ("Como esta el clima?", "Ki jan tan an ye?", "How di weather stay?"),
        "I collect seashells.": ("Recojo conchas marinas.", "Mwen ranmase kokiyaj.", "Mi collect seashells."),
        "Look at the fish!": ("Mira los peces!", "Gade pwason yo!", "Look pon di fish dem!"),
        "Let us protect the ocean.": ("Protejamos el oceano.", "Ann pwoteje oseyan an.", "Mek wi protect di ocean."),
        "I play soccer.": ("Juego futbol.", "Mwen jwe foutbol.", "Mi play football."),
        "Let us watch the match.": ("Veamos el partido.", "Ann gade match la.", "Mek wi watch di match."),
        "This is my team.": ("Este es mi equipo.", "Sa a se ekip mwen.", "Dis a mi team."),
        "What is the score?": ("Cual es el marcador?", "Ki sko a?", "What di score?"),
        "We won!": ("Ganamos!", "Nou genyen!", "Wi win!"),
        "I am training today.": ("Estoy entrenando hoy.", "M ap antrene jodi a.", "Mi training today."),
        "I run fast.": ("Corro rapido.", "Mwen kouri vit.", "Mi run fast."),
        "I go swimming.": ("Voy a nadar.", "Mwen al naje.", "Mi go swimming."),
        "Well played!": ("Bien jugado!", "Byen jwe!", "Well played!"),
        "I am ready to play.": ("Estoy listo para jugar.", "Mwen pare pou jwe.", "Mi ready fi play.")
    ]
}

struct BeyondPhrase: Hashable {
    let french: String
    let pronunciation: String
    let spanish: String
    let kreyol: String
    let patois: String
}

struct AcademyLessonExperience: Hashable {
    let teaching: String
    let practice: String
    let checkPrompt: String
    let supportLine: String
}

extension AcademyLesson {
    func experience(for ageGroup: AgeGroup) -> AcademyLessonExperience {
        switch ageGroup.slug {
        case "preschool":
            return AcademyLessonExperience(
                teaching: "Grown-up helper: say \(french), clap the beats, then let your learner echo only the first word.",
                practice: "Point, smile, and repeat the phrase three times with a gesture.",
                checkPrompt: "Say or type one phrase you remember.",
                supportLine: "Tiny repetitions count. Keep it playful and short."
            )
        case "kids":
            return AcademyLessonExperience(
                teaching: "\(teaching) Listen once in French, then compare it with Spanish, Kreyol, and Patois.",
                practice: "\(practice) Pick one Beyond language and say that version too.",
                checkPrompt: "Choose a language and type the phrase for: \(english)",
                supportLine: "Try it like a mini role-play."
            )
        case "preteen":
            return AcademyLessonExperience(
                teaching: "\(teaching) Compare the phrase across French, Spanish, Kreyol, and Patois. Notice what changes and what stays familiar.",
                practice: "\(practice) Then say the same idea in two languages back to back.",
                checkPrompt: "Choose a language and type the complete phrase from memory.",
                supportLine: "Look for patterns, not just translation."
            )
        case "teen":
            return AcademyLessonExperience(
                teaching: "\(teaching) Practice it in French, then decide which Spanish, Kreyol, or Patois version feels closest in tone.",
                practice: "\(practice) Add a follow-up sentence you might actually use in any one language.",
                checkPrompt: "Choose a language and type the phrase exactly enough to use in conversation.",
                supportLine: "Aim for usable conversation, not classroom perfection."
            )
        case "adult":
            return AcademyLessonExperience(
                teaching: "\(teaching) Compare formality across French, Spanish, Kreyol, and Patois before choosing the version you would use.",
                practice: "\(practice) Say it once slowly, once at normal speed, and once in a real-life scenario.",
                checkPrompt: "Choose a language and type the phrase you would use in this situation: \(english)",
                supportLine: "Build one practical phrase you can use today."
            )
        default:
            return AcademyLessonExperience(
                teaching: teaching,
                practice: practice,
                checkPrompt: "Type the French phrase for: \(english)",
                supportLine: ageGroup.guidance
            )
        }
    }
}
