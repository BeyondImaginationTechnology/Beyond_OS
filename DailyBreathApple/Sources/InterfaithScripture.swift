import Foundation

enum FaithTradition: String, CaseIterable, Identifiable, Codable, Sendable {
    case bible
    case torah
    case quran

    var id: String { rawValue }

    var name: String {
        switch self {
        case .bible: "Bible"
        case .torah: "Torah"
        case .quran: "Quran"
        }
    }

    var libraryName: String {
        switch self {
        case .bible: "Bible"
        case .torah: "Torah & Tanakh"
        case .quran: "Quran"
        }
    }

    var symbolName: String {
        switch self {
        case .bible: "cross.fill"
        case .torah: "star.circle.fill"
        case .quran: "moon.stars.fill"
        }
    }

    var dailyReadingName: String {
        switch self {
        case .bible: "Bible Verse"
        case .torah: "Torah & Tanakh Passage"
        case .quran: "Quran Ayah"
        }
    }

    var passageUnitPlural: String {
        switch self {
        case .bible: "verses"
        case .torah: "pesukim"
        case .quran: "ayahs"
        }
    }

    var chapterUnitPlural: String {
        self == .quran ? "surahs" : "chapters"
    }

    var devotionalName: String {
        switch self {
        case .bible: "Christian Devotional"
        case .torah: "Jewish Daily Reflection"
        case .quran: "Quran Reflection"
        }
    }

    var prayerName: String {
        switch self {
        case .bible: "Prayer"
        case .torah: "Tefillah"
        case .quran: "Du’a"
        }
    }

    var prayerCollectionName: String {
        switch self {
        case .bible: "Specific Prayers"
        case .torah: "Jewish Tefillah"
        case .quran: "Du’a & Dhikr"
        }
    }

    var academyName: String {
        switch self {
        case .bible: "Christian Academy"
        case .torah: "Jewish Academy"
        case .quran: "Islamic Academy"
        }
    }

    var newsletterTagline: String {
        switch self {
        case .bible: "Grace for the next faithful step."
        case .torah: "Return, courage, and care for the next step."
        case .quran: "Mercy, remembrance, and the next right step."
        }
    }

    var guideName: String {
        switch self {
        case .bible: "Chris"
        case .torah: "Dovi"
        case .quran: "Moe"
        }
    }

    var guideAssetName: String {
        switch self {
        case .bible: "ChrisGuide"
        case .torah: "DoviGuide"
        case .quran: "MoeGuide"
        }
    }
}

enum ScriptureEdition: String, CaseIterable, Identifiable, Codable, Sendable {
    case bibleEnglish
    case bibleFrench
    case bibleSpanish
    case torahHebrew
    case torahEnglish
    case torahFrench
    case quranArabic
    case quranEnglish

    var id: String { rawValue }

    var tradition: FaithTradition {
        switch self {
        case .bibleEnglish, .bibleFrench, .bibleSpanish: .bible
        case .torahHebrew, .torahEnglish, .torahFrench: .torah
        case .quranArabic, .quranEnglish: .quran
        }
    }

    var languageName: String {
        switch self {
        case .bibleEnglish, .torahEnglish, .quranEnglish: "English"
        case .bibleFrench, .torahFrench: "Français"
        case .bibleSpanish: "Español"
        case .torahHebrew: "עברית"
        case .quranArabic: "العربية"
        }
    }

    var displayName: String {
        switch self {
        case .bibleEnglish: "English · World English Bible"
        case .bibleFrench: "Français · Louis Segond 1910"
        case .bibleSpanish: "Español · Reina-Valera 1909"
        case .torahHebrew: "עברית · Hebrew (Default)"
        case .torahEnglish: "English translation"
        case .torahFrench: "Traduction française"
        case .quranArabic: "العربية · Arabic (Default)"
        case .quranEnglish: "English meaning · Pickthall"
        }
    }

    var translationTitle: String {
        switch self {
        case .bibleEnglish: "World English Bible"
        case .bibleFrench: "Louis Segond 1910"
        case .bibleSpanish: "Reina-Valera 1909"
        case .torahHebrew: "Hebrew Tanakh · Public Domain"
        case .torahEnglish: "World English Bible · Jewish book names"
        case .torahFrench: "Louis Segond 1910 · Noms des livres juifs"
        case .quranArabic: "Arabic Quran · Uthmani text"
        case .quranEnglish: "Pickthall English Meaning"
        }
    }

