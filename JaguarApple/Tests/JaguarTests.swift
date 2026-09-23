import XCTest
@testable import Beyond1

final class JaguarTests: XCTestCase {
    func testChatPayloadUsesServerContract() throws {
        let request = JaguarChatRequest(
            mode: "core",
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
}
