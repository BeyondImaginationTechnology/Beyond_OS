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
}
