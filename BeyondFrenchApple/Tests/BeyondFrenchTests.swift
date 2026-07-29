import XCTest
@testable import BeyondFrench

final class BeyondFrenchTests: XCTestCase {
    func testFallbackLessonHasFrenchContent() {
        XCTAssertFalse(FrenchLesson.fallback.french.isEmpty)
        XCTAssertEqual(FrenchLesson.fallback.id, 1)
    }
}
