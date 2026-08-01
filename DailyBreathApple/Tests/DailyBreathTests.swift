import XCTest
@testable import DailyBreath

final class DailyBreathTests: XCTestCase {
    func testDailyVerseHasReference() {
        XCTAssertEqual(Verse.daily.reference, "Psalm 46:10")
        XCTAssertFalse(Verse.daily.text.isEmpty)
    }

    func testAcademyHasFreeStarterModule() async {
        let store = await DailyBreathStore()
        let hasFreeModule = await store.modules.contains { $0.isFree }
        XCTAssertTrue(hasFreeModule)
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
}
