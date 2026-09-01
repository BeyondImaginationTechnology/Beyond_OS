import Foundation
import XCTest
@testable import DailyBreath

final class DailyBreathTests: XCTestCase {
    func testDailyVerseHasReference() {
        XCTAssertEqual(Verse.daily.reference, "John 8:36")
        XCTAssertEqual(Verse.daily.text, "If therefore the Son makes you free, you will be free indeed.")
    }

    func testTodayResponseDecodesAdminVersePayload() throws {
        let data = """
        {
          "ok": true,
          "date": "2026-08-14",
          "verse": {
            "id": 1,
            "text": "Be still, and know that I am God.",
            "reference": "Psalm 46:10",
            "reflection": "Begin slowly."
          },
          "devotional": {
            "id": 1,
            "title": "Walk in Quiet Confidence",
            "excerpt": "Make room for stillness.",
            "body": "Stillness is not empty time.",
            "scripture": "Psalm 46:10",
            "minutes": 5,
            "prayer": "Lord, quiet my heart.",
            "practice": "Take three slow breaths."
          },
          "challenge": {
            "id": "week-2026-33",
            "title": "Seven Days of Encouragement",
            "description": "Encourage one person each day.",
            "scripture_reference": "Hebrews 10:24-25",
            "steps": ["Choose a person.", "Send encouragement.", "Record the result."],
            "target_count": 7,
            "starts_on": "2026-08-10",
            "ends_on": "2026-08-16"
          }
        }
        """.data(using: .utf8)!

        struct TodayPayload: Decodable {
            let verse: Verse
            let devotional: Devotional
            let challenge: RecoveryChallenge
        }

        let payload = try JSONDecoder().decode(TodayPayload.self, from: data)

        XCTAssertEqual(payload.verse.reference, "Psalm 46:10")
        XCTAssertEqual(payload.devotional.scripture, "Psalm 46:10")
        XCTAssertEqual(payload.challenge.title, "Seven Days of Encouragement")
    }

    func testBibleParserBuildsBooksChaptersAndSearchableVerses() {
        let source = """
        GEN 1:1 In the beginning, God created the heavens and the earth.
        GEN 1:2 The earth was formless and empty.
        JHN 3:16 For God so loved the world, that he gave his one and only Son.
        REV 22:21 The grace of the Lord Jesus Christ be with all the saints. Amen.
        """
        let library = BibleLibrary(translation: "World English Bible", books: BibleLibrary.parse(source))

        XCTAssertEqual(library.books.map(\.name), ["Genesis", "John", "Revelation"])
        XCTAssertEqual(library.chapter(bookCode: "GEN", number: 1)?.verses.count, 2)
        XCTAssertEqual(library.search("loved world").first?.reference, "John 3:16")
    }

    func testBundledBibleIncludesEveryBookAndVerse() {
        let library = BibleLibrary.loadWorldEnglishBible()

        XCTAssertEqual(library.books.count, 66)
        XCTAssertEqual(library.verseCount, 31_103)
        XCTAssertEqual(library.books.first?.name, "Genesis")
        XCTAssertEqual(library.books.last?.name, "Revelation")
    }

    func testBreathPatternIncludesReadableRhythm() {
        let pattern = BreathPattern.dailyPatterns[0]

        XCTAssertEqual(pattern.rhythmText, "Inhale 4 · Hold 4 · Exhale 6")
        XCTAssertFalse(pattern.intention.isEmpty)
    }

    func testBreathOfTheDayRotatesPredictably() {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(secondsFromGMT: 0)!
        let firstDay = DateComponents(calendar: calendar, year: 2026, month: 1, day: 1).date!
        let secondDay = DateComponents(calendar: calendar, year: 2026, month: 1, day: 2).date!

        XCTAssertNotEqual(
            BreathPattern.breathOfTheDay(for: firstDay, calendar: calendar),
            BreathPattern.breathOfTheDay(for: secondDay, calendar: calendar)
        )
    }

    func testRecoveryResourcesHaveExpectedCounts() {
        let counts = RecoveryContent.resourceCounts()
        XCTAssertEqual(counts.verses, 138)
        XCTAssertEqual(counts.devotionals, 138)
        XCTAssertEqual(counts.challenges, 20)
    }

