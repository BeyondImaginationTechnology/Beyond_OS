import CryptoKit
import Foundation
import Security

struct Verse: Identifiable, Codable, Equatable, Sendable {
    let id: Int
    let text: String
    let reference: String
    let reflection: String
    let audioURL: String?

    enum CodingKeys: String, CodingKey {
        case id, text, reference, reflection
        case audioURL = "audio_url"
    }

    init(id: Int, text: String, reference: String, reflection: String, audioURL: String? = nil) {
        self.id = id
        self.text = text
        self.reference = reference
        self.reflection = reflection
        self.audioURL = audioURL
    }
}

struct Devotional: Identifiable, Codable, Equatable, Sendable {
    let id: Int
    let title: String
    let excerpt: String
    let body: String
    let scripture: String
    let minutes: Int
    let prayer: String
    let practice: String
}

struct RecoveryChallenge: Identifiable, Codable, Equatable, Sendable {
    let id: String
    let title: String
    let description: String
    let scriptureReference: String
    let steps: [String]
    let targetCount: Int
    let startsOn: String
    let endsOn: String

    enum CodingKeys: String, CodingKey {
        case id, title, description, steps
        case scriptureReference = "scripture_reference"
        case targetCount = "target_count"
        case startsOn = "starts_on"
        case endsOn = "ends_on"
    }
}

enum RecoveryContent {
    private struct VerseDocument: Decodable { let entries: [VerseEntry] }
    private struct VerseEntry: Decodable {
        let text: String
        let reference: String
        let theme: String
        let scheduleDate: String?

        enum CodingKeys: String, CodingKey {
            case text, reference, theme
            case scheduleDate = "schedule_date"
        }
    }

    private struct DevotionalDocument: Decodable { let entries: [DevotionalEntry] }
    private struct DevotionalEntry: Decodable {
        let title: String
        let excerpt: String
        let body: String
        let scriptureReference: String
        let prayer: String
        let practice: String
        let durationMinutes: Int
        let scheduleDate: String?
        let scheduleRole: String?

        enum CodingKeys: String, CodingKey {
            case title, excerpt, body, prayer, practice
            case scriptureReference = "scripture_reference"
            case durationMinutes = "duration_minutes"
            case scheduleDate = "schedule_date"
            case scheduleRole = "schedule_role"
        }
    }

    private struct ChallengeDocument: Decodable { let entries: [RecoveryChallenge] }

    static func verseOfTheDay(for date: Date = Date(), bundle: Bundle = .main) -> Verse? {
        guard let document: VerseDocument = decode("daily-verses", bundle: bundle), !document.entries.isEmpty else { return nil }
        let key = dateKey(date)
        let index = document.entries.firstIndex { $0.scheduleDate == key }
            ?? rotationIndex(date, count: document.entries.count)
        let entry = document.entries[index]
        return Verse(
            id: index + 1,
            text: entry.text,
            reference: entry.reference,
            reflection: "Carry this verse with you today, and let it guide your next faithful step."
        )
    }

    static func devotionalOfTheDay(for date: Date = Date(), bundle: Bundle = .main) -> Devotional? {
        guard let document: DevotionalDocument = decode("daily-devotionals", bundle: bundle), !document.entries.isEmpty else { return nil }
        let key = dateKey(date)
        let index = document.entries.firstIndex { $0.scheduleDate == key } ?? rotationIndex(date, count: document.entries.count)
        let entry = document.entries[index]
        return Devotional(
            id: index + 1,
            title: entry.title,
            excerpt: entry.excerpt,
            body: entry.body,
            scripture: entry.scriptureReference,
            minutes: entry.durationMinutes,
            prayer: entry.prayer,
            practice: entry.practice
        )
    }

    static func challengeOfTheDay(for date: Date = Date(), bundle: Bundle = .main) -> RecoveryChallenge? {
        guard let document: ChallengeDocument = decode("recovery-challenges", bundle: bundle), !document.entries.isEmpty else { return nil }
        let key = dateKey(date)
        return document.entries
            .filter { $0.startsOn <= key && $0.endsOn >= key }
            .sorted { $0.startsOn > $1.startsOn }
            .first ?? document.entries[rotationIndex(date, count: document.entries.count)]
    }

