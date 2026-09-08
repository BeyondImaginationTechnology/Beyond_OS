import XCTest
@testable import BeyondBabyNames

final class NameLibraryTests: XCTestCase {
    func testSearchFindsMeaningAndOrigin() {
        XCTAssertEqual(NameLibrary.search("moon", style: nil, origin: nil, vibes: []).first?.name, "Luna")
        XCTAssertTrue(NameLibrary.search("Swahili", style: nil, origin: nil, vibes: []).map(\.name).contains("Zuri"))
    }

    func testFiltersRespectStyleAndOrigin() {
        let result = NameLibrary.search("", style: .unisex, origin: "Hebrew", vibes: [])
        XCTAssertFalse(result.isEmpty)
        XCTAssertTrue(result.allSatisfy { $0.style == .unisex && $0.origin == "Hebrew" })
    }

    func testRecommendationsPrioritizeSelectedVibe() {
        let picks = NameLibrary.recommendations(vibes: ["Celestial"], favorites: [])
        XCTAssertTrue(picks.prefix(3).allSatisfy { $0.vibe.contains("Celestial") })
    }

    func testTwinPairsUseDifferentNames() {
        let pairs = NameLibrary.twinPairs(from: Array(NameLibrary.all.prefix(8)))
        XCTAssertFalse(pairs.isEmpty)
        XCTAssertTrue(pairs.allSatisfy { $0.first != $0.second })
    }

    func testFamilyNamePreviewBuildsNameInitialsAndFlow() {
        let luna = NameLibrary.all.first { $0.name == "Luna" }!
        XCTAssertEqual(NameLibrary.fullName(for: luna, familyName: " Carter "), "Luna Carter")
        XCTAssertEqual(NameLibrary.initials(for: luna, familyName: "Carter"), "LC")
        XCTAssertEqual(NameLibrary.flowDescription(for: luna, familyName: "Carter"), "Balanced, even rhythm")
    }
}