    func testRecoveryScheduleStartsAugustSixteenth() {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(secondsFromGMT: 0)!
        let date = DateComponents(calendar: calendar, year: 2026, month: 8, day: 16, hour: 12).date!

        XCTAssertEqual(RecoveryContent.verseOfTheDay(for: date)?.reference, "Psalm 3:3")
        XCTAssertEqual(RecoveryContent.devotionalOfTheDay(for: date)?.scripture, "Psalm 3:3")
        XCTAssertEqual(RecoveryContent.challengeOfTheDay(for: date)?.title, "Build Your Support Circle")
    }

    func testCompleteQuranLibraryHasAllSurahsAndVerses() {
        let quran = SacredTextLibrary.loadPickthallQuran()

        XCTAssertEqual(quran.books.count, 114)
        XCTAssertEqual(quran.chapterCount, 114)
        XCTAssertEqual(quran.verseCount, 6_236)
        XCTAssertEqual(quran.books.first?.chapters.first?.verses.first?.text, "In the name of Allah, the Beneficent, the Merciful.")
    }

    func testFaithTraditionsUseConsistentRecommendedThemes() {
        XCTAssertEqual(DailyBreathTheme.recommended(for: .bible), .forest)
        XCTAssertEqual(DailyBreathTheme.recommended(for: .torah), .torahLight)
        XCTAssertEqual(DailyBreathTheme.recommended(for: .quran), .quranMoon)
    }

    func testTorahEditionIncludesCompleteHebrewScriptures() {
        let bible = BibleLibrary.loadWorldEnglishBible()
        let torah = SacredTextLibrary.torah(from: bible)

        XCTAssertEqual(torah.books.count, 39)
        XCTAssertEqual(torah.books.first?.name, "Bereshit")
        XCTAssertEqual(torah.books.last?.name, "Malakhi")
        XCTAssertNotNil(torah.books.first { $0.name == "Tehillim" })
    }

    func testAugustTwentyFourthInterfaithVersesMatchCourageTheme() {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(secondsFromGMT: 0)!
        let date = DateComponents(calendar: calendar, year: 2026, month: 8, day: 24, hour: 12).date!
        let bibleLibrary = BibleLibrary.loadWorldEnglishBible()
        let bible = SacredTextLibrary.bible(from: bibleLibrary)
        let torah = SacredTextLibrary.torah(from: bibleLibrary)
        let quran = SacredTextLibrary.loadPickthallQuran()
        let daily = RecoveryContent.verseOfTheDay(for: date)!

        let jewishVerse = InterfaithDailyContent.verse(for: .torah, date: date, bibleVerse: daily, bible: bible, torah: torah, quran: quran)
        let muslimVerse = InterfaithDailyContent.verse(for: .quran, date: date, bibleVerse: daily, bible: bible, torah: torah, quran: quran)

        XCTAssertEqual(jewishVerse.reference, "Tehillim 27:14")
        XCTAssertEqual(muslimVerse.reference, "Al-Imran 3:200")
    }

    func testTorahResolverMatchesSingularPsalmReference() {
        let bibleLibrary = BibleLibrary.loadWorldEnglishBible()
        let torah = SacredTextLibrary.torah(from: bibleLibrary)
        let verse = Verse(
            id: 1,
            text: "Wait for Yahweh. Be strong, and let your heart take courage.",
            reference: "Psalm 27:14",
            reflection: "Courage"
        )

        let resolved = InterfaithDailyContent.verse(
            for: .torah,
            date: Date(timeIntervalSince1970: 0),
            bibleVerse: verse,
            bible: SacredTextLibrary.bible(from: bibleLibrary),
            torah: torah,
            quran: SacredTextLibrary(tradition: .quran, translation: "Test", books: [])
        )

        XCTAssertEqual(resolved.reference, "Tehillim 27:14")
        XCTAssertEqual(resolved.text, torah.verse(bookCode: "PSA", chapter: 27, verse: 14)?.text)
    }

    func testTorahFallbackUsesJewishBookNameBeforeLibraryLoads() {
        let verse = Verse(
            id: 1,
            text: "You shall make no covenant with them, nor with their gods.",
            reference: "Exodus 23:32",
            reflection: "Choose the next faithful step."
        )
        let emptyLibrary = SacredTextLibrary(tradition: .torah, translation: "Loading", books: [])

        let resolved = InterfaithDailyContent.verse(
            for: .torah,
            date: Date(timeIntervalSince1970: 0),
            bibleVerse: verse,
            bible: SacredTextLibrary(tradition: .bible, translation: "Loading", books: []),
            torah: emptyLibrary,
            quran: SacredTextLibrary(tradition: .quran, translation: "Loading", books: [])
        )

        XCTAssertEqual(resolved.reference, "Shemot 23:32")
        XCTAssertEqual(resolved.text, verse.text)
    }

