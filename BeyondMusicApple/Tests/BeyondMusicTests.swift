import XCTest
@testable import BeyondMusic

final class BeyondMusicTests: XCTestCase {
    @MainActor
    func testLibraryStartsWithoutFakeTracks() {
        let store = MusicStore()

        XCTAssertEqual(store.tracks.count, 0)
        XCTAssertEqual(store.downloadedTracks.count, 0)
        XCTAssertNil(store.currentTrack)
    }

    @MainActor
    func testLocalPlaylistsReflectActualLibrary() {
        let store = MusicStore()

        XCTAssertEqual(store.playlists.map(\.title), ["Downloaded", "Recently Added"])
        XCTAssertTrue(store.playlists.allSatisfy { $0.tracks.isEmpty })
    }

    func testCompactCountText() {
        XCTAssertEqual(128.compactCountText, "128")
        XCTAssertEqual(3600.compactCountText, "3.6k")
    }
}
