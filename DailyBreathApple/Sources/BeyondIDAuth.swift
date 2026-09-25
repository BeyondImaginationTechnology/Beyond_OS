import AuthenticationServices
import Combine
import CryptoKit
import Foundation
import Security
import UIKit

@MainActor
final class BeyondIDAuthManager: NSObject, ObservableObject, ASWebAuthenticationPresentationContextProviding {
    @Published private(set) var isSignedIn: Bool
    @Published private(set) var isSigningIn = false
    @Published private(set) var message: String?
    @Published private(set) var accountDeletionMessage: String?

    var accessToken: String? {
        KeychainTokenStore.read(service: "DailyBreath", account: tokenKey)
    }

    private var session: ASWebAuthenticationSession?
    private var tokenRefreshTask: Task<String?, Never>?
    private var verifier = ""
    private let tokenKey = "dailybreath.beyondid.access-token"
    private let refreshTokenKey = "dailybreath.beyondid.refresh-token"
    private let tokenExpiresAtKey = "dailybreath.beyondid.expires-at"
    private let loginURL = URL(string: "https://beyondimagination.co.technology/beyond-id/auth/login.php")!
    private let tokenURL = URL(string: "https://beyondimagination.co.technology/beyond-id/api/mobile-token.php")!
    private let refreshURL = URL(string: "https://beyondimagination.co.technology/beyond-id/api/mobile-token-refresh.php")!
    private let revokeURL = URL(string: "https://beyondimagination.co.technology/beyond-id/api/mobile-token-revoke.php")!
    private let accountDeletionURL = URL(string: "https://beyondimagination.co.technology/beyond-id/api/account-deletion-request.php")!

    override init() {
        isSignedIn = KeychainTokenStore.read(service: "DailyBreath", account: tokenKey) != nil
        super.init()
    }

    func signIn() {
        guard !isSigningIn else { return }
        message = nil
        isSigningIn = true
        verifier = randomURLSafe(count: 64)
        let challenge = base64URL(Data(SHA256.hash(data: Data(verifier.utf8))))
        var components = URLComponents(url: loginURL, resolvingAgainstBaseURL: false)!
        let returnPath = "/beyond-id/auth/mobile-complete.php?scheme=dailybreath&code_challenge=\(challenge)"
        components.queryItems = [
            URLQueryItem(name: "return", value: returnPath)
        ]
        session = ASWebAuthenticationSession(url: components.url!, callbackURLScheme: "dailybreath") { [weak self] callback, error in
            guard let self else { return }
            Task { @MainActor in
                if let error {
                    if (error as? ASWebAuthenticationSessionError)?.code != .canceledLogin { self.message = "Beyond-ID sign-in could not be completed." }
                    self.isSigningIn = false
                    return
                }
                guard let callback else {
                    self.message = "Beyond ID sign-in did not return to the app."
                    self.isSigningIn = false
                    return
                }
                let queryItems = URLComponents(url: callback, resolvingAgainstBaseURL: false)?.queryItems ?? []
                if let returnedError = queryItems.first(where: { $0.name == "error" })?.value, !returnedError.isEmpty {
                    self.message = "\(returnedError) If this keeps happening, try again in a moment or contact support."
                    self.isSigningIn = false
                    return
                }
                guard let code = queryItems.first(where: { $0.name == "code" })?.value else {
                    self.message = "Beyond ID sign-in returned no authorization code. Please try again."
                    self.isSigningIn = false
                    return
                }
                await self.exchange(code: code)
            }
        }
        session?.presentationContextProvider = self
        session?.prefersEphemeralWebBrowserSession = false
        if session?.start() != true {
            isSigningIn = false
            message = "Beyond-ID sign-in could not be started. Check your connection and try again."
        }
    }

    func signOut() {
        if let token = accessToken {
            let refreshToken = KeychainTokenStore.read(service: "DailyBreath", account: refreshTokenKey)
            Task {
                var request = URLRequest(url: revokeURL)
                request.httpMethod = "POST"
                request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
                request.setValue("application/json", forHTTPHeaderField: "Content-Type")
                let body: [String: String] = refreshToken.map { ["refresh_token": $0] } ?? [:]
                request.httpBody = try? JSONSerialization.data(withJSONObject: body)
                _ = try? await URLSession.shared.data(for: request)
            }
        }
        clearSession(message: "Signed out of Beyond-ID on this device.")
    }

    func usableAccessToken() async -> String? {
        if let tokenRefreshTask { return await tokenRefreshTask.value }
        guard let token = accessToken else { return nil }
        let expiration = UserDefaults.standard.double(forKey: tokenExpiresAtKey)
        if expiration > Date().timeIntervalSince1970 + 60 { return token }
        guard let refresh = KeychainTokenStore.read(service: "DailyBreath", account: refreshTokenKey) else {
            clearSession(message: "Your Beyond-ID session expired. Sign in again to continue.")
            return nil
        }
        let task = Task { await rotateAccessToken(using: refresh) }
        tokenRefreshTask = task
        let renewed = await task.value
        tokenRefreshTask = nil
        return renewed
    }

