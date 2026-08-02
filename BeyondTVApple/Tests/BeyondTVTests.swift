import XCTest
@testable import BeyondTVCore

final class BeyondTVTests: XCTestCase {
    func testMovieChannelUsesMovieScheduleEndpoint() {
        XCTAssertEqual(Channel.preview.endpoint, "/beyond-tv/api/movies-live.php")
    }

    func testDefaultChannelUsesFeaturedChannelOne() {
        let comedy = Channel(
            number: 5,
            slug: "beyond-comedy",
            name: "Beyond Comedy",
            icon: "😂",
            description: "Comedy channel.",
            category: "Comedy"
        )
        let afterDark = Channel(
            number: 1,
            slug: "beyond-after-dark",
            name: "Beyond After Dark",
            icon: "🌙",
            description: "Supernatural stories.",
            category: "Horror"
        )

        XCTAssertEqual(Channel.defaultChannel(in: [comedy, afterDark])?.slug, "beyond-after-dark")
    }

    func testGuideUsesLightweightScheduleEndpoint() {
        let channel = Channel(
            number: 1,
            slug: "beyond-after-dark",
            name: "Beyond After Dark",
            icon: "🌙",
            description: "Supernatural stories.",
            category: "Horror"
        )

        XCTAssertEqual(channel.guideEndpoint, "/beyond-tv/api/schedule-live.php?slug=beyond-after-dark")
        XCTAssertEqual(channel.embedPath, "/beyond-tv/embed-player.php?slug=beyond-after-dark")
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
        XCTAssertEqual(channel.embedPath, "/beyond-tv/embed-player.php?slug=yugioh-tv")
        XCTAssertTrue(channel.isWebPlaybackChannel)
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

    func testGoogleSignInURLTargetsBeyondTVMobileCompletion() throws {
        let service = BeyondIDService(baseURL: URL(string: "https://example.com")!)
        let components = try XCTUnwrap(URLComponents(url: service.googleSignInURL(), resolvingAgainstBaseURL: false))
        let query = Dictionary(uniqueKeysWithValues: (components.queryItems ?? []).compactMap { item in
            item.value.map { (item.name, $0) }
        })

        XCTAssertEqual(components.path, "/beyond-id/auth/oauth-start.php")
        XCTAssertEqual(query["provider"], "google")
        XCTAssertEqual(query["return"], "/beyond-id/auth/mobile-complete.php?scheme=beyondtv")
    }

    func testBeyondIDSessionDecodesFlexibleProfileValues() throws {
        let data = Data(
            #"{"ok":true,"authenticated":true,"user":{"id":"42","name":"Ari Beyond","email":"ari@example.com","role":"user"},"wallet":{"balance":"12.5","currency":"BITS","status":"active"}}"#.utf8
        )
        let session = try JSONDecoder().decode(BeyondIDSession.self, from: data)

        XCTAssertEqual(session.user?.id, 42)
        XCTAssertEqual(session.user?.preferredName, "Ari Beyond")
        XCTAssertEqual(session.wallet?.balanceText, "12.50 BITS")
    }
}
