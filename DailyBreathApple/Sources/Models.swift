import Foundation

struct Verse: Identifiable, Equatable {
    let id: Int
    let text: String
    let reference: String
    let reflection: String
}

struct Devotional: Identifiable, Equatable {
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

struct AcademyModule: Identifiable, Equatable {
    let id: Int
    let title: String
    let subtitle: String
    let progress: Double
    let isFree: Bool
}

struct JournalEntry: Identifiable, Equatable {
    let id: UUID
    let createdAt: Date
    let text: String
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
        text: "Be still, and know that I am God.",
        reference: "Psalm 46:10",
        reflection: "Begin slowly. Make room for quiet, notice your breath, and let the next faithful step be enough for today."
    )
}

extension Devotional {
    static let today = Devotional(
        id: 1,
        title: "Walk in Quiet Confidence",
        excerpt: "Make room for stillness and remember that God is present before your next step.",
        body: "Stillness is not empty time. It is a faithful pause where you remember that God is already present, already attentive, and already enough for the road in front of you. Begin today by slowing your pace before you solve everything. Let confidence grow from trust, not hurry.",
        scripture: "Psalm 46:10",
        minutes: 5,
        prayer: "Lord, quiet my heart and steady my thoughts. Help me move through today with trust, patience, and courage.",
        practice: "Before your next task, take three slow breaths and name one thing you can entrust to God."
    )
}