    var attribution: String {
        switch self {
        case .bibleEnglish, .torahEnglish:
            "World English Bible · Public domain"
        case .bibleFrench, .torahFrench:
            "Louis Segond 1910 via eBible.org · Public domain"
        case .bibleSpanish:
            "Reina-Valera 1909 via eBible.org · Public domain"
        case .torahHebrew:
            "Modern Hebrew Bible via eBible.org · Public domain"
        case .quranArabic:
            "Quran JSON 3.1.2 · CC BY-SA 4.0 · Uthmani text from QuranEnc"
        case .quranEnglish:
            "Mohammed Marmaduke Pickthall · English meaning · Public domain in the USA"
        }
    }

    static func options(for tradition: FaithTradition) -> [ScriptureEdition] {
        allCases.filter { $0.tradition == tradition }
    }

    static func defaultEdition(for tradition: FaithTradition) -> ScriptureEdition {
        switch tradition {
        case .bible: .bibleEnglish
        case .torah: .torahHebrew
        case .quran: .quranArabic
        }
    }

    static func storageKey(for tradition: FaithTradition) -> String {
        "scriptureEdition.\(tradition.rawValue)"
    }
}

struct SacredTextVerse: Identifiable, Equatable, Sendable {
    let tradition: FaithTradition
    let bookCode: String
    let bookName: String
    let chapter: Int
    let verse: Int
    let text: String

    var id: String { "\(tradition.rawValue)-\(bookCode)-\(chapter)-\(verse)" }
    var reference: String {
        tradition == .quran ? "\(bookName) \(chapter):\(verse)" : "\(bookName) \(chapter):\(verse)"
    }
}

struct SacredTextChapter: Identifiable, Equatable, Sendable {
    let tradition: FaithTradition
    let bookCode: String
    let bookName: String
    let number: Int
    let verses: [SacredTextVerse]

    var id: String { "\(tradition.rawValue)-\(bookCode)-\(number)" }
    var title: String {
        if tradition == .quran {
            let hasArabic = bookName.unicodeScalars.contains { (0x0600...0x08FF).contains(Int($0.value)) }
            return hasArabic ? "سورة \(bookName)" : "Surah \(bookName)"
        }
        return "\(bookName) \(number)"
    }
}

struct SacredTextBook: Identifiable, Equatable, Sendable {
    let tradition: FaithTradition
    let code: String
    let name: String
    let subtitle: String
    let chapters: [SacredTextChapter]

    var id: String { "\(tradition.rawValue)-\(code)" }
    var verseCount: Int { chapters.reduce(0) { $0 + $1.verses.count } }
}

struct SacredTextLibrary: Equatable, Sendable {
    let tradition: FaithTradition
    let translation: String
    let books: [SacredTextBook]

    var verseCount: Int { books.reduce(0) { $0 + $1.verseCount } }
    var chapterCount: Int { books.reduce(0) { $0 + $1.chapters.count } }

    func chapter(bookCode: String, number: Int) -> SacredTextChapter? {
        books.first { $0.code == bookCode }?.chapters.first { $0.number == number }
    }

    func search(_ query: String, limit: Int = 100) -> [SacredTextVerse] {
        let terms = query.lowercased().split(whereSeparator: \.isWhitespace).map(String.init)
        guard !terms.isEmpty else { return [] }
        var matches: [SacredTextVerse] = []
        for book in books {
            for chapter in book.chapters {
                for verse in chapter.verses {
                    let searchable = "\(verse.reference) \(verse.text)".lowercased()
                    guard terms.allSatisfy({ searchable.contains($0) }) else { continue }
                    matches.append(verse)
                    if matches.count == limit { return matches }
                }
            }
        }
        return matches
    }

    func verse(bookCode: String, chapter: Int, verse: Int) -> SacredTextVerse? {
        self.chapter(bookCode: bookCode, number: chapter)?.verses.first { $0.verse == verse }
    }

    static func bible(from library: BibleLibrary) -> SacredTextLibrary {
        fromBible(library, tradition: .bible, include: { _ in true })
    }

    static func torah(from library: BibleLibrary) -> SacredTextLibrary {
        fromBible(
            library,
            tradition: .torah,
            include: { $0.testament == "Old Testament" },
            names: jewishBookNames,
            title: ScriptureEdition.torahEnglish.translationTitle
        )
    }

