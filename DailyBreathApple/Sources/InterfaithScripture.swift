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
        case .torah: "star.of.david"
        case .quran: "moon.stars.fill"
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
    var title: String { tradition == .quran ? "Surah \(bookName)" : "\(bookName) \(number)" }
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
        fromBible(library, tradition: .torah, include: { $0.testament == "Old Testament" })
    }

    private static func fromBible(
        _ library: BibleLibrary,
        tradition: FaithTradition,
        include: (BibleBook) -> Bool
    ) -> SacredTextLibrary {
        let books = library.books.filter(include).map { book in
            SacredTextBook(
                tradition: tradition,
                code: book.code,
                name: book.name,
                subtitle: tradition == .torah ? "Tanakh" : book.testament,
                chapters: book.chapters.map { chapter in
                    SacredTextChapter(
                        tradition: tradition,
                        bookCode: book.code,
                        bookName: book.name,
                        number: chapter.number,
                        verses: chapter.verses.map {
                            SacredTextVerse(
                                tradition: tradition,
                                bookCode: $0.bookCode,
                                bookName: $0.bookName,
                                chapter: $0.chapter,
                                verse: $0.verse,
                                text: $0.text
                            )
                        }
                    )
                }
            )
        }
        let title = tradition == .torah ? "World English Bible — Hebrew Scriptures" : library.translation
        return SacredTextLibrary(tradition: tradition, translation: title, books: books)
    }

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
        let bookName = String(reference[bookRange])
        return library.books.first { $0.name.caseInsensitiveCompare(bookName) == .orderedSame }?
            .chapters.first { $0.number == chapter }?.verses.first { $0.verse == verse }
    }

    private static func resolve(code: String, in library: SacredTextLibrary) -> SacredTextVerse? {
        let parts = code.split(separator: "-")
        guard parts.count == 3, let chapter = Int(parts[1]), let verse = Int(parts[2]) else { return nil }
        return library.verse(bookCode: String(parts[0]), chapter: chapter, verse: verse)
    }
}
