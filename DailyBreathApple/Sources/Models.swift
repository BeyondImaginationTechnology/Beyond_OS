import Foundation

struct Verse: Identifiable, Codable, Equatable {
    let id: Int
    let text: String
    let reference: String
    let reflection: String
}

struct Devotional: Identifiable, Codable, Equatable {
    let id: Int
    let title: String
    let excerpt: String
    let body: String
    let scripture: String
    let minutes: Int
    let prayer: String
    let practice: String
}

struct PrayerPractice: Identifiable, Equatable {
    let id: Int
    let title: String
    let subtitle: String
    let systemImage: String
}

struct AcademyLesson: Identifiable, Equatable {
    let id: Int
    let title: String
    let duration: String
    let scripture: String
    let summary: String
    let teaching: String
    let practice: String
    let reflectionPrompt: String
    let checkPrompt: String
    let checkAnswer: String
}

struct AcademyPath: Identifiable, Equatable {
    let id: Int
    let title: String
    let subtitle: String
    let systemImage: String
    let lessons: [AcademyLesson]
}

struct BreathPattern: Identifiable, Equatable {
    let id: Int
    let title: String
    let intention: String
    let instruction: String
    let inhale: Int
    let hold: Int
    let exhale: Int

    var rhythmText: String {
        "Inhale \(inhale) · Hold \(hold) · Exhale \(exhale)"
    }

    static let dailyPatterns = [
        BreathPattern(
            id: 1,
            title: "Peace Breath",
            intention: "Settle your pace before the day asks for more.",
            instruction: "Inhale for four, hold for four, exhale for six.",
            inhale: 4,
            hold: 4,
            exhale: 6
        ),
        BreathPattern(
            id: 2,
            title: "Mercy Breath",
            intention: "Make room for patience with yourself and others.",
            instruction: "Inhale for three, hold for three, exhale for five.",
            inhale: 3,
            hold: 3,
            exhale: 5
        ),
        BreathPattern(
            id: 3,
            title: "Courage Breath",
            intention: "Enter the next step with a steady heart.",
            instruction: "Inhale for four, hold for two, exhale for four.",
            inhale: 4,
            hold: 2,
            exhale: 4
        )
    ]

    static func breathOfTheDay(for date: Date = Date(), calendar: Calendar = .current) -> BreathPattern {
        let day = calendar.ordinality(of: .day, in: .era, for: date) ?? 1
        return dailyPatterns[(day - 1) % dailyPatterns.count]
    }
}

struct JournalEntry: Identifiable, Equatable {
    let id: UUID
    let createdAt: Date
    let prompt: String
    let text: String
    let mood: String?
}

struct BibleVerse: Identifiable, Equatable {
    let bookCode: String
    let bookName: String
    let chapter: Int
    let verse: Int
    let text: String

    var id: String { "\(bookCode)-\(chapter)-\(verse)" }
    var reference: String { "\(bookName) \(chapter):\(verse)" }
}

struct BibleChapter: Identifiable, Equatable {
    let bookCode: String
    let bookName: String
    let number: Int
    let verses: [BibleVerse]

    var id: String { "\(bookCode)-\(number)" }
    var title: String { "\(bookName) \(number)" }
}

struct BibleBook: Identifiable, Equatable {
    let code: String
    let name: String
    let testament: String
    let chapters: [BibleChapter]

    var id: String { code }
    var verseCount: Int { chapters.reduce(0) { $0 + $1.verses.count } }
}

struct BibleLibrary: Equatable {
    let translation: String
    let books: [BibleBook]

    var verseCount: Int { books.reduce(0) { $0 + $1.verseCount } }

    func chapter(bookCode: String, number: Int) -> BibleChapter? {
        books.first { $0.code == bookCode }?.chapters.first { $0.number == number }
    }

