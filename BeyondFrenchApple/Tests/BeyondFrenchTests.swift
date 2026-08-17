import XCTest
@testable import BeyondFrench

final class BeyondFrenchTests: XCTestCase {
    func testFallbackLessonHasFrenchContent() {
        XCTAssertFalse(FrenchLesson.fallback.french.isEmpty)
        XCTAssertEqual(FrenchLesson.fallback.id, 1)
    }

    func testFallbackAcademyHasFreeGreetingLesson() {
        let module = AcademyCatalog.fallback.modules[0]

        XCTAssertEqual(module.slug, "greetings")
        XCTAssertTrue(module.isFree)
        XCTAssertEqual(module.lessons[0].french, "Bonjour !")
    }

    func testAcademyLessonExperienceChangesByAgeGroup() {
        let lesson = AcademyCatalog.fallback.modules[0].lessons[0]
        let preschool = AgeGroup(slug: "preschool", title: "Preschool", ages: "3-5", icon: "bear", guidance: "")
        let adult = AgeGroup(slug: "adult", title: "Adult", ages: "18+", icon: "cup.and.saucer", guidance: "")

        XCTAssertNotEqual(lesson.experience(for: preschool).practice, lesson.experience(for: adult).practice)
        XCTAssertTrue(lesson.experience(for: preschool).teaching.contains("Grown-up helper"))
    }

    func testAcademyLessonIncludesBeyondLanguageBridge() {
        let lesson = AcademyCatalog.fallback.modules[0].lessons[0]

        XCTAssertEqual(lesson.beyondPhrase.french, "Bonjour !")
        XCTAssertEqual(lesson.beyondPhrase.spanish, "Hola!")
        XCTAssertEqual(lesson.beyondPhrase.kreyol, "Bonjou!")
        XCTAssertEqual(lesson.beyondPhrase.patois, "Wah gwaan!")
    }

    func testThemeChoicesAreAvailable() {
        XCTAssertEqual(AppTheme.allCases.map(\.title), ["Classic", "Ocean", "Sunrise", "Garden"])
    }

    @MainActor
    func testAnswerCheckingIgnoresCasePunctuationAndAccents() {
        let store = AppStore()

        XCTAssertTrue(store.checkAnswer("  tres bien! ", expected: "Très bien"))
    }
}
