import XCTest
@testable import BeyondTattoo

final class BeyondTattooTests: XCTestCase {
    func testDailyStencilKeepsExistingReleaseDetails() {
        XCTAssertEqual(StencilDrop.daily.title, "Eye of Horus Anubis")
        XCTAssertEqual(StencilDrop.daily.rewardBits, 25)
        XCTAssertTrue(StencilDrop.daily.packageURL.absoluteString.contains("stencil-download.php"))
    }

    func testSeedCollectionsExposeScheduledDrops() {
        let collections = TattooCollection.seed
        XCTAssertEqual(collections.count, 4)
        XCTAssertTrue(collections.allSatisfy { !$0.stencils.isEmpty })
    }

    @MainActor
    func testSavingDailyStencilCanBeToggled() {
        let store = TattooStore()
        XCTAssertTrue(store.savedStencilIDs.contains(store.dailyDrop.id))
        store.toggleSaved(store.dailyDrop)
        XCTAssertFalse(store.savedStencilIDs.contains(store.dailyDrop.id))
    }
}