    private static func fromBible(
        _ library: BibleLibrary,
        tradition: FaithTradition,
        include: (BibleBook) -> Bool,
        names: [String: String] = [:],
        title: String? = nil
    ) -> SacredTextLibrary {
        let books = library.books.filter(include).map { book in
            SacredTextBook(
                tradition: tradition,
                code: book.code,
                name: names[book.code] ?? book.name,
                subtitle: tradition == .torah ? "Tanakh" : book.testament,
                chapters: book.chapters.map { chapter in
                    SacredTextChapter(
                        tradition: tradition,
                        bookCode: book.code,
                        bookName: names[book.code] ?? book.name,
                        number: chapter.number,
                        verses: chapter.verses.map {
                            SacredTextVerse(
                                tradition: tradition,
                                bookCode: $0.bookCode,
                                bookName: names[book.code] ?? $0.bookName,
                                chapter: $0.chapter,
                                verse: $0.verse,
                                text: $0.text
                            )
                        }
                    )
                }
            )
        }
        return SacredTextLibrary(tradition: tradition, translation: title ?? library.translation, books: books)
    }

    static func load(_ edition: ScriptureEdition, bundle: Bundle = .main) -> SacredTextLibrary {
        switch edition {
        case .bibleEnglish:
            return bible(from: .loadWorldEnglishBible(bundle: bundle))
        case .bibleFrench:
            return loadBiblicalEdition(
                resource: "fraLSG_vpl",
                edition: edition,
                names: frenchBibleBookNames,
                bundle: bundle
            )
        case .bibleSpanish:
            return loadBiblicalEdition(
                resource: "spaRV1909_vpl",
                edition: edition,
                names: spanishBibleBookNames,
                bundle: bundle
            )
        case .torahHebrew:
            return loadBiblicalEdition(
                resource: "heb_vpl",
                edition: edition,
                names: hebrewJewishBookNames,
                oldTestamentOnly: true,
                bundle: bundle
            )
        case .torahEnglish:
            return torah(from: .loadWorldEnglishBible(bundle: bundle))
        case .torahFrench:
            return loadBiblicalEdition(
                resource: "fraLSG_vpl",
                edition: edition,
                names: jewishBookNames,
                oldTestamentOnly: true,
                bundle: bundle
            )
        case .quranArabic:
            return loadQuranJSON(resource: "quran-ar", edition: edition, bundle: bundle)
        case .quranEnglish:
            return loadPickthallQuran(bundle: bundle)
        }
    }

    private static func loadBiblicalEdition(
        resource: String,
        edition: ScriptureEdition,
        names: [String: String],
        oldTestamentOnly: Bool = false,
        bundle: Bundle
    ) -> SacredTextLibrary {
        guard let url = bundle.url(forResource: resource, withExtension: "txt"),
              let source = try? String(contentsOf: url, encoding: .utf8) else {
            return SacredTextLibrary(tradition: edition.tradition, translation: edition.translationTitle, books: [])
        }
        let parsed = BibleLibrary(translation: edition.translationTitle, books: BibleLibrary.parse(source))
        return fromBible(
            parsed,
            tradition: edition.tradition,
            include: { !oldTestamentOnly || $0.testament == "Old Testament" },
            names: names,
            title: edition.translationTitle
        )
    }

    private struct QuranJSONChapter: Decodable {
        let id: Int
        let name: String
        let transliteration: String
        let translation: String?
        let verses: [QuranJSONVerse]
    }

    private struct QuranJSONVerse: Decodable {
        let id: Int
        let text: String
        let translation: String?
    }

    private static func loadQuranJSON(resource: String, edition: ScriptureEdition, bundle: Bundle) -> SacredTextLibrary {
        guard let url = bundle.url(forResource: resource, withExtension: "json"),
              let data = try? Data(contentsOf: url),
              let decoded = try? JSONDecoder().decode([QuranJSONChapter].self, from: data) else {
            return SacredTextLibrary(tradition: .quran, translation: edition.translationTitle, books: [])
        }
        let isArabic = edition == .quranArabic
        let books = decoded.map { item in
            let code = String(format: "Q%03d", item.id)
            let bookName = isArabic ? item.name : item.transliteration
            let verses = item.verses.map {
                SacredTextVerse(
                    tradition: .quran,
                    bookCode: code,
                    bookName: bookName,
                    chapter: item.id,
                    verse: $0.id,
                    text: isArabic ? $0.text : ($0.translation ?? $0.text)
                )
            }
            let chapter = SacredTextChapter(tradition: .quran, bookCode: code, bookName: bookName, number: item.id, verses: verses)
            return SacredTextBook(
                tradition: .quran,
                code: code,
                name: bookName,
                subtitle: isArabic ? item.transliteration : (item.translation ?? "Surah \(item.id)"),
                chapters: [chapter]
            )
        }
        return SacredTextLibrary(tradition: .quran, translation: edition.translationTitle, books: books)
    }

