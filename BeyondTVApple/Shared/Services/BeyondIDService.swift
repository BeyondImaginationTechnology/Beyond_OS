import Foundation

#if os(iOS)
import AuthenticationServices
import UIKit
#endif

enum BeyondIDError: LocalizedError {
    case missingCallbackToken
    case unavailableOnPlatform
    case server(String)

    var errorDescription: String? {
        switch self {
        case .missingCallbackToken:
            "Beyond ID did not return a mobile sign-in token."
        case .unavailableOnPlatform:
            "Google sign-in is available on iPhone and iPad."
        case .server(let message):
            message
        }
    }
}

struct BeyondIDService: Sendable {
    static let production = BeyondIDService(
        baseURL: URL(string: "https://beyondimagination.co.technology")!
    )

    let baseURL: URL
    private let decoder = JSONDecoder()

    func googleSignInURL(codeChallenge: String = "") -> URL {
        var components = URLComponents(url: baseURL.appending(path: "beyond-id/auth/oauth-start.php"), resolvingAgainstBaseURL: false)!
        components.queryItems = [
            URLQueryItem(name: "provider", value: "google"),
            URLQueryItem(name: "return", value: "/beyond-id/auth/mobile-complete.php?scheme=beyondtv&code_challenge=\(codeChallenge)")
        ]
        return components.url!
    }

    func exchange(code: String, verifier: String) async throws -> String {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-token.php"))
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONEncoder().encode(["code": code, "code_verifier": verifier])
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else { throw BeyondIDError.server("Could not complete mobile sign-in.") }
        let payload = try decoder.decode(BeyondTVTokenResponse.self, from: data)
        guard payload.ok, let token = payload.accessToken else { throw BeyondIDError.server("Could not complete mobile sign-in.") }
        return token
    }

    func session(for token: String) async throws -> BeyondIDSession {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-session.php"))
        request.timeoutInterval = 10
        request.cachePolicy = .reloadIgnoringLocalAndRemoteCacheData
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("beyond-tv-ios", forHTTPHeaderField: "X-Beyond-App")
        request.setValue("BeyondTV-Apple/1.1.0", forHTTPHeaderField: "User-Agent")

        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        let session = try decoder.decode(BeyondIDSession.self, from: data)
        guard (200..<300).contains(http.statusCode), session.ok, session.authenticated else {
            throw BeyondIDError.server(session.error ?? "Beyond ID sign-in failed.")
        }
        return session
    }
}

private struct BeyondTVTokenResponse: Decodable { let ok: Bool; let accessToken: String?; enum CodingKeys: String, CodingKey { case ok; case accessToken = "access_token" } }

#if os(iOS)
@MainActor
final class WebAuthPresentationContextProvider: NSObject, ASWebAuthenticationPresentationContextProviding {
    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        let scenes = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }
        return scenes
            .flatMap(\.windows)
            .first { $0.isKeyWindow } ?? ASPresentationAnchor()
    }
}
#endif