    func testQuranFallbackNeverDisplaysChristianDailyVerseWhileLibraryLoads() {
        let emptyLibrary = SacredTextLibrary(tradition: .quran, translation: "Loading", books: [])
        let resolved = InterfaithDailyContent.verse(
            for: .quran,
            date: Date(timeIntervalSince1970: 0),
            bibleVerse: .daily,
            bible: SacredTextLibrary(tradition: .bible, translation: "Loading", books: []),
            torah: SacredTextLibrary(tradition: .torah, translation: "Loading", books: []),
            quran: emptyLibrary
        )

        XCTAssertEqual(resolved.reference, "Ar-Ra'd 13:28")
        XCTAssertTrue(resolved.text.localizedCaseInsensitiveContains("Allah"))
        XCTAssertFalse(resolved.text.localizedCaseInsensitiveContains("the Son"))
    }

    func testTanakhFallbackNeverDisplaysNewTestamentVerseWhileLibraryLoads() {
        let resolved = InterfaithDailyContent.verse(
            for: .torah,
            date: Date(timeIntervalSince1970: 0),
            bibleVerse: .daily,
            bible: SacredTextLibrary(tradition: .bible, translation: "Loading", books: []),
            torah: SacredTextLibrary(tradition: .torah, translation: "Loading", books: []),
            quran: SacredTextLibrary(tradition: .quran, translation: "Loading", books: [])
        )

        XCTAssertEqual(resolved.reference, "Tehillim 46:10")
        XCTAssertFalse(resolved.text.localizedCaseInsensitiveContains("the Son"))
    }

    func testICloudMergeIsStableWhenContentAlreadyMatches() {
        let timestamp = Date(timeIntervalSince1970: 100)
        let data = DailyBreathUserData(
            journalEntries: [],
            challengeCompletionDayKeys: ["challenge": ["2026-09-01"]],
            modifiedAt: timestamp
        )

        let merged = DailyBreathStore.mergeUserData(data, with: data)

        XCTAssertEqual(merged.modifiedAt, timestamp)
        XCTAssertTrue(DailyBreathStore.hasSameUserContent(merged, as: data))
    }

    func testScriptureEditionsUseTraditionAppropriateDefaults() {
        XCTAssertEqual(ScriptureEdition.defaultEdition(for: .bible), .bibleEnglish)
        XCTAssertEqual(ScriptureEdition.defaultEdition(for: .torah), .torahHebrew)
        XCTAssertEqual(ScriptureEdition.defaultEdition(for: .quran), .quranArabic)
        XCTAssertEqual(ScriptureEdition.options(for: .bible), [.bibleEnglish, .bibleFrench, .bibleSpanish])
        XCTAssertEqual(ScriptureEdition.options(for: .torah), [.torahHebrew, .torahEnglish, .torahFrench])
        XCTAssertEqual(ScriptureEdition.options(for: .quran), [.quranArabic, .quranEnglish])
    }

    func testBundledNativeAndTranslatedEditionsLoadOffline() {
        let hebrew = SacredTextLibrary.load(.torahHebrew)
        let frenchBible = SacredTextLibrary.load(.bibleFrench)
        let spanishBible = SacredTextLibrary.load(.bibleSpanish)
        let arabicQuran = SacredTextLibrary.load(.quranArabic)

        XCTAssertEqual(hebrew.books.count, 39)
        XCTAssertEqual(hebrew.books.first { $0.code == "PSA" }?.name, "תהילים · Tehillim")
        XCTAssertTrue(hebrew.verse(bookCode: "PSA", chapter: 23, verse: 1)?.text.contains("יהוה") == true)
        XCTAssertEqual(frenchBible.books.count, 66)
        XCTAssertEqual(frenchBible.books.first?.name, "Genèse")
        XCTAssertEqual(spanishBible.books.count, 66)
        XCTAssertEqual(spanishBible.books.first?.name, "Génesis")
        XCTAssertEqual(arabicQuran.verseCount, 6_236)
        XCTAssertEqual(arabicQuran.books.first?.name, "الفاتحة")
    }

