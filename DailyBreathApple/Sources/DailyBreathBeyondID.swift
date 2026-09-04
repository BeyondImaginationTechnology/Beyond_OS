import AuthenticationServices
import CryptoKit
import Foundation
import Security
import UIKit

struct DailyBreathBeyondIDAccount: Decodable, Equatable {
    let name: String?
    let firstName: String?
    let displayNameValue: String?
    let email: String

    enum CodingKeys: String, CodingKey {
        case name, email
        case firstName = "first_name"
        case displayNameValue = "display_name"
    }

    var displayName: String {
        [displayNameValue, name, firstName, email]
            .compactMap { $0?.trimmingCharacters(in: .whitespacesAndNewlines) }
            .first(where: { !$0.isEmpty }) ?? email
    }
}

@MainActor
final class DailyBreathBeyondIDSession: ObservableObject {
    @Published private(set) var account: DailyBreathBeyondIDAccount?
    @Published private(set) var statusMessage = "Sign in to connect DailyBreath with your Beyond ID."
    @Published private(set) var isWorking = false

    private let service = DailyBreathBeyondIDService()
    private let webAuthenticator = DailyBreathWebAuthenticator()

    init() {
        Task { await restoreSession() }
    }

    func signIn() async {
        guard !isWorking else { return }
        isWorking = true
        statusMessage = "Opening Beyond ID…"
        defer { isWorking = false }

        do {
            let verifier = DailyBreathPKCE.makeVerifier()
            let callbackURL = try await webAuthenticator.authenticate(url: service.signInURL(codeChallenge: DailyBreathPKCE.challenge(for: verifier)), callbackScheme: "dailybreath")
            let code = try service.code(from: callbackURL)
            let token = try await service.exchange(code: code, verifier: verifier)
            let account = try await service.account(for: token)
            try DailyBreathBeyondIDKeychain.save(token)
            self.account = account
            statusMessage = "Signed in as \(account.displayName). Your reflections remain private on this device unless you enable encrypted iCloud sync."
        } catch {
            statusMessage = error.localizedDescription
        }
    }

    func signOut() {
        if let token = DailyBreathBeyondIDKeychain.token {
            Task { await service.revoke(token: token) }
        }
        DailyBreathBeyondIDKeychain.deleteToken()
        account = nil
        statusMessage = "Signed out. Your reflections remain on this device."
    }

    private func restoreSession() async {
        guard let token = DailyBreathBeyondIDKeychain.token else { return }
        do {
            account = try await service.account(for: token)
            statusMessage = "Beyond ID connected."
        } catch {
            DailyBreathBeyondIDKeychain.deleteToken()
            statusMessage = "Your Beyond ID session expired. Sign in again to reconnect."
        }
    }
}

private struct DailyBreathSessionResponse: Decodable {
    let ok: Bool
    let authenticated: Bool
    let user: DailyBreathBeyondIDAccount?
    let error: String?
}

private struct DailyBreathMobileTokenResponse: Decodable {
    let ok: Bool
    let accessToken: String?
    let error: String?

    enum CodingKeys: String, CodingKey {
        case ok, error
        case accessToken = "access_token"
    }
}

private enum DailyBreathBeyondIDError: LocalizedError {
    case missingCallbackToken
    case unauthorized
    case server(String)

    var errorDescription: String? {
        switch self {
        case .missingCallbackToken: "Beyond ID did not return a sign-in token."
        case .unauthorized: "Your Beyond ID session expired. Sign in again."
        case .server(let message): message
        }
    }
}

private struct DailyBreathBeyondIDService: Sendable {
    private let baseURL = URL(string: "https://beyondimagination.co.technology")!

    func signInURL(codeChallenge: String) -> URL {
        var components = URLComponents(url: baseURL.appending(path: "beyond-id/auth/login.php"), resolvingAgainstBaseURL: false)!
        components.queryItems = [
            URLQueryItem(name: "app", value: "dailybreath"),
            URLQueryItem(name: "return", value: "/beyond-id/auth/mobile-complete.php?scheme=dailybreath&code_challenge=\(codeChallenge)")
        ]
        return components.url!
    }

    func code(from callbackURL: URL) throws -> String {
        let items = URLComponents(url: callbackURL, resolvingAgainstBaseURL: false)?.queryItems ?? []
        if let message = items.first(where: { $0.name == "error" })?.value, !message.isEmpty {
            throw DailyBreathBeyondIDError.server(message)
        }
        guard let code = items.first(where: { $0.name == "code" })?.value, !code.isEmpty else {
            throw DailyBreathBeyondIDError.missingCallbackToken
        }
        return code
    }