    private static let jewishBookNames: [String: String] = [
        "GEN": "Bereshit", "EXO": "Shemot", "LEV": "Vayikra", "NUM": "Bamidbar", "DEU": "Devarim",
        "JOS": "Yehoshua", "JDG": "Shoftim", "RUT": "Ruth", "1SA": "Shmuel I", "2SA": "Shmuel II",
        "1KI": "Melakhim I", "2KI": "Melakhim II", "1CH": "Divrei Hayamim I", "2CH": "Divrei Hayamim II",
        "EZR": "Ezra", "NEH": "Nechemyah", "EST": "Esther", "JOB": "Iyov", "PSA": "Tehillim",
        "PRO": "Mishlei", "ECC": "Kohelet", "SNG": "Shir HaShirim", "ISA": "Yeshayahu", "JER": "Yirmeyahu",
        "LAM": "Eikhah", "EZK": "Yechezkel", "DAN": "Daniel", "HOS": "Hoshea", "JOL": "Yoel", "AMO": "Amos",
        "OBA": "Ovadiah", "JON": "Yonah", "MIC": "Mikhah", "NAM": "Nahum", "HAB": "Habakkuk",
        "ZEP": "Tzefaniah", "HAG": "Haggai", "ZEC": "Zekhariah", "MAL": "Malakhi"
    ]

    private static let hebrewJewishBookNames: [String: String] = [
        "GEN": "בראשית · Bereshit", "EXO": "שמות · Shemot", "LEV": "ויקרא · Vayikra", "NUM": "במדבר · Bamidbar", "DEU": "דברים · Devarim",
        "JOS": "יהושע · Yehoshua", "JDG": "שופטים · Shoftim", "RUT": "רות · Ruth", "1SA": "שמואל א׳ · Shmuel I", "2SA": "שמואל ב׳ · Shmuel II",
        "1KI": "מלכים א׳ · Melakhim I", "2KI": "מלכים ב׳ · Melakhim II", "1CH": "דברי הימים א׳ · Divrei Hayamim I", "2CH": "דברי הימים ב׳ · Divrei Hayamim II",
        "EZR": "עזרא · Ezra", "NEH": "נחמיה · Nechemyah", "EST": "אסתר · Esther", "JOB": "איוב · Iyov", "PSA": "תהילים · Tehillim",
        "PRO": "משלי · Mishlei", "ECC": "קהלת · Kohelet", "SNG": "שיר השירים · Shir HaShirim", "ISA": "ישעיהו · Yeshayahu", "JER": "ירמיהו · Yirmeyahu",
        "LAM": "איכה · Eikhah", "EZK": "יחזקאל · Yechezkel", "DAN": "דניאל · Daniel", "HOS": "הושע · Hoshea", "JOL": "יואל · Yoel", "AMO": "עמוס · Amos",
        "OBA": "עובדיה · Ovadiah", "JON": "יונה · Yonah", "MIC": "מיכה · Mikhah", "NAM": "נחום · Nahum", "HAB": "חבקוק · Habakkuk",
        "ZEP": "צפניה · Tzefaniah", "HAG": "חגי · Haggai", "ZEC": "זכריה · Zekhariah", "MAL": "מלאכי · Malakhi"
    ]

