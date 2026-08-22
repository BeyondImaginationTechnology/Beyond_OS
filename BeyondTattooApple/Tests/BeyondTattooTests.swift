import XCTest
import CoreLocation
import Foundation
@testable import BeyondTattoo

final class BeyondTattooTests: XCTestCase {
    func testBundledDailyStencilUsesActualNioAssets() {
        let daily = TattooLibraryManifest.bundled().dailyAsset.dailyDrop
        XCTAssertEqual(daily.title, "Nio Guardians")
        XCTAssertEqual(daily.rewardBits, 25)
        XCTAssertTrue(daily.previewURL.absoluteString.contains("15-nio-guardians"))
        XCTAssertNotNil(daily.transferURL)
    }

    func testVersion12LibraryOnlyExposesAssetBackedDrops() {
        let manifest = TattooLibraryManifest.bundled()
        let collections = manifest.tattooCollections
        XCTAssertEqual(manifest.version, "1.2")
        XCTAssertEqual(manifest.seasonTotal, 55)
        XCTAssertEqual(manifest.assetCount, 2)
        XCTAssertEqual(collections.reduce(0) { $0 + $1.stencils.count }, 2)
        XCTAssertTrue(collections.allSatisfy { !$0.stencils.isEmpty })
        XCTAssertEqual(collections.map(\.dropCount), collections.map { $0.stencils.count })
    }

    func testStencilLibraryIncludesOnlyBundledActualAssets() {
        let stencilNames = TattooLibraryManifest.bundled().assets.map(\.title)
        XCTAssertEqual(Set(stencilNames), Set(["Biblical Realism", "Nio Guardians"]))
    }

    func testStudioDirectoryHasNearestTenCapacity() {
        XCTAssertEqual(StudioLead.seed.count, 19)
        XCTAssertEqual(StudioLead.seed.filter { $0.city == "Ottawa" }.count, 9)
        XCTAssertTrue(StudioLead.seed.contains { $0.city == "Vancouver" })
        XCTAssertTrue(StudioLead.seed.contains { $0.city == "Montréal" })
        XCTAssertTrue(StudioLead.seed.contains { $0.city == "Halifax" })
        XCTAssertTrue(StudioLead.seed.contains { $0.name == "StonerInkk" && $0.isVerified })
    }

    func testSharedStudioDirectoryResponseDecodes() throws {
        let payload = #"{"version":"1.2","studios":[{"slug":"test-ottawa","name":"Test Ottawa","city":"Ottawa","province":"Ontario","address":"1 Test Street, Ottawa, Ontario","services":["Realism","Fine line"],"walk_ins":true,"is_verified":true,"latitude":45.42,"longitude":-75.69,"profile_url":"https://beyondimagination.co.technology/beyond-tattoo/studio-profile.php?slug=test-ottawa"}]}"#.data(using: .utf8)!
        let response = try JSONDecoder().decode(StudioDirectoryResponse.self, from: payload)
        let studio = try XCTUnwrap(response.studios.first?.studioLead)
        XCTAssertEqual(response.version, "1.2")
        XCTAssertEqual(studio.city, "Ottawa")
        XCTAssertTrue(studio.acceptsWalkIns)
        let distance = try XCTUnwrap(studio.distanceKilometres(from: CLLocation(latitude: 45.42, longitude: -75.69)))
        XCTAssertEqual(distance, 0, accuracy: 0.001)
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
        store.userLocation = CLLocation(latitude: 45.4294, longitude: -75.6904)
        XCTAssertEqual(store.nearestStudios.count, 10)
        XCTAssertEqual(store.nearestStudios.first?.name, "StonerInkk")
    }
}
