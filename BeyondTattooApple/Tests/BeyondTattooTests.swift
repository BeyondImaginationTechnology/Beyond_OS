import XCTest
import CoreLocation
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
        XCTAssertEqual(collections.reduce(0) { $0 + $1.stencils.count }, 55)
        XCTAssertTrue(collections.allSatisfy { !$0.stencils.isEmpty })
        XCTAssertEqual(collections.map(\.dropCount), collections.map { $0.stencils.count })
    }

    func testStencilLibraryIncludesKnownWebCatalogItems() {
        let stencilNames = TattooCollection.seed.flatMap(\.stencils).map(\.name)
        XCTAssertTrue(stencilNames.contains("Praying Hands & Rosary"))
        XCTAssertTrue(stencilNames.contains("Ornamental Egyptian Frame"))
        XCTAssertTrue(stencilNames.contains("Mythical Guardian"))
        XCTAssertTrue(stencilNames.contains("Final Judgment"))
    }

    func testStudioDirectoryHasNearestTenCapacity() {
        XCTAssertGreaterThanOrEqual(StudioLead.seed.count, 10)
        XCTAssertTrue(StudioLead.seed.contains { $0.name == "StonerInkk" && $0.isVerified })
    }

    @MainActor
    func testSavingDailyStencilCanBeToggled() {
        let store = TattooStore()
        XCTAssertTrue(store.savedStencilIDs.contains(store.dailyDrop.id))
        store.toggleSaved(store.dailyDrop)
        XCTAssertFalse(store.savedStencilIDs.contains(store.dailyDrop.id))
    }

    @MainActor
    func testNearestStudiosLimitsToTen() {
        let store = TattooStore()
        store.userLocation = CLLocation(latitude: 45.4215, longitude: -75.6972)
        XCTAssertEqual(store.nearestStudios.count, 10)
        XCTAssertEqual(store.nearestStudios.first?.name, "StonerInkk")
    }
}