    func testFaithAwareDevotionalsUseSelectedReferenceAndPrayerLanguage() {
        let base = Devotional(
            id: 1,
            title: "Christian Base",
            excerpt: "Base excerpt",
            body: "Base body",
            scripture: "Psalm 27:14",
            minutes: 3,
            prayer: "Lord, help me.",
            practice: "Base practice"
        )
        let torahVerse = Verse(id: 2, text: "Be strong.", reference: "Psalms 27:14", reflection: "Courage")
        let quranVerse = Verse(id: 3, text: "Persevere.", reference: "Al-Imran 3:200", reflection: "Courage")

        let jewish = InterfaithDailyContent.devotional(for: .torah, base: base, verse: torahVerse)
        let muslim = InterfaithDailyContent.devotional(for: .quran, base: base, verse: quranVerse)

        XCTAssertEqual(jewish.scripture, torahVerse.reference)
        XCTAssertTrue(jewish.practice.contains(torahVerse.reference))
        XCTAssertFalse(jewish.prayer.contains("Lord"))
        XCTAssertEqual(muslim.scripture, quranVerse.reference)
        XCTAssertTrue(muslim.practice.contains(quranVerse.reference))
        XCTAssertTrue(muslim.prayer.contains("Allah"))
        XCTAssertFalse(muslim.prayer.contains("Lord"))
    }

    func testFaithAwareChallengeUsesSelectedReading() {
        let base = RecoveryChallenge(
            id: "challenge",
            title: "Morning Before Messages",
            description: "Begin grounded.",
            scriptureReference: "Ephesians 4:25",
            steps: ["Read one recovery verse.", "End with a brief prayer."],
            targetCount: 7,
            startsOn: "2026-08-01",
            endsOn: "2026-08-31"
        )
        let ayah = Verse(id: 1, text: "Remember Allah.", reference: "Ar-Ra'd 13:28", reflection: "Peace")

        let muslim = InterfaithDailyContent.challenge(for: .quran, base: base, verse: ayah)

        XCTAssertEqual(muslim.id, base.id)
        XCTAssertEqual(muslim.scriptureReference, ayah.reference)
        XCTAssertTrue(muslim.steps.contains { $0.localizedCaseInsensitiveContains("daily ayah") })
        XCTAssertFalse(muslim.steps.contains { $0.localizedCaseInsensitiveContains("prayer") })
    }

    func testReviewDateUsesScheduledVerseInsteadOfHardcodedFallback() {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(secondsFromGMT: 0)!
        let date = DateComponents(calendar: calendar, year: 2026, month: 8, day: 22, hour: 12).date!

        let verse = RecoveryContent.verseOfTheDay(for: date)

        XCTAssertEqual(verse?.reference, "Psalm 23:4")
        XCTAssertEqual(verse?.reflection, "Let this recovery verse guide your next faithful step.")
        XCTAssertFalse(verse?.reflection.contains("entry.theme") ?? true)
        XCTAssertNotEqual(verse, Verse.daily)
    }

    func testScheduledVerseCannotBeOverwrittenByRemoteCovenantFallback() {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(secondsFromGMT: 0)!
        let date = DateComponents(calendar: calendar, year: 2026, month: 8, day: 24, hour: 12).date!
        let remoteFallback = Verse(
            id: 999,
            text: "A generic covenant fallback.",
            reference: "Covenant 1:1",
            reflection: "Let this entry.theme verse guide your day."
        )

        let resolved = RecoveryContent.resolvedVerseOfTheDay(for: date, remoteVerse: remoteFallback)

        XCTAssertEqual(resolved.reference, "Psalm 27:14")
        XCTAssertNotEqual(resolved.text, remoteFallback.text)
        XCTAssertEqual(resolved.reflection, RecoveryContent.recoveryVerseReflection)
        XCTAssertFalse(resolved.reflection.contains("entry.theme"))
    }

    func testMatchingRemoteVerseCannotOverwriteScheduledContent() {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(secondsFromGMT: 0)!
        let date = DateComponents(calendar: calendar, year: 2026, month: 8, day: 24, hour: 12).date!
        let scheduled = RecoveryContent.verseOfTheDay(for: date)!
        let matchingRemote = Verse(
            id: 500,
            text: scheduled.text,
            reference: scheduled.reference,
            reflection: "Let this entry.theme verse guide your day."
        )

        let resolved = RecoveryContent.resolvedVerseOfTheDay(for: date, remoteVerse: matchingRemote)

        XCTAssertEqual(resolved.id, scheduled.id)
        XCTAssertEqual(resolved.text, scheduled.text)
        XCTAssertEqual(resolved.reflection, RecoveryContent.recoveryVerseReflection)
    }