    static func resourceCounts(bundle: Bundle = .main) -> (verses: Int, devotionals: Int, challenges: Int) {
        let verses: VerseDocument? = decode("daily-verses", bundle: bundle)
        let devotionals: DevotionalDocument? = decode("daily-devotionals", bundle: bundle)
        let challenges: ChallengeDocument? = decode("recovery-challenges", bundle: bundle)
        return (verses?.entries.count ?? 0, devotionals?.entries.count ?? 0, challenges?.entries.count ?? 0)
    }

    private static func decode<T: Decodable>(_ resource: String, bundle: Bundle) -> T? {
        guard let url = bundle.url(forResource: resource, withExtension: "json"), let data = try? Data(contentsOf: url) else { return nil }
        return try? JSONDecoder().decode(T.self, from: data)
    }

    private static func dateKey(_ date: Date) -> String {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter.string(from: date)
    }

    private static func rotationIndex(_ date: Date, count: Int) -> Int {
        let day = Calendar.current.ordinality(of: .day, in: .era, for: date) ?? 1
        return (day - 1) % count
    }
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

struct JournalEntry: Identifiable, Codable, Equatable, Sendable {
    let id: UUID
    let createdAt: Date
    let prompt: String
    let text: String
    let mood: String?
    let updatedAt: Date

    init(id: UUID, createdAt: Date, prompt: String, text: String, mood: String?, updatedAt: Date? = nil) {
        self.id = id
        self.createdAt = createdAt
        self.prompt = prompt
        self.text = text
        self.mood = mood
        self.updatedAt = updatedAt ?? createdAt
    }

    private enum CodingKeys: String, CodingKey { case id, createdAt, prompt, text, mood, updatedAt }

    init(from decoder: Decoder) throws {
        let values = try decoder.container(keyedBy: CodingKeys.self)
        id = try values.decode(UUID.self, forKey: .id)
        createdAt = try values.decode(Date.self, forKey: .createdAt)
        prompt = try values.decode(String.self, forKey: .prompt)
        text = try values.decode(String.self, forKey: .text)
        mood = try values.decodeIfPresent(String.self, forKey: .mood)
        updatedAt = try values.decodeIfPresent(Date.self, forKey: .updatedAt) ?? createdAt
    }
}

struct BibleAnnotation: Identifiable, Codable, Equatable, Sendable {
    var id: String { verseID }
    let verseID: String
    var isFavorite: Bool
    var collections: [String]
    var highlightColor: String?
    var note: String
    var updatedAt: Date
}

struct DailyHistoryRecord: Identifiable, Codable, Equatable, Sendable {
    var id: String { dayKey }
    let dayKey: String
    var verseReference: String
    var verseText: String
    var devotionalTitle: String
    var updatedAt: Date
}

struct BibleVerse: Identifiable, Equatable, Sendable {
    let bookCode: String
    let bookName: String
    let chapter: Int
    let verse: Int
    let text: String

    var id: String { "\(bookCode)-\(chapter)-\(verse)" }
    var reference: String { "\(bookName) \(chapter):\(verse)" }
}

struct BibleChapter: Identifiable, Equatable, Sendable {
    let bookCode: String
    let bookName: String
    let number: Int
    let verses: [BibleVerse]

    var id: String { "\(bookCode)-\(number)" }
    var title: String { "\(bookName) \(number)" }
}

struct BibleBook: Identifiable, Equatable, Sendable {
    let code: String
    let name: String
    let testament: String
    let chapters: [BibleChapter]

    var id: String { code }
    var verseCount: Int { chapters.reduce(0) { $0 + $1.verses.count } }
}

struct BibleLibrary: Equatable, Sendable {
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

struct DailyBreathUserData: Codable, Equatable, Sendable {
    var journalEntries: [JournalEntry]
    var challengeCompletionDayKeys: [String: [String]]
    var bibleAnnotations: [String: BibleAnnotation]
    var dailyHistory: [String: DailyHistoryRecord]
    var deletedJournalEntryIDs: [UUID: Date]
    var modifiedAt: Date

