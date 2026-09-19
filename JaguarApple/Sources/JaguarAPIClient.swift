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
        case let .rateLimited(seconds): seconds.map { "Jaguar is resting. Try again in about \($0) seconds." } ?? "Jaguar is resting for a moment. Please try again shortly."
        case let .server(message): message
        case .invalidResponse: "Jaguar returned an unreadable response. Please try again."
        case .connection: "Jaguar could not connect. Check your network and try again."
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
            let message = (try? JSONDecoder().decode(JaguarAPIErrorResponse.self, from: data).error) ?? "Jaguar is temporarily unavailable."
            throw JaguarAPIError.server(message)
        }
        do {
            return try JSONDecoder().decode(JaguarChatResponse.self, from: data)
        } catch {
            throw JaguarAPIError.invalidResponse
        }
    }
}
