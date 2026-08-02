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

    func googleSignInURL() -> URL {
        var components = URLComponents(url: baseURL.appending(path: "beyond-id/auth/oauth-start.php"), resolvingAgainstBaseURL: false)!
        components.queryItems = [
            URLQueryItem(name: "provider", value: "google"),
            URLQueryItem(name: "return", value: "/beyond-id/auth/mobile-complete.php?scheme=beyondtv")
        ]
        return components.url!
    }

    func session(for token: String) async throws -> BeyondIDSession {
        var components = URLComponents(url: baseURL.appending(path: "beyond-id/api/mobile-session.php"), resolvingAgainstBaseURL: false)!
        components.queryItems = [URLQueryItem(name: "token", value: token)]
        guard let url = components.url else {
            throw APIError.invalidResponse
        }

        var request = URLRequest(url: url)
        request.timeoutInterval = 10
        request.cachePolicy = .reloadIgnoringLocalAndRemoteCacheData
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("BeyondTV-Apple/1.0", forHTTPHeaderField: "User-Agent")

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
