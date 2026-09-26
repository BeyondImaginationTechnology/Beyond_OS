import XCTest
@testable import Beyond1

final class JaguarTests: XCTestCase {
    func testChatPayloadUsesServerContract() throws {
        let request = JaguarChatRequest(
            mode: JaguarMode.explain.rawValue,
            language: "en",
            messages: [JaguarWireMessage(role: "user", content: "Hello Jaguar")]
        )
        let data = try JSONEncoder().encode(request)
        let json = try XCTUnwrap(JSONSerialization.jsonObject(with: data) as? [String: Any])
        XCTAssertEqual(json["mode"] as? String, "core")
        XCTAssertEqual(json["language"] as? String, "en")
        let messages = try XCTUnwrap(json["messages"] as? [[String: String]])
        XCTAssertEqual(messages.first?["content"], "Hello Jaguar")
    }

    func testSuccessfulResponseDecodes() throws {
        let json = #"{"model":"jaguar-core-fast-lane","adapter":"local","mode":"core","message":"Hello!"}"#
        let response = try JSONDecoder().decode(JaguarChatResponse.self, from: Data(json.utf8))
        XCTAssertEqual(response.message, "Hello!")
        XCTAssertEqual(response.mode, "core")
    }

    func testConversationRoundTripsLocally() throws {
        let original = JaguarConversation(messages: [JaguarMessage(role: .user, content: "Plan a lesson")])
        let decoded = try JSONDecoder().decode(JaguarConversation.self, from: JSONEncoder().encode(original))
        XCTAssertEqual(decoded, original)
    }

    func testBuildModeUsesPublicServerCatalogKey() throws {
        let request = JaguarChatRequest(
            mode: JaguarMode.build.rawValue,
            language: JaguarLanguage.english.rawValue,
            messages: [JaguarWireMessage(role: "user", content: "Design a feature")]
        )
        let json = try XCTUnwrap(JSONSerialization.jsonObject(with: JSONEncoder().encode(request)) as? [String: Any])
        XCTAssertEqual(json["mode"] as? String, "build")
    }

    func testExistingConversationWithoutModeDefaultsToExplain() throws {
        let original = JaguarConversation(messages: [JaguarMessage(role: .user, content: "Plan a lesson")])
        var json = try XCTUnwrap(JSONSerialization.jsonObject(with: JSONEncoder().encode(original)) as? [String: Any])
        json.removeValue(forKey: "mode")
        let legacyData = try JSONSerialization.data(withJSONObject: json)
        let decoded = try JSONDecoder().decode(JaguarConversation.self, from: legacyData)
        XCTAssertEqual(decoded.mode, .explain)
    }

    func testReviewerDemoReturnsLocalResponse() async {
        let client = JaguarAPIClient(endpoint: URL(string: "https://example.invalid")!)
        let response = await client.sendDemo(
            messages: [JaguarMessage(role: .user, content: "Explain photosynthesis")],
            language: .english
        )
        XCTAssertEqual(response.model, "jaguar-reviewer-demo")
        XCTAssertTrue(response.message.contains("Explain photosynthesis"))
        XCTAssertTrue(response.message.contains("runs locally"))
    }

    func testBuildReviewerDemoClearlyStatesItsLimits() async {
        let client = JaguarAPIClient(endpoint: URL(string: "https://example.invalid")!)
        let response = await client.sendDemo(
            messages: [JaguarMessage(role: .user, content: "Design a feature")],
            language: .english,
            mode: .build
        )
        XCTAssertEqual(response.mode, "build")
        XCTAssertTrue(response.message.contains("does not inspect or modify repositories"))
        XCTAssertTrue(response.message.contains("does not generate images or video"))
        XCTAssertTrue(response.message.contains("does not send your message to a server"))
    }
}
