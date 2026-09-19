import AuthenticationServices
import Combine
import CryptoKit
import Foundation
import Security
import UIKit

@MainActor
final class JaguarAuthManager: NSObject, ObservableObject, ASWebAuthenticationPresentationContextProviding {
    @Published private(set) var isSignedIn: Bool
    @Published private(set) var message: String?

    var accessToken: String? { JaguarKeychain.read(service: service, account: tokenKey) }

    private let service = "Jaguar"
    private let tokenKey = "jaguar.beyondid.access-token"
    private let loginURL = URL(string: "https://beyondimagination.co.technology/beyond-id/auth/login.php")!
    private let tokenURL = URL(string: "https://beyondimagination.co.technology/beyond-id/api/mobile-token.php")!
    private var webSession: ASWebAuthenticationSession?
    private var verifier = ""

    override init() {
        isSignedIn = JaguarKeychain.read(service: "Jaguar", account: "jaguar.beyondid.access-token") != nil
        super.init()
    }

    func signIn() {
        message = nil
        verifier = randomURLSafe(count: 64)
        let challenge = base64URL(SHA256.hash(data: Data(verifier.utf8)))
        var components = URLComponents(url: loginURL, resolvingAgainstBaseURL: false)!
        let returnPath = "/beyond-id/auth/mobile-complete.php?scheme=jaguar&code_challenge=\(challenge)"
        components.queryItems = [
            URLQueryItem(name: "app", value: "jaguar"),
            URLQueryItem(name: "return", value: returnPath)
        ]
        guard let url = components.url else {
            message = "Beyond ID sign-in could not be prepared."
            return
        }
        webSession = ASWebAuthenticationSession(url: url, callbackURLScheme: "jaguar") { [weak self] callback, error in
            guard let self else { return }
            Task { @MainActor in
                if let error {
                    if (error as? ASWebAuthenticationSessionError)?.code != .canceledLogin {
                        self.message = "Beyond ID sign-in could not be completed."
                    }
                    return
                }
                guard let callback,
                      let code = URLComponents(url: callback, resolvingAgainstBaseURL: false)?.queryItems?.first(where: { $0.name == "code" })?.value else {
                    self.message = "Beyond ID returned no authorization code."
                    return
                }
                await self.exchange(code: code)
            }
        }
        webSession?.presentationContextProvider = self
        webSession?.prefersEphemeralWebBrowserSession = false
        if webSession?.start() != true { message = "Beyond ID sign-in could not be started." }
    }

    func signOut(message: String = "Signed out of Beyond ID on this device.") {
        JaguarKeychain.delete(service: service, account: tokenKey)
        isSignedIn = false
        self.message = message
    }

    func clearMessage() { message = nil }

    func presentationAnchor(for session: ASWebAuthenticationSession) -> ASPresentationAnchor {
        UIApplication.shared.connectedScenes
            .compactMap { $0 as? UIWindowScene }
            .flatMap(\.windows)
            .first(where: \.isKeyWindow) ?? ASPresentationAnchor()
    }

    private func exchange(code: String) async {
        var request = URLRequest(url: tokenURL)
        request.httpMethod = "POST"
        request.timeoutInterval = 30
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try? JSONSerialization.data(withJSONObject: ["code": code, "code_verifier": verifier])
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode),
                  let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
                  let token = json["access_token"] as? String, !token.isEmpty else {
                message = "Beyond ID sign-in could not be exchanged securely."
                return
            }
            JaguarKeychain.save(token, service: service, account: tokenKey)
            isSignedIn = true
            message = "Signed in with Beyond ID."
        } catch {
            message = "Beyond ID sign-in could not connect."
        }
    }

    private func randomURLSafe(count: Int) -> String {
        base64URL(Data((0..<count).map { _ in UInt8.random(in: 0...255) }))
    }

    private func base64URL<T: DataProtocol>(_ value: T) -> String {
        Data(value).base64EncodedString()
            .replacingOccurrences(of: "+", with: "-")
            .replacingOccurrences(of: "/", with: "_")
            .replacingOccurrences(of: "=", with: "")
    }
}

private enum JaguarKeychain {
    static func save(_ value: String, service: String, account: String) {
        delete(service: service, account: account)
        let query: [CFString: Any] = [
            kSecClass: kSecClassGenericPassword,
            kSecAttrService: service,
            kSecAttrAccount: account,
            kSecValueData: Data(value.utf8),
            kSecAttrAccessible: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly
        ]
        SecItemAdd(query as CFDictionary, nil)
    }

    static func read(service: String, account: String) -> String? {
        var result: CFTypeRef?
        let query: [CFString: Any] = [
            kSecClass: kSecClassGenericPassword,
            kSecAttrService: service,
            kSecAttrAccount: account,
            kSecReturnData: true,
            kSecMatchLimit: kSecMatchLimitOne
        ]
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess,
              let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    static func delete(service: String, account: String) {
        SecItemDelete([kSecClass: kSecClassGenericPassword, kSecAttrService: service, kSecAttrAccount: account] as CFDictionary)
    }
}