    private static let frenchBibleBookNames: [String: String] = [
        "GEN": "Genèse", "EXO": "Exode", "LEV": "Lévitique", "NUM": "Nombres", "DEU": "Deutéronome", "JOS": "Josué", "JDG": "Juges",
        "1KI": "1 Rois", "2KI": "2 Rois", "1CH": "1 Chroniques", "2CH": "2 Chroniques", "NEH": "Néhémie", "PSA": "Psaumes",
        "PRO": "Proverbes", "ECC": "Ecclésiaste", "SNG": "Cantique des Cantiques", "ISA": "Ésaïe", "JER": "Jérémie", "LAM": "Lamentations",
        "EZK": "Ézékiel", "HOS": "Osée", "JOL": "Joël", "OBA": "Abdias", "JON": "Jonas", "MIC": "Michée", "HAB": "Habacuc",
        "ZEP": "Sophonie", "HAG": "Aggée", "ZEC": "Zacharie", "MAT": "Matthieu", "MRK": "Marc", "LUK": "Luc", "JHN": "Jean",
        "ACT": "Actes", "ROM": "Romains", "1CO": "1 Corinthiens", "2CO": "2 Corinthiens", "GAL": "Galates", "EPH": "Éphésiens",
        "PHP": "Philippiens", "1TH": "1 Thessaloniciens", "2TH": "2 Thessaloniciens", "1TI": "1 Timothée", "2TI": "2 Timothée",
        "PHM": "Philémon", "HEB": "Hébreux", "JAS": "Jacques", "1PE": "1 Pierre", "2PE": "2 Pierre",
        "1JN": "1 Jean", "2JN": "2 Jean", "3JN": "3 Jean", "JUD": "Jude", "REV": "Apocalypse"
    ]

    private static let spanishBibleBookNames: [String: String] = [
        "GEN": "Génesis", "EXO": "Éxodo", "LEV": "Levítico", "NUM": "Números", "DEU": "Deuteronomio", "JOS": "Josué", "JDG": "Jueces",
        "1KI": "1 Reyes", "2KI": "2 Reyes", "1CH": "1 Crónicas", "2CH": "2 Crónicas", "EZR": "Esdras", "NEH": "Nehemías", "PSA": "Salmos",
        "PRO": "Proverbios", "ECC": "Eclesiastés", "SNG": "Cantares", "ISA": "Isaías", "JER": "Jeremías", "LAM": "Lamentaciones",
        "EZK": "Ezequiel", "HOS": "Oseas", "JOL": "Joel", "OBA": "Abdías", "JON": "Jonás", "MIC": "Miqueas", "NAH": "Nahúm",
        "HAB": "Habacuc", "ZEP": "Sofonías", "HAG": "Hageo", "ZEC": "Zacarías", "MAT": "Mateo", "MRK": "Marcos", "LUK": "Lucas",
        "JHN": "Juan", "ACT": "Hechos", "ROM": "Romanos", "1CO": "1 Corintios", "2CO": "2 Corintios", "GAL": "Gálatas",
        "EPH": "Efesios", "PHP": "Filipenses", "COL": "Colosenses", "1TH": "1 Tesalonicenses", "2TH": "2 Tesalonicenses",
        "1TI": "1 Timoteo", "2TI": "2 Timoteo", "TIT": "Tito", "PHM": "Filemón", "HEB": "Hebreos", "JAS": "Santiago",
        "1PE": "1 Pedro", "2PE": "2 Pedro", "1JN": "1 Juan", "2JN": "2 Juan", "3JN": "3 Juan", "JUD": "Judas", "REV": "Apocalipsis"
    ]

    static func loadPickthallQuran(bundle: Bundle = .main) -> SacredTextLibrary {
        guard
            let url = bundle.url(forResource: "quran-pickthall-vpl", withExtension: "txt"),
            let source = try? String(contentsOf: url, encoding: .utf8)
        else {
            return SacredTextLibrary(tradition: .quran, translation: "Pickthall English Meaning", books: [])
        }

        var versesBySurah: [Int: [SacredTextVerse]] = [:]
        var names: [Int: String] = [:]
        for line in source.split(whereSeparator: \.isNewline) where !line.hasPrefix("#") {
            let parts = line.split(separator: "|", maxSplits: 3, omittingEmptySubsequences: false)
            guard parts.count == 4, let surah = Int(parts[0]), let verse = Int(parts[1]) else { continue }
            let rawName = String(parts[2]).components(separatedBy: " (").first ?? String(parts[2])
            let name = rawName == "AL-E-IMRAN"
                ? "Al-Imran"
                : rawName.replacingOccurrences(of: "AL-", with: "Al-").capitalized
            names[surah] = name
            versesBySurah[surah, default: []].append(
                SacredTextVerse(
                    tradition: .quran,
                    bookCode: String(format: "Q%03d", surah),
                    bookName: name,
                    chapter: surah,
                    verse: verse,
                    text: String(parts[3])
                )
            )
        }

        let books = (1...114).compactMap { surah -> SacredTextBook? in
            guard let verses = versesBySurah[surah], let name = names[surah] else { return nil }
            let code = String(format: "Q%03d", surah)
            let chapter = SacredTextChapter(
                tradition: .quran,
                bookCode: code,
                bookName: name,
                number: surah,
                verses: verses.sorted { $0.verse < $1.verse }
            )
            return SacredTextBook(
                tradition: .quran,
                code: code,
                name: name,
                subtitle: "Surah \(surah)",
                chapters: [chapter]
            )
        }
        return SacredTextLibrary(tradition: .quran, translation: "Pickthall English Meaning", books: books)
    }
}