    func testUserDataStorePersistsJournalAndChallengeProgress() throws {
        let directory = FileManager.default.temporaryDirectory.appendingPathComponent(UUID().uuidString)
        let store = DailyBreathUserDataStore(fileURL: directory.appendingPathComponent("user-data.json"))
        addTeardownBlock { try? FileManager.default.removeItem(at: directory) }
        let entry = JournalEntry(
            id: UUID(),
            createdAt: Date(timeIntervalSince1970: 1_786_838_400),
            prompt: "What is one faithful next step?",
            text: "Ask for support.",
            mood: "Hopeful"
        )
        let expected = DailyBreathUserData(
            journalEntries: [entry],
            challengeCompletionDayKeys: ["challenge-01": ["2026-08-22"]],
            bibleAnnotations: [
                "JHN-3-16": BibleAnnotation(
                    verseID: "JHN-3-16",
                    isFavorite: true,
                    collections: ["Hope"],
                    highlightColor: "yellow",
                    note: "Carry this today.",
                    updatedAt: Date(timeIntervalSince1970: 1_786_838_400)
                )
            ],
            dailyHistory: [
                "2026-08-22": DailyHistoryRecord(
                    dayKey: "2026-08-22",
                    verseReference: "Psalm 23:4",
                    verseText: "I will fear no evil.",
                    devotionalTitle: "Walk With Courage",
                    updatedAt: Date(timeIntervalSince1970: 1_786_838_400)
                )
            ],
            modifiedAt: Date(timeIntervalSince1970: 1_786_838_400)
        )

        try store.save(expected)

        XCTAssertEqual(try store.load(), expected)
    }

    func testUserDataDecoderMigratesVersionOneLocalFile() throws {
        let legacy = Data("""
        {
          "journalEntries": [],
          "challengeCompletionDayKeys": {"challenge-01": ["2026-08-22"]}
        }
        """.utf8)

        let decoded = try DailyBreathUserDataStore.makeDecoder().decode(DailyBreathUserData.self, from: legacy)

        XCTAssertEqual(decoded.challengeCompletionDayKeys["challenge-01"], ["2026-08-22"])
        XCTAssertTrue(decoded.bibleAnnotations.isEmpty)
        XCTAssertTrue(decoded.dailyHistory.isEmpty)
        XCTAssertEqual(decoded.modifiedAt, .distantPast)
    }

    func testEncryptedSyncMergeHonorsEditsAndDeletionTombstones() {
        let first = Date(timeIntervalSince1970: 100)
        let second = Date(timeIntervalSince1970: 200)
        let third = Date(timeIntervalSince1970: 300)
        let journalID = UUID()
        let local = DailyBreathUserData(
            journalEntries: [JournalEntry(id: journalID, createdAt: first, prompt: "Prompt", text: "Local", mood: nil, updatedAt: first)],
            challengeCompletionDayKeys: ["challenge": ["2026-08-21"]],
            bibleAnnotations: [
                "JHN-3-16": BibleAnnotation(verseID: "JHN-3-16", isFavorite: true, collections: ["Hope"], highlightColor: "yellow", note: "Local", updatedAt: first)
            ],
            modifiedAt: first
        )
        let cloud = DailyBreathUserData(
            journalEntries: [JournalEntry(id: journalID, createdAt: first, prompt: "Prompt", text: "Cloud edit", mood: nil, updatedAt: second)],
            challengeCompletionDayKeys: ["challenge": ["2026-08-22"]],
            bibleAnnotations: [
                "JHN-3-16": BibleAnnotation(verseID: "JHN-3-16", isFavorite: false, collections: [], highlightColor: nil, note: "", updatedAt: second)
            ],
            deletedJournalEntryIDs: [journalID: third],
            modifiedAt: third
        )

        let merged = DailyBreathStore.mergeUserData(local, with: cloud)

        XCTAssertTrue(merged.journalEntries.isEmpty)
        XCTAssertEqual(merged.challengeCompletionDayKeys["challenge"], ["2026-08-21", "2026-08-22"])
        XCTAssertEqual(merged.bibleAnnotations["JHN-3-16"]?.isFavorite, false)
        XCTAssertEqual(merged.deletedJournalEntryIDs[journalID], third)
    }