    func search(_ query: String, limit: Int = 80) -> [BibleVerse] {
        let terms = query
            .lowercased()
            .split(whereSeparator: { $0.isWhitespace })
            .map(String.init)
        guard !terms.isEmpty else { return [] }

        var matches: [BibleVerse] = []
        for book in books {
            for chapter in book.chapters {
                for verse in chapter.verses {
                    let searchable = "\(verse.reference) \(verse.text)".lowercased()
                    guard terms.allSatisfy({ searchable.contains($0) }) else { continue }
                    matches.append(verse)
                    if matches.count >= limit { return matches }
                }
            }
        }
        return matches
    }

    static func loadWorldEnglishBible() -> BibleLibrary {
        guard
            let url = Bundle.main.url(forResource: "engwebp_vpl", withExtension: "txt"),
            let source = try? String(contentsOf: url, encoding: .utf8)
        else {
            return BibleLibrary(translation: "World English Bible", books: [])
        }

        return BibleLibrary(translation: "World English Bible", books: parse(source))
    }

    static func parse(_ source: String) -> [BibleBook] {
        var versesByBook: [String: [BibleVerse]] = [:]

        for line in source.split(whereSeparator: \.isNewline) {
            let parts = line.split(separator: " ", maxSplits: 2, omittingEmptySubsequences: true)
            guard parts.count == 3 else { continue }
            let code = String(parts[0])
            let chapterVerse = parts[1].split(separator: ":", maxSplits: 1)
            guard
                chapterVerse.count == 2,
                let chapter = Int(chapterVerse[0]),
                let verse = Int(chapterVerse[1]),
                let metadata = bookMetadata[code]
            else {
                continue
            }

            let bibleVerse = BibleVerse(
                bookCode: code,
                bookName: metadata.name,
                chapter: chapter,
                verse: verse,
                text: String(parts[2])
            )
            versesByBook[code, default: []].append(bibleVerse)
        }

        return bookOrder.compactMap { code in
            guard let metadata = bookMetadata[code], let verses = versesByBook[code], !verses.isEmpty else {
                return nil
            }

            let grouped = Dictionary(grouping: verses, by: \.chapter)
            let chapters = grouped.keys.sorted().map { number in
                BibleChapter(
                    bookCode: code,
                    bookName: metadata.name,
                    number: number,
                    verses: grouped[number, default: []].sorted { $0.verse < $1.verse }
                )
            }
            return BibleBook(code: code, name: metadata.name, testament: metadata.testament, chapters: chapters)
        }
    }