enum InterfaithDailyContent {
    private static let torahCourage = ["DEU-31-6", "JOS-1-9", "PSA-27-14", "PSA-31-24", "ISA-41-10"]
    private static let torahPeace = ["PSA-4-8", "PSA-23-4", "ISA-26-3", "PRO-3-5", "PSA-46-10"]
    private static let torahRecovery = ["PSA-40-1", "PSA-107-14", "ISA-43-2", "PRO-24-16", "PSA-118-5"]
    private static let quranCourage = ["Q003-3-200", "Q002-2-286", "Q009-9-40", "Q094-94-5", "Q065-65-3"]
    private static let quranPeace = ["Q013-13-28", "Q002-2-153", "Q039-39-23", "Q089-89-27", "Q048-48-4"]
    private static let quranRecovery = ["Q039-39-53", "Q012-12-87", "Q003-3-139", "Q005-5-90", "Q094-94-6"]

    static func verse(
        for tradition: FaithTradition,
        date: Date = Date(),
        bibleVerse: Verse,
        bible: SacredTextLibrary,
        torah: SacredTextLibrary,
        quran: SacredTextLibrary
    ) -> Verse {
        switch tradition {
        case .bible:
            return bibleVerse
        case .torah:
            if let same = resolve(reference: bibleVerse.reference, in: torah) {
                return displayVerse(same, reflection: "Dovi invites you to carry this Jewish Scripture into one honest, healthy choice today.")
            }
            let pool = biblicalPool(for: bibleVerse)
            let selected = pool[stableIndex(date, count: pool.count)]
            return resolve(code: selected, in: torah).map {
                displayVerse($0, reflection: "Dovi pairs this teaching with today’s recovery theme: one breath, one faithful next step.")
            } ?? bibleVerse
        case .quran:
            if bibleVerse.reference == "Psalm 27:14", let matched = resolve(code: "Q003-3-200", in: quran) {
                return displayVerse(matched, reflection: "Moe pairs perseverance in this ayah with today’s call to wait with courage.")
            }
            let pool = quranPool(for: bibleVerse)
            let selected = pool[stableIndex(date, count: pool.count)]
            return resolve(code: selected, in: quran).map {
                displayVerse($0, reflection: "Moe pairs this ayah with today’s recovery theme: pause, seek Allah’s help, and choose the next right step.")
            } ?? bibleVerse
        }
    }

