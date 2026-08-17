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
          }
        }
        """.data(using: .utf8)!

        struct TodayPayload: Decodable {
            let verse: Verse
            let devotional: Devotional
        }

        let payload = try JSONDecoder().decode(TodayPayload.self, from: data)

        XCTAssertEqual(payload.verse.reference, "Psalm 46:10")
        XCTAssertEqual(payload.devotional.scripture, "Psalm 46:10")
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
}