    private static let bookMetadata: [String: (name: String, testament: String)] = [
        "GEN": ("Genesis", "Old Testament"), "EXO": ("Exodus", "Old Testament"), "LEV": ("Leviticus", "Old Testament"),
        "NUM": ("Numbers", "Old Testament"), "DEU": ("Deuteronomy", "Old Testament"), "JOS": ("Joshua", "Old Testament"),
        "JDG": ("Judges", "Old Testament"), "RUT": ("Ruth", "Old Testament"), "1SA": ("1 Samuel", "Old Testament"),
        "2SA": ("2 Samuel", "Old Testament"), "1KI": ("1 Kings", "Old Testament"), "2KI": ("2 Kings", "Old Testament"),
        "1CH": ("1 Chronicles", "Old Testament"), "2CH": ("2 Chronicles", "Old Testament"), "EZR": ("Ezra", "Old Testament"),
        "NEH": ("Nehemiah", "Old Testament"), "EST": ("Esther", "Old Testament"), "JOB": ("Job", "Old Testament"),
        "PSA": ("Psalms", "Old Testament"), "PRO": ("Proverbs", "Old Testament"), "ECC": ("Ecclesiastes", "Old Testament"),
        "SNG": ("Song of Solomon", "Old Testament"), "ISA": ("Isaiah", "Old Testament"), "JER": ("Jeremiah", "Old Testament"),
        "LAM": ("Lamentations", "Old Testament"), "EZK": ("Ezekiel", "Old Testament"), "DAN": ("Daniel", "Old Testament"),
        "HOS": ("Hosea", "Old Testament"), "JOL": ("Joel", "Old Testament"), "AMO": ("Amos", "Old Testament"),
        "OBA": ("Obadiah", "Old Testament"), "JON": ("Jonah", "Old Testament"), "MIC": ("Micah", "Old Testament"),
        "NAM": ("Nahum", "Old Testament"), "HAB": ("Habakkuk", "Old Testament"), "ZEP": ("Zephaniah", "Old Testament"),
        "HAG": ("Haggai", "Old Testament"), "ZEC": ("Zechariah", "Old Testament"), "MAL": ("Malachi", "Old Testament"),
        "MAT": ("Matthew", "New Testament"), "MRK": ("Mark", "New Testament"), "LUK": ("Luke", "New Testament"),
        "JHN": ("John", "New Testament"), "ACT": ("Acts", "New Testament"), "ROM": ("Romans", "New Testament"),
        "1CO": ("1 Corinthians", "New Testament"), "2CO": ("2 Corinthians", "New Testament"),
        "GAL": ("Galatians", "New Testament"), "EPH": ("Ephesians", "New Testament"), "PHP": ("Philippians", "New Testament"),
        "COL": ("Colossians", "New Testament"), "1TH": ("1 Thessalonians", "New Testament"),
        "2TH": ("2 Thessalonians", "New Testament"), "1TI": ("1 Timothy", "New Testament"),
        "2TI": ("2 Timothy", "New Testament"), "TIT": ("Titus", "New Testament"), "PHM": ("Philemon", "New Testament"),
        "HEB": ("Hebrews", "New Testament"), "JAS": ("James", "New Testament"), "1PE": ("1 Peter", "New Testament"),
        "2PE": ("2 Peter", "New Testament"), "1JN": ("1 John", "New Testament"), "2JN": ("2 John", "New Testament"),
        "3JN": ("3 John", "New Testament"), "JUD": ("Jude", "New Testament"), "REV": ("Revelation", "New Testament")
    ]

    private static let bookOrder = [
        "GEN", "EXO", "LEV", "NUM", "DEU", "JOS", "JDG", "RUT", "1SA", "2SA", "1KI", "2KI", "1CH", "2CH",
        "EZR", "NEH", "EST", "JOB", "PSA", "PRO", "ECC", "SNG", "ISA", "JER", "LAM", "EZK", "DAN", "HOS",
        "JOL", "AMO", "OBA", "JON", "MIC", "NAM", "HAB", "ZEP", "HAG", "ZEC", "MAL", "MAT", "MRK", "LUK",
        "JHN", "ACT", "ROM", "1CO", "2CO", "GAL", "EPH", "PHP", "COL", "1TH", "2TH", "1TI", "2TI", "TIT",
        "PHM", "HEB", "JAS", "1PE", "2PE", "1JN", "2JN", "3JN", "JUD", "REV"
    ]
}

extension Verse {
    static let daily = Verse(
        id: 1,
        text: "If therefore the Son makes you free, you will be free indeed.",
        reference: "John 8:36",
        reflection: "When a craving rises, pause before you answer it. Take one honest breath, ask God for help, and choose the next free step."
    )
}

extension Devotional {
    static let today = Devotional(
        id: 1,
        title: "One Free Breath",
        excerpt: "Meet the next craving with prayer, patience, and one faithful choice.",
        body: "Freedom often arrives one moment at a time. You do not have to solve every urge at once; you can bring this breath, this craving, and this decision to God. When the pull feels loud, pause long enough to remember that grace is present here too. Choose the next free step, then the next one after that.",
        scripture: "John 8:36",
        minutes: 5,
        prayer: "Lord, strengthen me in this moment. Help me breathe through the craving, receive your grace, and choose freedom one step at a time.",
        practice: "When an urge comes, take three slow breaths, drink water, and pray: Lord, help me choose freedom now."
    )
}
