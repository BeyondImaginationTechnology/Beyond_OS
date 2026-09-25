import AuthenticationServices
import Combine
import CryptoKit
import Foundation
import Security
import UIKit

@MainActor
final class BeyondFrenchAuth: NSObject, ObservableObject, ASWebAuthenticationPresentationContextProviding {
    @Published private(set) var isSignedIn = false
    @Published private(set) var userID: Int?
    @Published private(set) var message: String?

    private var session: ASWebAuthenticationSession?
    private var verifier = ""
    private let service = "technology.co.beyondimagination.beyondfrench"
    private let account = "beyond-id-mobile-token"
    private let refreshAccount = "beyond-id-mobile-refresh-token"
    private let expiresAtKey = "beyond-id-mobile-token-expires-at"
    private var refreshTask: Task<String?, Never>?
    private let baseURL = URL(string: "https://beyondimagination.co.technology")!

    var accessToken: String? { KeychainToken.read(service: service, account: account) }

    override init() {
        super.init()
    }

    func restoreSession() async {
        guard let token = await usableAccessToken() else { return }
        do {
            userID = try await accountID(for: token)
            isSignedIn = true
        } catch {
            if (error as? AuthError) == .unauthorized { handleExpiredToken() }
            else { message = "Beyond ID could not connect. Guest progress is still available." }
        }
    }

    func signIn() {
        message = nil
        verifier = (UUID().uuidString + UUID().uuidString).replacingOccurrences(of: "-", with: "")
        let digest = SHA256.hash(data: Data(verifier.utf8))
        let challenge = Data(digest).base64EncodedString()
            .replacingOccurrences(of: "+", with: "-")
            .replacingOccurrences(of: "/", with: "_")
            .replacingOccurrences(of: "=", with: "")
        var components = URLComponents(url: baseURL.appending(path: "beyond-id/auth/login.php"), resolvingAgainstBaseURL: false)!
        components.queryItems = [
            URLQueryItem(name: "app", value: "beyond-french"),
            URLQueryItem(name: "return", value: "/beyond-id/auth/mobile-complete.php?scheme=beyondfrench&code_challenge=\(challenge)")
        ]
        guard let url = components.url else { message = "Could not open Beyond ID."; return }
        session = ASWebAuthenticationSession(url: url, callbackURLScheme: "beyondfrench") { [weak self] callback, error in
            guard let self else { return }
            Task { @MainActor in
                self.session = nil
                if let error {
                    let nsError = error as NSError
                    if nsError.domain != ASWebAuthenticationSessionErrorDomain
                        || nsError.code != ASWebAuthenticationSessionError.Code.canceledLogin.rawValue {
                        self.message = "Beyond ID sign-in could not be completed."
                    }
                    return
                }
                guard let callback else { self.message = "Beyond ID did not return to the app."; return }
                let items = URLComponents(url: callback, resolvingAgainstBaseURL: false)?.queryItems ?? []
                if let returnedError = items.first(where: { $0.name == "error" })?.value {
                    self.message = returnedError
                    return
                }
                guard let code = items.first(where: { $0.name == "code" })?.value else {
                    self.message = "Beyond ID returned no sign-in code."
                    return
                }
                await self.exchange(code: code)
            }
        }
        session?.presentationContextProvider = self
        session?.prefersEphemeralWebBrowserSession = false
        if session?.start() != true { message = "Could not open Beyond ID." }
    }

    func signOut() {
        let oldToken = accessToken
        let oldRefresh = KeychainToken.read(service: service, account: refreshAccount)
        KeychainToken.delete(service: service, account: account)
        KeychainToken.delete(service: service, account: refreshAccount)
        UserDefaults.standard.removeObject(forKey: expiresAtKey)
        isSignedIn = false
        userID = nil
        message = "Signed out. You can keep learning as a guest and sign in later to restore Beyond ID progress."
        if let oldToken {
            Task {
                var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-token-revoke.php"))
                request.httpMethod = "POST"
                request.setValue("Bearer \(oldToken)", forHTTPHeaderField: "Authorization")
                request.setValue("application/json", forHTTPHeaderField: "Content-Type")
                request.httpBody = try? JSONEncoder().encode(["refresh_token": oldRefresh ?? ""])
                _ = try? await URLSession.shared.data(for: request)
            }
        }
    }

    func handleExpiredToken() {
        KeychainToken.delete(service: service, account: account)
        KeychainToken.delete(service: service, account: refreshAccount)
        UserDefaults.standard.removeObject(forKey: expiresAtKey)
        isSignedIn = false
        userID = nil
        message = "Your Beyond ID session expired. Sign in again to sync saved progress."
    }