    private func rotateAccessToken(using refresh: String) async -> String? {
        var request = URLRequest(url: refreshURL)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try? JSONSerialization.data(withJSONObject: ["refresh_token": refresh, "audience": "daily-breath-ios"])
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode),
                  let nextAccess = json?["access_token"] as? String,
                  let nextRefresh = json?["refresh_token"] as? String else {
                clearSession(message: "Your Beyond-ID session expired. Sign in again to continue.")
                return nil
            }
            guard KeychainTokenStore.read(service: "DailyBreath", account: refreshTokenKey) == refresh else { return nil }
            KeychainTokenStore.save(nextAccess, service: "DailyBreath", account: tokenKey)
            KeychainTokenStore.save(nextRefresh, service: "DailyBreath", account: refreshTokenKey)
            UserDefaults.standard.set(Date().timeIntervalSince1970 + (json?["expires_in"] as? Double ?? 900), forKey: tokenExpiresAtKey)
            return nextAccess
        } catch {
            message = "Could not renew Beyond-ID right now. Check your connection and try again."
            return nil
        }
    }

    func handleAuthenticationExpired() {
        clearSession(message: "Your Beyond-ID session expired. Sign in again to continue.")
    }

    private func clearSession(message: String) {
        KeychainTokenStore.delete(service: "DailyBreath", account: tokenKey)
        KeychainTokenStore.delete(service: "DailyBreath", account: refreshTokenKey)
        UserDefaults.standard.removeObject(forKey: tokenExpiresAtKey)
        isSignedIn = false
        self.message = message
        isSigningIn = false
    }

    func requestAccountDeletion() async -> Bool {
        accountDeletionMessage = nil
        guard let token = await usableAccessToken() else {
            accountDeletionMessage = "Sign in before requesting account deletion."
            return false
        }

        var request = URLRequest(url: accountDeletionURL)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.httpBody = try? JSONSerialization.data(withJSONObject: ["confirm": "DELETE"])

        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
                accountDeletionMessage = json?["error"] as? String ?? "The deletion request could not be submitted."
                return false
            }
            accountDeletionMessage = json?["message"] as? String ?? "Your account deletion request was submitted."
            return true
        } catch {
            accountDeletionMessage = "The deletion request could not connect. Please try again."
            return false
        }
    }

    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }.flatMap(\.windows).first(where: \.isKeyWindow) ?? ASPresentationAnchor()
    }

    private func exchange(code: String) async {
        var request = URLRequest(url: tokenURL)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try? JSONSerialization.data(withJSONObject: ["code": code, "code_verifier": verifier, "device_id": deviceIdentifier(), "device_name": "DailyBreath on \(UIDevice.current.model)", "token_version": "0.4"])
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode),
                  let token = json?["access_token"] as? String, !token.isEmpty,
                  let refresh = json?["refresh_token"] as? String else {
                let serverMessage = json?["error"] as? String
                message = (serverMessage ?? "Beyond-ID sign-in could not be completed.") + " Check your connection and retry. If the service remains unavailable, contact support."
                isSigningIn = false
                return
            }
            KeychainTokenStore.save(token, service: "DailyBreath", account: tokenKey)
            KeychainTokenStore.save(refresh, service: "DailyBreath", account: refreshTokenKey)
            UserDefaults.standard.set(Date().timeIntervalSince1970 + (json?["expires_in"] as? Double ?? 900), forKey: tokenExpiresAtKey)
            isSignedIn = true
            isSigningIn = false
            message = "Signed in with Beyond-ID."
        } catch {
            isSigningIn = false
            message = "Beyond-ID sign-in could not connect. Check your connection and try again."
        }
    }

    private func randomURLSafe(count: Int) -> String {
        base64URL(Data((0..<count).map { _ in UInt8.random(in: 0...255) }))
    }

    private func deviceIdentifier() -> String {
        let key = "dailybreath.beyondid.device-id"
        if let existing = UserDefaults.standard.string(forKey: key) { return existing }
        let value = UUID().uuidString.lowercased()
        UserDefaults.standard.set(value, forKey: key)
        return value
    }

    private func base64URL<T: DataProtocol>(_ value: T) -> String {
        Data(value).base64EncodedString().replacingOccurrences(of: "+", with: "-").replacingOccurrences(of: "/", with: "_").replacingOccurrences(of: "=", with: "")
    }
}

private enum KeychainTokenStore {
    static func save(_ value: String, service: String, account: String) {
        let data = Data(value.utf8)
        delete(service: service, account: account)
        SecItemAdd([kSecClass: kSecClassGenericPassword, kSecAttrService: service, kSecAttrAccount: account, kSecValueData: data, kSecAttrAccessible: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly] as CFDictionary, nil)
    }

    static func read(service: String, account: String) -> String? {
        var result: CFTypeRef?
        let status = SecItemCopyMatching([kSecClass: kSecClassGenericPassword, kSecAttrService: service, kSecAttrAccount: account, kSecReturnData: true, kSecMatchLimit: kSecMatchLimitOne] as CFDictionary, &result)
        guard status == errSecSuccess, let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    static func delete(service: String, account: String) {
        SecItemDelete([kSecClass: kSecClassGenericPassword, kSecAttrService: service, kSecAttrAccount: account] as CFDictionary)
    }
}