    static func devotional(for tradition: FaithTradition, base: Devotional, verse: Verse) -> Devotional {
        guard tradition != .bible else { return base }

        let content: (title: String, excerpt: String, body: String, prayer: String, practice: String)
        switch (tradition, theme(for: verse)) {
        case (.torah, .courage):
            content = (
                "Chizuk for the Next Step",
                "Receive chizuk—strength and encouragement—for one honest choice today.",
                "This passage from the Tanakh joins courage with responsibility. Teshuvah is a return: not pretending the past did not happen, but turning toward God, truth, repair, and life. You do not need to finish the whole journey in this moment. Take the next faithful step and let trusted community help you keep returning.",
                "Source of strength, steady my heart and help me choose what leads toward truth, repair, and life. Give me courage to ask for help and wisdom to take the next right step.",
                "Read \(verse.reference) slowly. Name one act of teshuvah—return or repair—you can begin today, then share it with someone trustworthy."
            )
        case (.torah, .peace):
            content = (
                "A Quiet Moment of Tefillah",
                "Let sacred reading, honest self-reckoning, and a quiet breath make room for peace.",
                "Jewish tefillah is more than asking for something; it is also connection and honest self-examination before God. Let this passage slow the urgency of the moment. Breathe, notice what is true, and choose a response shaped by wisdom rather than impulse.",
                "Holy One, quiet what is restless in me. Help me listen honestly, receive support, and act with patience, compassion, and good judgment.",
                "Take three slow breaths after reading \(verse.reference). Sit in silence for one minute, then write one truthful sentence about what you need now."
            )
        case (.torah, .recovery):
            content = (
                "Teshuvah, One Step at a Time",
                "Recovery can be a practice of return, repair, and renewed responsibility.",
                "Teshuvah means return. In recovery, return may look like telling the truth, making repair where it is safe, accepting help, and choosing life again after a setback. This Tanakh passage does not ask for perfection. It invites a sincere turn toward God and toward the next action that protects healing.",
                "God of our ancestors, help me return to what is true and life-giving. Give me humility to repair what I can, courage to seek help, and strength for today’s next step.",
                "Choose one concrete act of teshuvah today: tell the truth, contact support, remove a trigger, or make a safe plan for repair."
            )
        case (.quran, .courage):
            content = (
                "Sabr for the Next Step",
                "Practice sabr—steadfast patience—without facing the struggle alone.",
                "This ayah calls you toward sabr: patient perseverance rooted in trust in Allah. Sabr is not passive resignation. It can mean pausing before harm, seeking wise support, and continuing with the next right action even when the path feels difficult.",
                "Allah, grant me sabr, clear judgment, and courage. Protect me from choices that cause harm, guide me toward trustworthy support, and strengthen me for the next right step. Amin.",
                "Read \(verse.reference) slowly, pause for three breaths, and say: Hasbunallahu wa ni‘mal-wakil—Allah is sufficient for us and the best disposer of affairs. Then contact one safe support person."
            )
        case (.quran, .peace):
            content = (
                "Peace Through Dhikr",
                "Return your attention to Allah through remembrance, breath, and a grounded next action.",
                "This ayah points toward remembrance of Allah. Dhikr can interrupt the rush of fear or craving and reorient the heart. Let remembrance accompany practical care: slow down, seek help when needed, and choose what protects your wellbeing and responsibilities.",
                "Allah, steady my heart in Your remembrance. Grant me calm without denial, wisdom without fear, and the willingness to receive the help I need. Amin.",
                "Breathe gently and repeat SubhanAllah, Alhamdulillah, and Allahu Akbar at a comfortable pace. Then write the safest next action you can take."
            )
        case (.quran, .recovery):
            content = (
                "Tawbah and Hope",
                "Turn back to Allah with honesty, hope, and a concrete commitment to change.",
                "Tawbah is a sincere return to Allah. It includes leaving harm, regretting it, asking forgiveness, and resolving to change; repair is also due when another person’s rights were harmed. A setback does not place you beyond Allah’s mercy. Begin again with honesty and seek the human support that recovery requires.",
                "Allah, You are merciful and accepting of repentance. Help me leave what harms me, make amends where I safely can, and remain firm in recovery. Guide me to people and choices that support what is good. Amin.",
                "Say Astaghfirullah with attention, then take one concrete step: remove access to a trigger, contact support, or renew today’s recovery plan."
            )
        case (.bible, _):
            return base
        }

        return Devotional(
            id: base.id + (tradition == .torah ? 10_000 : 20_000),
            title: content.title,
            excerpt: content.excerpt,
            body: content.body,
            scripture: verse.reference,
            minutes: base.minutes,
            prayer: content.prayer,
            practice: content.practice
        )
    }

    static func challenge(for tradition: FaithTradition, base: RecoveryChallenge, verse: Verse) -> RecoveryChallenge {
        guard tradition != .bible else { return base }
        let readingName = tradition == .quran ? "daily ayah" : "daily passage"
        let steps = base.steps.map {
            $0.replacingOccurrences(of: "recovery verse", with: readingName, options: .caseInsensitive)
                .replacingOccurrences(of: "brief prayer", with: "brief spiritual practice", options: .caseInsensitive)
        }
        return RecoveryChallenge(
            id: base.id,
            title: base.title,
            description: base.description,
            scriptureReference: verse.reference,
            steps: steps,
            targetCount: base.targetCount,
            startsOn: base.startsOn,
            endsOn: base.endsOn
        )
    }

    private static func displayVerse(_ verse: SacredTextVerse, reflection: String) -> Verse {
        Verse(id: verse.id.hashValue & Int.max, text: verse.text, reference: verse.reference, reflection: reflection)
    }

