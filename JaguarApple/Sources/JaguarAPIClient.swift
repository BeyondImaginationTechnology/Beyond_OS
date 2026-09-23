import Foundation

enum JaguarAPIError: LocalizedError, Equatable {
    case authenticationExpired
    case rateLimited(retryAfter: Int?)
    case server(String)
    case invalidResponse
    case connection

    var errorDescription: String? {
        switch self {
        case .authenticationExpired: "Your Beyond ID session expired. Sign in again to continue."
        case let .rateLimited(seconds): seconds.map { "Beyond-1 is resting. Try again in about \($0) seconds." } ?? "Beyond-1 is resting for a moment. Please try again shortly."
        case let .server(message): message
        case .invalidResponse: "Beyond-1 returned an unreadable response. Please try again."
        case .connection: "Beyond-1 could not connect. Check your network and try again."
        }
    }
}

struct JaguarAPIClient: Sendable {
    static let live = JaguarAPIClient(endpoint: URL(string: "https://beyondimagination.co.technology/ai/api/chat.php")!)
    let endpoint: URL

    func send(messages: [JaguarMessage], language: JaguarLanguage, accessToken: String) async throws -> JaguarChatResponse {
        let payload = JaguarChatRequest(
            mode: "core",
            language: language.rawValue,
            messages: messages.suffix(24).map { JaguarWireMessage(role: $0.role.rawValue, content: $0.content) }
        )
        var request = URLRequest(url: endpoint)
        request.httpMethod = "POST"
        request.timeoutInterval = 45
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("Bearer \(accessToken)", forHTTPHeaderField: "Authorization")
        request.setValue("ios", forHTTPHeaderField: "X-Jaguar-Client")
        request.httpBody = try JSONEncoder().encode(payload)

        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await URLSession.shared.data(for: request)
        } catch {
            throw JaguarAPIError.connection
        }
        guard let http = response as? HTTPURLResponse else { throw JaguarAPIError.invalidResponse }
        if http.statusCode == 401 { throw JaguarAPIError.authenticationExpired }
        if http.statusCode == 429 {
            throw JaguarAPIError.rateLimited(retryAfter: http.value(forHTTPHeaderField: "Retry-After").flatMap(Int.init))
        }
        guard (200..<300).contains(http.statusCode) else {
            let message = (try? JSONDecoder().decode(JaguarAPIErrorResponse.self, from: data).error) ?? "Beyond-1 is temporarily unavailable."
            throw JaguarAPIError.server(message)
        }
        do {
            return try JSONDecoder().decode(JaguarChatResponse.self, from: data)
        } catch {
            throw JaguarAPIError.invalidResponse
        }
    }

    func sendDemo(messages: [JaguarMessage], language: JaguarLanguage) async -> JaguarChatResponse {
        let latest = messages.last(where: { $0.role == .user })?.content ?? "your question"
        let languageNote: String
        switch language {
        case .english: languageNote = "This reviewer demo runs locally and does not send your message to a server."
        case .french: languageNote = "Cette démonstration fonctionne localement et n’envoie pas votre message à un serveur."
        case .spanish: languageNote = "Esta demostración funciona localmente y no envía tu mensaje a un servidor."
        }
        return JaguarChatResponse(
            model: "jaguar-reviewer-demo",
            adapter: "local",
            mode: "core",
            message: "Demo response for: \"\(latest)\"\n\nBeyond-1 can explain an idea, shape a plan, and break down a difficult topic into plain language. Try changing the language menu, starting a new conversation, and reopening this conversation from the list.\n\n\(languageNote)"
        )
    }
}