    func usableAccessToken() async -> String? {
        if let refreshTask { return await refreshTask.value }
        guard let token = accessToken else { return nil }
        if UserDefaults.standard.double(forKey: expiresAtKey) > Date().timeIntervalSince1970 + 60 { return token }
        guard let refresh = KeychainToken.read(service: service, account: refreshAccount) else {
            handleExpiredToken()
            return nil
        }
        let task = Task { await self.rotateAccessToken(using: refresh) }
        refreshTask = task
        let renewed = await task.value
        refreshTask = nil
        return renewed
    }

    private func rotateAccessToken(using refresh: String) async -> String? {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-token-refresh.php"))
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try? JSONEncoder().encode(["refresh_token": refresh, "audience": "beyond-french-ios"])
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            let payload = try? JSONDecoder().decode(BeyondFrenchTokenResponse.self, from: data)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200,
                  let token = payload?.accessToken, let nextRefresh = payload?.refreshToken else {
                handleExpiredToken()
                return nil
            }
            guard KeychainToken.read(service: service, account: refreshAccount) == refresh else { return nil }
            guard KeychainToken.save(token, service: service, account: account), KeychainToken.save(nextRefresh, service: service, account: refreshAccount) else { return nil }
            UserDefaults.standard.set(Date().timeIntervalSince1970 + Double(payload?.expiresIn ?? 900), forKey: expiresAtKey)
            return token
        } catch {
            message = "Beyond ID could not renew the session. Check your connection and try again."
            return nil
        }
    }

    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        UIApplication.shared.connectedScenes
            .compactMap { $0 as? UIWindowScene }
            .flatMap(\.windows)
            .first { $0.isKeyWindow } ?? ASPresentationAnchor()
    }

    private func exchange(code: String) async {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-token.php"))
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try? JSONEncoder().encode(["code": code, "code_verifier": verifier, "device_id": deviceIdentifier(), "device_name": "Beyond French on \(UIDevice.current.model)", "token_version": "0.4"])
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            let payload = try? JSONDecoder().decode(BeyondFrenchTokenResponse.self, from: data)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200,
                  let token = payload?.accessToken, !token.isEmpty,
                  let refresh = payload?.refreshToken else {
                message = payload?.error ?? "Beyond ID sign-in failed."
                return
            }
            let accountID = try await accountID(for: token)
            guard KeychainToken.save(token, service: service, account: account), KeychainToken.save(refresh, service: service, account: refreshAccount) else {
                message = "Could not store the Beyond ID session securely."
                return
            }
            UserDefaults.standard.set(Date().timeIntervalSince1970 + Double(payload?.expiresIn ?? 900), forKey: expiresAtKey)
            userID = accountID
            isSignedIn = true
            message = "Signed in. Your French progress is syncing."
        } catch {
            message = "Beyond ID could not connect. Try again later."
        }
    }

    private func deviceIdentifier() -> String {
        let key = "beyond-french-ios-device-id"
        if let existing = UserDefaults.standard.string(forKey: key) { return existing }
        let value = UUID().uuidString.lowercased()
        UserDefaults.standard.set(value, forKey: key)
        return value
    }

    private func accountID(for token: String) async throws -> Int {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-session.php"))
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("beyond-french-ios", forHTTPHeaderField: "X-Beyond-App")
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw AuthError.invalidResponse }
        if http.statusCode == 401 { throw AuthError.unauthorized }
        guard http.statusCode == 200 else { throw AuthError.invalidResponse }
        let session = try JSONDecoder().decode(BeyondFrenchSessionResponse.self, from: data)
        guard session.ok, session.authenticated, session.user.id > 0 else { throw AuthError.invalidResponse }
        return session.user.id
    }
}

private enum AuthError: Error, Equatable {
    case unauthorized
    case invalidResponse
}

private struct BeyondFrenchSessionResponse: Decodable {
    let ok: Bool
    let authenticated: Bool
    let user: Account

    struct Account: Decodable { let id: Int }
}

private struct BeyondFrenchTokenResponse: Decodable {
    let accessToken: String?
    let refreshToken: String?
    let expiresIn: Int?
    let error: String?
    enum CodingKeys: String, CodingKey {
        case error
        case accessToken = "access_token"
        case refreshToken = "refresh_token"
        case expiresIn = "expires_in"
    }
}

private enum KeychainToken {
    static func save(_ value: String, service: String, account: String) -> Bool {
        delete(service: service, account: account)
        let item: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecValueData as String: Data(value.utf8),
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly
        ]
        return SecItemAdd(item as CFDictionary, nil) == errSecSuccess
    }

    static func read(service: String, account: String) -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]
        var result: CFTypeRef?
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess,
              let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    static func delete(service: String, account: String) {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account
        ]
        SecItemDelete(query as CFDictionary)
    }
}
