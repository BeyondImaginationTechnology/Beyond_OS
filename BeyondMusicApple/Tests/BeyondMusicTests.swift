import XCTest
@testable import BeyondMusic

final class BeyondMusicTests: XCTestCase {
    @MainActor
    func testSeedDownloadsAreAvailableOffline() {
        let store = MusicStore()

        XCTAssertEqual(store.downloadedTracks.count, 2)
        XCTAssertTrue(store.isAvailableOffline(MusicTrack.seed[0]))
    }

    @MainActor
    func testSearchFilterMatchesArtistAndMood() {
        let store = MusicStore()
        store.searchText = "Daily"
        store.selectedMood = .calm

        XCTAssertEqual(store.filteredTracks.map(\.title), ["Neon Devotional"])
    }

    func testCompactCountText() {
        XCTAssertEqual(128.compactCountText, "128")
        XCTAssertEqual(3600.compactCountText, "3.6k")
    }
}