    func exchange(code: String, verifier: String) async throws -> String {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-token.php"))
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.httpBody = try JSONEncoder().encode(["code": code, "code_verifier": verifier])
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
            let message = try? JSONDecoder().decode(DailyBreathMobileTokenResponse.self, from: data).error
            throw DailyBreathBeyondIDError.server(message ?? "Could not complete Beyond ID sign-in.")
        }
        let payload = try JSONDecoder().decode(DailyBreathMobileTokenResponse.self, from: data)
        guard payload.ok, let token = payload.accessToken, !token.isEmpty else {
            throw DailyBreathBeyondIDError.server(payload.error ?? "Could not complete Beyond ID sign-in.")
        }
        return token
    }

    func account(for token: String) async throws -> DailyBreathBeyondIDAccount {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-session.php"))
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("daily-breath-ios", forHTTPHeaderField: "X-Beyond-App")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw DailyBreathBeyondIDError.server("Beyond ID returned an invalid response.")
        }
        if http.statusCode == 401 { throw DailyBreathBeyondIDError.unauthorized }
        guard (200..<300).contains(http.statusCode) else {
            let message = try? JSONDecoder().decode(DailyBreathSessionResponse.self, from: data).error
            throw DailyBreathBeyondIDError.server(message ?? "Beyond ID returned HTTP \(http.statusCode).")
        }
        let payload = try JSONDecoder().decode(DailyBreathSessionResponse.self, from: data)
        guard payload.ok, payload.authenticated, let account = payload.user else {
            throw DailyBreathBeyondIDError.server(payload.error ?? "Beyond ID sign-in failed.")
        }
        return account
    }

    func revoke(token: String) async {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-token-revoke.php"))
        request.httpMethod = "POST"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        _ = try? await URLSession.shared.data(for: request)
    }
}

private enum DailyBreathPKCE {
    static func makeVerifier() -> String {
        var bytes = [UInt8](repeating: 0, count: 48)
        let status = bytes.withUnsafeMutableBytes { buffer in
            SecRandomCopyBytes(kSecRandomDefault, buffer.count, buffer.baseAddress!)
        }
        guard status == errSecSuccess else { return UUID().uuidString.replacingOccurrences(of: "-", with: "") + UUID().uuidString.replacingOccurrences(of: "-", with: "") }
        return rtrim(Data(bytes).base64EncodedString())
    }

    static func challenge(for verifier: String) -> String {
        let digest = SHA256.hash(data: Data(verifier.utf8))
        return rtrim(Data(digest).base64EncodedString())
    }

    private static func rtrim(_ value: String) -> String {
        value.replacingOccurrences(of: "+", with: "-")
            .replacingOccurrences(of: "/", with: "_")
            .replacingOccurrences(of: "=", with: "")
    }
}

@MainActor
private final class DailyBreathWebAuthenticator: NSObject, ASWebAuthenticationPresentationContextProviding {
    private var session: ASWebAuthenticationSession?

    func authenticate(url: URL, callbackScheme: String) async throws -> URL {
        try await withCheckedThrowingContinuation { continuation in
            let session = ASWebAuthenticationSession(url: url, callbackURLScheme: callbackScheme) { [weak self] callbackURL, error in
                self?.session = nil
                if let callbackURL {
                    continuation.resume(returning: callbackURL)
                } else {
                    continuation.resume(throwing: error ?? DailyBreathBeyondIDError.server("Beyond ID sign-in was cancelled."))
                }
            }
            session.presentationContextProvider = self
            session.prefersEphemeralWebBrowserSession = false
            self.session = session
            if !session.start() {
                self.session = nil
                continuation.resume(throwing: DailyBreathBeyondIDError.server("Could not open Beyond ID sign-in."))
            }
        }
    }

    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        UIApplication.shared.connectedScenes
            .compactMap { $0 as? UIWindowScene }
            .flatMap(\.windows)
            .first { $0.isKeyWindow } ?? ASPresentationAnchor()
    }
}

private enum DailyBreathBeyondIDKeychain {
    private static let service = "technology.co.beyondimagination.thedailybreath"
    private static let account = "beyond-id-mobile-token"

    static var token: String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]
        var result: CFTypeRef?
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess, let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    static func save(_ token: String) throws {
        deleteToken()
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly,
            kSecValueData as String: Data(token.utf8)
        ]
        guard SecItemAdd(query as CFDictionary, nil) == errSecSuccess else {
            throw DailyBreathBeyondIDError.server("Could not securely store the Beyond ID session.")
        }
    }

    static func deleteToken() {
        let query: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrService as String: service, kSecAttrAccount as String: account]
        _ = SecItemDelete(query as CFDictionary)
    }
}
