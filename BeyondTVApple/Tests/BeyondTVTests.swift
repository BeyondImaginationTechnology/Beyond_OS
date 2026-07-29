import XCTest
@testable import BeyondTVCore

final class BeyondTVTests: XCTestCase {
    func testMovieChannelUsesMovieScheduleEndpoint() {
        XCTAssertEqual(Channel.preview.endpoint, "/beyond-tv/api/movies-live.php")
    }

    func testAnimeChannelUsesSharedAnimeScheduleEndpoint() {
        let channel = Channel(
            number: 3,
            slug: "yugioh-tv",
            name: "Beyond Anime",
            icon: "⚡",
            description: "Daily anime rotation.",
            category: "Anime"
        )
        XCTAssertEqual(channel.endpoint, "/beyond-tv/api/anime-live.php")
        XCTAssertFalse(channel.isWebPlaybackChannel)
    }

    func testNativePlaybackFiltering() throws {
        let data = Data(#"{"provider":"Internet Archive","title":"Feature","url":"https://example.com/feature.mp4","duration":120}"#.utf8)
        let source = try JSONDecoder().decode(StreamSource.self, from: data)
        XCTAssertTrue(source.isNativelyPlayable)
    }

    func testWebPlaybackURLDecoding() throws {
        let data = Data(
            #"{"ok":true,"state":{"current":{"title":"Live lesson"},"embed_url":"https://www.youtube-nocookie.com/embed/example"},"sources":[],"start_offset":0}"#.utf8
        )
        let response = try JSONDecoder().decode(ScheduleResponse.self, from: data)
        XCTAssertEqual(
            response.webPlaybackLocation,
            "https://www.youtube-nocookie.com/embed/example"
        )
    }
}