    func testTodayClientRejectsMalformedPayload() async {
        let client = makeClient { request in
            (try Self.response(for: request), Data("{".utf8))
        }

        do {
            _ = try await client.fetch(dateKey: "2026-08-22")
            XCTFail("Malformed JSON should not decode.")
        } catch is DecodingError {
            // Expected.
        } catch {
            XCTFail("Expected DecodingError, received \(error).")
        }
    }

    func testTodayClientRejectsStaleDate() async {
        let client = makeClient { request in
            (try Self.response(for: request), Self.todayPayload(date: "2026-08-21"))
        }

        do {
            _ = try await client.fetch(dateKey: "2026-08-22")
            XCTFail("A stale daily response should not be accepted.")
        } catch let error as DailyBreathAPIError {
            XCTAssertEqual(error, .staleDate(expected: "2026-08-22", received: "2026-08-21"))
        } catch {
            XCTFail("Expected staleDate, received \(error).")
        }
    }

    func testTodayClientSurfacesOfflineFailure() async {
        let client = makeClient { _ in throw URLError(.notConnectedToInternet) }

        do {
            _ = try await client.fetch(dateKey: "2026-08-22")
            XCTFail("An offline request should fail.")
        } catch let error as URLError {
            XCTAssertEqual(error.code, .notConnectedToInternet)
        } catch {
            XCTFail("Expected notConnectedToInternet, received \(error).")
        }
    }

    func testTodayClientUsesExplicitTimeoutAndSurfacesSlowFailure() async {
        let client = makeClient(timeout: 0.25) { request in
            XCTAssertEqual(request.timeoutInterval, 0.25, accuracy: 0.001)
            throw URLError(.timedOut)
        }

        do {
            _ = try await client.fetch(dateKey: "2026-08-22")
            XCTFail("A timed-out request should fail.")
        } catch let error as URLError {
            XCTAssertEqual(error.code, .timedOut)
        } catch {
            XCTFail("Expected timedOut, received \(error).")
        }
    }

    private func makeClient(
        timeout: TimeInterval = 10,
        handler: @escaping (URLRequest) throws -> (HTTPURLResponse, Data)
    ) -> DailyBreathAPIClient {
        MockURLProtocol.handler = handler
        let configuration = URLSessionConfiguration.ephemeral
        configuration.protocolClasses = [MockURLProtocol.self]
        return DailyBreathAPIClient(
            endpoint: URL(string: "https://example.com/today.php")!,
            session: URLSession(configuration: configuration),
            timeoutInterval: timeout
        )
    }

    private static func response(for request: URLRequest) throws -> HTTPURLResponse {
        try XCTUnwrap(HTTPURLResponse(url: request.url!, statusCode: 200, httpVersion: nil, headerFields: nil))
    }

    private static func todayPayload(date: String) -> Data {
        Data("""
        {
          "date": "\(date)",
          "verse": {
            "id": 1,
            "text": "Be still, and know that I am God.",
            "reference": "Psalm 46:10",
            "reflection": "Begin slowly."
          },
          "devotional": {
            "id": 1,
            "title": "Walk in Quiet Confidence",
            "excerpt": "Make room for stillness.",
            "body": "Stillness is not empty time.",
            "scripture": "Psalm 46:10",
            "minutes": 5,
            "prayer": "Lord, quiet my heart.",
            "practice": "Take three slow breaths."
          },
          "challenge": null
        }
        """.utf8)
    }
}

private final class MockURLProtocol: URLProtocol {
    nonisolated(unsafe) static var handler: ((URLRequest) throws -> (HTTPURLResponse, Data))?

    override class func canInit(with request: URLRequest) -> Bool { true }

    override class func canonicalRequest(for request: URLRequest) -> URLRequest { request }

    override func startLoading() {
        guard let handler = Self.handler else {
            client?.urlProtocol(self, didFailWithError: URLError(.unknown))
            return
        }
        do {
            let (response, data) = try handler(request)
            client?.urlProtocol(self, didReceive: response, cacheStoragePolicy: .notAllowed)
            client?.urlProtocol(self, didLoad: data)
            client?.urlProtocolDidFinishLoading(self)
        } catch {
            client?.urlProtocol(self, didFailWithError: error)
        }
    }

    override func stopLoading() {}
}
