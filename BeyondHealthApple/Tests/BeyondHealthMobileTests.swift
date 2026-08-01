import XCTest
@testable import BeyondHealthMobile

final class BeyondHealthMobileTests: XCTestCase {
    @MainActor
    func testEntriesFilterBySelectedMemberAndDay() {
        let store = HealthStore()
        let today = Calendar.current.startOfDay(for: .now)

        store.selectedMemberID = "mia"

        XCTAssertTrue(store.entries(on: today, memberID: "mia").allSatisfy { $0.memberID == "mia" })
        XCTAssertFalse(store.entries(on: today, memberID: "mia").isEmpty)
    }

    @MainActor
    func testAddingFoodPhotoEntryAddsAttachmentAndSelectsEntryDay() {
        let store = HealthStore()
        let originalCount = store.entries.count
        let date = Date.now.addingTimeInterval(-240)

        store.addEntry(category: .food, title: "Lunch", detail: "Rice bowl", attachmentLabel: "Food photo", date: date)

        XCTAssertEqual(store.entries.count, originalCount + 1)
        XCTAssertEqual(store.entries.first?.category, .food)
        XCTAssertEqual(store.entries.first?.attachmentLabel, "Food photo")
        XCTAssertTrue(Calendar.current.isDate(store.selectedDate, inSameDayAs: date))
    }

    @MainActor
    func testChecklistCompletionChangesWhenRoutineToggles() {
        let store = HealthStore()
        store.selectedMemberID = "mia"
        let originalRatio = store.completionRatio
        let incomplete = try XCTUnwrap(store.selectedDayRoutineItems.first { !$0.isComplete })

        store.toggleRoutine(incomplete)

        XCTAssertGreaterThan(store.completionRatio, originalRatio)
    }

    @MainActor
    func testBlankEntryFallsBackToCategoryCopy() {
        let store = HealthStore()

        store.addEntry(category: .sleep, title: " ", detail: " ")

        XCTAssertEqual(store.entries.first?.title, "Sleep")
        XCTAssertEqual(store.entries.first?.detail, "Logged from Today")
    }
}
