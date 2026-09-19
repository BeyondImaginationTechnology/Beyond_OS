import AuthenticationServices
import Combine
import CryptoKit
import Foundation
import Security
import UIKit

@MainActor
final class BeyondIDAuthManager: NSObject, ObservableObject, ASWebAuthenticationPresentationContextProviding {
    @Published private(set) var isSignedIn: Bool
    @Published private(set) var message: String?
    @Published private(set) var accountDeletionMessage: String?

    var accessToken: String? {
        KeychainTokenStore.read(service: "DailyBreath", account: tokenKey)
    }

    private var session: ASWebAuthenticationSession?
    private var verifier = ""
    private let tokenKey = "dailybreath.beyondid.access-token"
    private let loginURL = URL(string: "https://beyondimagination.co.technology/beyond-id/auth/login.php")!
    private let tokenURL = URL(string: "https://beyondimagination.co.technology/beyond-id/api/mobile-token.php")!
    private let accountDeletionURL = URL(string: "https://beyondimagination.co.technology/beyond-id/api/account-deletion-request.php")!

    override init() {
        isSignedIn = KeychainTokenStore.read(service: "DailyBreath", account: tokenKey) != nil
        super.init()
    }

    func signIn() {
        message = nil
        verifier = randomURLSafe(count: 64)
        let challenge = base64URL(SHA256.hash(data: Data(verifier.utf8)))
        var components = URLComponents(url: loginURL, resolvingAgainstBaseURL: false)!
        let returnPath = "/beyond-id/auth/mobile-complete.php?scheme=dailybreath&code_challenge=\(challenge)"
        components.queryItems = [
            URLQueryItem(name: "app", value: "dailybreath"),
            URLQueryItem(name: "return", value: returnPath)
        ]
        session = ASWebAuthenticationSession(url: components.url!, callbackURLScheme: "dailybreath") { [weak self] callback, error in
            guard let self else { return }
            Task { @MainActor in
                if let error {
                    if (error as? ASWebAuthenticationSessionError)?.code != .canceledLogin { self.message = "Beyond-ID sign-in could not be completed." }
                    return
                }
                guard let callback, let code = URLComponents(url: callback, resolvingAgainstBaseURL: false)?.queryItems?.first(where: { $0.name == "code" })?.value else {
                    self.message = "Beyond-ID sign-in returned no authorization code."
                    return
                }
                await self.exchange(code: code)
            }
        }
        session?.presentationContextProvider = self
        session?.prefersEphemeralWebBrowserSession = false
        if session?.start() != true { message = "Beyond-ID sign-in could not be started." }
    }

    func signOut() {
        KeychainTokenStore.delete(service: "DailyBreath", account: tokenKey)
        isSignedIn = false
        message = "Signed out of Beyond-ID on this device."
    }

    func requestAccountDeletion() async -> Bool {
        accountDeletionMessage = nil
        guard let token = accessToken else {
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
        request.httpBody = try? JSONSerialization.data(withJSONObject: ["code": code, "code_verifier": verifier])
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode),
                  let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
                  let token = json["access_token"] as? String, !token.isEmpty else {
                message = "Beyond-ID sign-in could not be exchanged securely."
                return
            }
            KeychainTokenStore.save(token, service: "DailyBreath", account: tokenKey)
            isSignedIn = true
            message = "Signed in with Beyond-ID."
        } catch { message = "Beyond-ID sign-in could not connect." }
    }

    private func randomURLSafe(count: Int) -> String {
        base64URL(Data((0..<count).map { _ in UInt8.random(in: 0...255) }))
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
