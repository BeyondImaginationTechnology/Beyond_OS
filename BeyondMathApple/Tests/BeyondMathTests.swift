import XCTest
@testable import BeyondMath

final class BeyondMathTests: XCTestCase {
    func testNumberSenseHasTenLessonsAndValidGames() {
        XCTAssertEqual(NumberSenseContent.lessons.count, 10)
        for lesson in NumberSenseContent.lessons {
            XCTAssertTrue(lesson.game.choices.contains(lesson.game.answer))
        }
    }
}