    static let empty = DailyBreathUserData(
        journalEntries: [],
        challengeCompletionDayKeys: [:],
        bibleAnnotations: [:],
        dailyHistory: [:],
        deletedJournalEntryIDs: [:],
        modifiedAt: .distantPast
    )

    init(
        journalEntries: [JournalEntry],
        challengeCompletionDayKeys: [String: [String]],
        bibleAnnotations: [String: BibleAnnotation] = [:],
        dailyHistory: [String: DailyHistoryRecord] = [:],
        deletedJournalEntryIDs: [UUID: Date] = [:],
        modifiedAt: Date = Date()
    ) {
        self.journalEntries = journalEntries
        self.challengeCompletionDayKeys = challengeCompletionDayKeys
        self.bibleAnnotations = bibleAnnotations
        self.dailyHistory = dailyHistory
        self.deletedJournalEntryIDs = deletedJournalEntryIDs
        self.modifiedAt = modifiedAt
    }

    private enum CodingKeys: String, CodingKey {
        case journalEntries, challengeCompletionDayKeys, bibleAnnotations, dailyHistory, deletedJournalEntryIDs, modifiedAt
    }

    init(from decoder: Decoder) throws {
        let values = try decoder.container(keyedBy: CodingKeys.self)
        journalEntries = try values.decodeIfPresent([JournalEntry].self, forKey: .journalEntries) ?? []
        challengeCompletionDayKeys = try values.decodeIfPresent([String: [String]].self, forKey: .challengeCompletionDayKeys) ?? [:]
        bibleAnnotations = try values.decodeIfPresent([String: BibleAnnotation].self, forKey: .bibleAnnotations) ?? [:]
        dailyHistory = try values.decodeIfPresent([String: DailyHistoryRecord].self, forKey: .dailyHistory) ?? [:]
        deletedJournalEntryIDs = try values.decodeIfPresent([UUID: Date].self, forKey: .deletedJournalEntryIDs) ?? [:]
        modifiedAt = try values.decodeIfPresent(Date.self, forKey: .modifiedAt) ?? .distantPast
    }
}

struct DailyBreathUserDataStore: Sendable {
    let fileURL: URL

    static var live: DailyBreathUserDataStore {
        let supportDirectory = FileManager.default.urls(for: .applicationSupportDirectory, in: .userDomainMask).first!
        return DailyBreathUserDataStore(fileURL: supportDirectory.appendingPathComponent("DailyBreath/user-data.json"))
    }

    func load() throws -> DailyBreathUserData {
        guard FileManager.default.fileExists(atPath: fileURL.path) else { return .empty }
        return try Self.makeDecoder().decode(DailyBreathUserData.self, from: Data(contentsOf: fileURL))
    }

    func save(_ userData: DailyBreathUserData) throws {
        let directory = fileURL.deletingLastPathComponent()
        try FileManager.default.createDirectory(
            at: directory,
            withIntermediateDirectories: true,
            attributes: [.protectionKey: FileProtectionType.complete]
        )
        let encoder = Self.makeEncoder()
        encoder.outputFormatting = [.sortedKeys]
        try encoder.encode(userData).write(to: fileURL, options: [.atomic, .completeFileProtection])
    }

    static func makeEncoder() -> JSONEncoder {
        let encoder = JSONEncoder()
        encoder.dateEncodingStrategy = .secondsSince1970
        return encoder
    }

    static func makeDecoder() -> JSONDecoder {
        let decoder = JSONDecoder()
        decoder.dateDecodingStrategy = .custom { value in
            let container = try value.singleValueContainer()
            if let seconds = try? container.decode(Double.self) {
                return Date(timeIntervalSince1970: seconds)
            }
            let string = try container.decode(String.self)
            let formatter = ISO8601DateFormatter()
            formatter.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
            if let date = formatter.date(from: string) { return date }
            formatter.formatOptions = [.withInternetDateTime]
            if let date = formatter.date(from: string) { return date }
            throw DecodingError.dataCorruptedError(in: container, debugDescription: "Invalid date value")
        }
        return decoder
    }
}

enum EncryptedICloudSyncError: LocalizedError {
    case keyUnavailable(OSStatus)
    case encryptionFailed
    case unavailable
    case payloadTooLarge

