import AuthenticationServices
import Combine
import CryptoKit
import Foundation
import OSLog
import Security
import UIKit

@MainActor
final class JaguarAuthManager: NSObject, ObservableObject, ASWebAuthenticationPresentationContextProviding {
    @Published private(set) var isSignedIn: Bool
    @Published private(set) var isSigningIn = false
    @Published private(set) var message: String?
    @Published private(set) var signInDetail: String?

    var accessToken: String? { JaguarKeychain.read(service: service, account: tokenKey) }

    private let service = "Jaguar"
    private let tokenKey = "jaguar.beyondid.access-token"
    private let loginURL = URL(string: "https://beyondimagination.co.technology/beyond-id/auth/login.php")!
    private let tokenURL = URL(string: "https://beyondimagination.co.technology/beyond-id/api/mobile-token.php")!
    private var webSession: ASWebAuthenticationSession?
    private var verifier = ""
    private let logger = Logger(subsystem: "technology.co.beyondimagination.jaguar", category: "authentication")

    override init() {
        isSignedIn = JaguarKeychain.read(service: "Jaguar", account: "jaguar.beyondid.access-token") != nil
        super.init()
    }

    func signIn() {
        guard !isSigningIn else { return }
        message = nil
        signInDetail = nil
        isSigningIn = true
        verifier = randomURLSafe(count: 64)
        let challenge = base64URL(Data(SHA256.hash(data: Data(verifier.utf8))))
        var components = URLComponents(url: loginURL, resolvingAgainstBaseURL: false)!
        let returnPath = "/beyond-id/auth/mobile-complete.php?scheme=jaguar&code_challenge=\(challenge)"
        components.queryItems = [
            URLQueryItem(name: "app", value: "jaguar"),
            URLQueryItem(name: "return", value: returnPath)
        ]
        guard let url = components.url else {
            finishSignInFailure("We couldn’t prepare secure sign-in.", detail: "Unable to create the authorization request.")
            return
        }
        webSession = ASWebAuthenticationSession(url: url, callbackURLScheme: "jaguar") { [weak self] callback, error in
            guard let self else { return }
            Task { @MainActor in
                if let error {
                    if (error as? ASWebAuthenticationSessionError)?.code != .canceledLogin {
                        self.finishSignInFailure("We couldn’t finish sign-in.", detail: "The authorization session ended before Beyond ID returned a code.")
                    } else {
                        self.isSigningIn = false
                    }
                    return
                }
                guard let callback,
                      let code = URLComponents(url: callback, resolvingAgainstBaseURL: false)?.queryItems?.first(where: { $0.name == "code" })?.value else {
                    self.finishSignInFailure("We couldn’t finish sign-in.", detail: "Beyond ID returned without an authorization code.")
                    return
                }
                await self.exchange(code: code)
            }
        }
        webSession?.presentationContextProvider = self
        webSession?.prefersEphemeralWebBrowserSession = false
        if webSession?.start() != true {
            finishSignInFailure("We couldn’t start Beyond ID sign-in.", detail: "The system authentication session could not be presented.")
        }
    }

    func signOut(message: String? = nil) {
        JaguarKeychain.delete(service: service, account: tokenKey)
        isSignedIn = false
        self.message = message
        signInDetail = nil
    }

    func clearMessage() {
        message = nil
        signInDetail = nil
    }

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
            guard let http = response as? HTTPURLResponse else {
                finishSignInFailure("We couldn’t connect to Beyond ID.", detail: "The token service returned an invalid response.")
                return
            }
            let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
            guard (200..<300).contains(http.statusCode),
                  let token = json?["access_token"] as? String, !token.isEmpty else {
                let status = http.statusCode
                let serverCode = (json?["error"] as? String) ?? "No error payload"
                let errorCode = (json?["error_code"] as? String) ?? "unknown"
                logger.error("Beyond ID token exchange failed with HTTP \(status, privacy: .public): \(serverCode, privacy: .private(mask: .hash))")
                switch errorCode {
                case "authorization_code_rejected":
                    finishSignInFailure(
                        "Google sign-in finished, but Jaguar couldn’t verify the secure handoff.",
                        detail: "The one-time Beyond ID code expired, was already used, or did not match this sign-in attempt. Tap Try again and complete Google sign-in in the same session."
                    )
                case "token_exchange_rate_limited":
                    finishSignInFailure(
                        "Too many sign-in attempts.",
                        detail: "Beyond ID temporarily paused token exchanges to protect your account. Wait a few minutes, then try again once."
                    )
                case "token_service_unavailable":
                    finishSignInFailure(
                        "Beyond ID sign-in is temporarily unavailable.",
                        detail: "Your Google account was not changed. Jaguar could not create its secure session; please try again later."
                    )
                case "invalid_exchange_request":
                    finishSignInFailure(
                        "Jaguar couldn’t prepare the secure sign-in exchange.",
                        detail: "Close this message, then start a new Beyond ID sign-in attempt."
                    )
                default:
                    finishSignInFailure(
                        "Jaguar couldn’t finish Beyond ID sign-in.",
                        detail: "The secure token exchange was declined (HTTP \(status))."
                    )
                }
                return
            }
            JaguarKeychain.save(token, service: service, account: tokenKey)
            isSignedIn = true
            isSigningIn = false
            message = nil
            signInDetail = nil
        } catch {
            logger.error("Beyond ID token exchange connection failed: \(error.localizedDescription, privacy: .private(mask: .hash))")
            finishSignInFailure("We couldn’t connect to Beyond ID.", detail: "Check your connection, then try again.")
        }
    }

    private func finishSignInFailure(_ userMessage: String, detail: String) {
        isSigningIn = false
        message = userMessage
        signInDetail = detail
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