    private static func biblicalPool(for verse: Verse) -> [String] {
        switch theme(for: verse) {
        case .courage: torahCourage
        case .peace: torahPeace
        case .recovery: torahRecovery
        }
    }

    private static func quranPool(for verse: Verse) -> [String] {
        switch theme(for: verse) {
        case .courage: quranCourage
        case .peace: quranPeace
        case .recovery: quranRecovery
        }
    }

    private enum Theme { case courage, peace, recovery }

    private static func theme(for verse: Verse) -> Theme {
        let value = "\(verse.reference) \(verse.text)".lowercased()
        if ["strong", "courage", "fear", "endure", "persevere"].contains(where: value.contains) { return .courage }
        if ["peace", "rest", "still", "quiet", "comfort"].contains(where: value.contains) { return .peace }
        return .recovery
    }

    private static func stableIndex(_ date: Date, count: Int) -> Int {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = .current
        let day = calendar.ordinality(of: .day, in: .era, for: date) ?? 1
        return (day - 1) % count
    }

    private static func resolve(reference: String, in library: SacredTextLibrary) -> SacredTextVerse? {
        let pattern = #"^(.+?)\s+(\d+):(\d+)"#
        guard let regex = try? NSRegularExpression(pattern: pattern),
              let match = regex.firstMatch(in: reference, range: NSRange(reference.startIndex..., in: reference)),
              let bookRange = Range(match.range(at: 1), in: reference),
              let chapterRange = Range(match.range(at: 2), in: reference),
              let verseRange = Range(match.range(at: 3), in: reference),
              let chapter = Int(reference[chapterRange]),
              let verse = Int(reference[verseRange]) else { return nil }
        let bookName = normalizedBookName(String(reference[bookRange]))
        let canonicalCode = biblicalBookCode(for: bookName)
        return library.books.first { book in
            (canonicalCode != nil && book.code == canonicalCode) || normalizedBookName(book.name) == bookName
        }?
            .chapters.first { $0.number == chapter }?.verses.first { $0.verse == verse }
    }

    private static func biblicalBookCode(for name: String) -> String? {
        switch name {
        case "genesis": "GEN"
        case "exodus": "EXO"
        case "leviticus": "LEV"
        case "numbers": "NUM"
        case "deuteronomy": "DEU"
        case "joshua": "JOS"
        case "judges": "JDG"
        case "ruth": "RUT"
        case "1 samuel": "1SA"
        case "2 samuel": "2SA"
        case "1 kings": "1KI"
        case "2 kings": "2KI"
        case "1 chronicles": "1CH"
        case "2 chronicles": "2CH"
        case "ezra": "EZR"
        case "nehemiah": "NEH"
        case "esther": "EST"
        case "job": "JOB"
        case "psalms": "PSA"
        case "proverbs": "PRO"
        case "ecclesiastes": "ECC"
        case "song of solomon": "SNG"
        case "isaiah": "ISA"
        case "jeremiah": "JER"
        case "lamentations": "LAM"
        case "ezekiel": "EZK"
        case "daniel": "DAN"
        case "hosea": "HOS"
        case "joel": "JOL"
        case "amos": "AMO"
        case "obadiah": "OBA"
        case "jonah": "JON"
        case "micah": "MIC"
        case "nahum": "NAM"
        case "habakkuk": "HAB"
        case "zephaniah": "ZEP"
        case "haggai": "HAG"
        case "zechariah": "ZEC"
        case "malachi": "MAL"
        default: nil
        }
    }

    private static func normalizedBookName(_ value: String) -> String {
        let normalized = value
            .folding(options: [.caseInsensitive, .diacriticInsensitive], locale: Locale(identifier: "en_US_POSIX"))
            .lowercased()
            .trimmingCharacters(in: .whitespacesAndNewlines)
        switch normalized {
        case "psalm", "psalms": "psalms"
        case "song of solomon", "song of songs": "song of solomon"
        default: normalized
        }
    }

    private static func resolve(code: String, in library: SacredTextLibrary) -> SacredTextVerse? {
        let parts = code.split(separator: "-")
        guard parts.count == 3, let chapter = Int(parts[1]), let verse = Int(parts[2]) else { return nil }
        return library.verse(bookCode: String(parts[0]), chapter: chapter, verse: verse)
    }
}