    var errorDescription: String? {
        switch self {
        case .keyUnavailable: return "The encrypted iCloud key is unavailable. Check iCloud Keychain."
        case .encryptionFailed: return "The encrypted iCloud data could not be opened."
        case .unavailable: return "iCloud data is not available on this device."
        case .payloadTooLarge: return "Your private data is too large for iCloud key-value sync. It remains safely stored on this device."
        }
    }
}

struct EncryptedICloudSyncService {
    private static let payloadKey = "encryptedDailyBreathUserData.v1"
    private static let keyService = "technology.co.beyondimagination.thedailybreath.sync"
    private static let keyAccount = "daily-breath-user-data-key-v1"

    static func upload(_ userData: DailyBreathUserData) throws {
        guard FileManager.default.ubiquityIdentityToken != nil else { throw EncryptedICloudSyncError.unavailable }
        let encoder = DailyBreathUserDataStore.makeEncoder()
        let clearData = try encoder.encode(userData)
        let sealed = try AES.GCM.seal(clearData, using: symmetricKey(createIfMissing: true))
        guard let combined = sealed.combined else { throw EncryptedICloudSyncError.encryptionFailed }
        guard combined.count < 900_000 else { throw EncryptedICloudSyncError.payloadTooLarge }
        let store = NSUbiquitousKeyValueStore.default
        store.set(combined, forKey: payloadKey)
        _ = store.synchronize()
    }

    static func download() throws -> DailyBreathUserData? {
        guard FileManager.default.ubiquityIdentityToken != nil else { throw EncryptedICloudSyncError.unavailable }
        let store = NSUbiquitousKeyValueStore.default
        _ = store.synchronize()
        guard let encrypted = store.data(forKey: payloadKey) else { return nil }
        do {
            let box = try AES.GCM.SealedBox(combined: encrypted)
            let clearData = try AES.GCM.open(box, using: symmetricKey(createIfMissing: false))
            let decoder = DailyBreathUserDataStore.makeDecoder()
            return try decoder.decode(DailyBreathUserData.self, from: clearData)
        } catch let error as EncryptedICloudSyncError {
            throw error
        } catch {
            throw EncryptedICloudSyncError.encryptionFailed
        }
    }

    private static func symmetricKey(createIfMissing: Bool) throws -> SymmetricKey {
        var query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: keyService,
            kSecAttrAccount as String: keyAccount,
            kSecAttrSynchronizable as String: true,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]
        var item: CFTypeRef?
        let lookupStatus = SecItemCopyMatching(query as CFDictionary, &item)
        if lookupStatus == errSecSuccess, let data = item as? Data {
            return SymmetricKey(data: data)
        }
        guard lookupStatus == errSecItemNotFound else {
            throw EncryptedICloudSyncError.keyUnavailable(lookupStatus)
        }
        guard createIfMissing else {
            throw EncryptedICloudSyncError.keyUnavailable(errSecItemNotFound)
        }

        var bytes = [UInt8](repeating: 0, count: 32)
        let randomStatus = bytes.withUnsafeMutableBytes { buffer in
            SecRandomCopyBytes(kSecRandomDefault, buffer.count, buffer.baseAddress!)
        }
        guard randomStatus == errSecSuccess else {
            throw EncryptedICloudSyncError.keyUnavailable(randomStatus)
        }
        let data = Data(bytes)
        query.removeValue(forKey: kSecReturnData as String)
        query.removeValue(forKey: kSecMatchLimit as String)
        query[kSecValueData as String] = data
        query[kSecAttrAccessible as String] = kSecAttrAccessibleAfterFirstUnlock
        let addStatus = SecItemAdd(query as CFDictionary, nil)
        if addStatus == errSecDuplicateItem {
            return try symmetricKey(createIfMissing: false)
        }
        guard addStatus == errSecSuccess else {
            throw EncryptedICloudSyncError.keyUnavailable(addStatus)
        }
        return SymmetricKey(data: data)
    }
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
