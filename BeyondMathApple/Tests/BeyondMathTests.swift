import XCTest
@testable import BeyondMath

final class BeyondMathTests: XCTestCase {
    func testCourseCatalogHasValidLessonsAndGames() {
        XCTAssertGreaterThanOrEqual(BeyondMathContent.courses.count, 4)
        for course in BeyondMathContent.courses {
            XCTAssertFalse(course.lessons.isEmpty)
            XCTAssertGreaterThanOrEqual(course.progress, 0)
            XCTAssertLessThanOrEqual(course.progress, 1)
        }
        for lesson in BeyondMathContent.courses.flatMap(\.lessons) {
            XCTAssertTrue(lesson.game.choices.contains(lesson.game.answer))
        }
    }

    func testScannerDemoShowsSolvedEquationPath() {
        XCTAssertEqual(BeyondMathContent.scannerSteps.last?.expression, "x = 5")
    }
}
