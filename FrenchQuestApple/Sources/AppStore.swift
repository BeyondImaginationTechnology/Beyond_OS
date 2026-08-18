import AVFoundation
import AuthenticationServices
import Combine
import Foundation
import Security
import UIKit

@MainActor
final class QuestStore: ObservableObject {
    @Published private(set) var completedChallengeIDs: Set<String> = []
    @Published private(set) var xp = 0
    @Published private(set) var hearts = 5
    @Published private(set) var streak = 0
    @Published private(set) var lastResult: QuestResult?
    @Published private(set) var dictionary: [DictionaryWord] = []
    @Published private(set) var musicEnabled = true
    @Published private(set) var beyondIDAccount: BeyondIDAccount?
    @Published private(set) var cloudMessage = "Sign in to save this quest across devices."
    @Published private(set) var isCloudBusy = false
    @Published private(set) var hasSavedGame = false
    @Published var theme = QuestTheme.riviera {
        didSet {
            UserDefaults.standard.set(theme.rawValue, forKey: themeKey)
            scheduleCloudSave()
        }
    }

    let regions = QuestContent.regions

    private let speaker = AVSpeechSynthesizer()
    private var prerecordedPlayer: AVAudioPlayer?
    private var musicPlayer: AVAudioPlayer?
    private let completedKey = "FrenchQuest.completedChallengeIDs"
    private let xpKey = "FrenchQuest.xp"
    private let heartsKey = "FrenchQuest.hearts"
    private let streakKey = "FrenchQuest.streak"
    private let themeKey = "FrenchQuest.theme"
    private let musicKey = "FrenchQuest.musicEnabled"
    private let saveExistsKey = "FrenchQuest.hasSavedGame"
    private let beyondID = FrenchQuestBeyondIDService()
    private let webAuthenticator = FrenchQuestWebAuthenticator()
    private var mobileToken: String?
    private var cloudSaveTask: Task<Void, Never>?
    private var isApplyingCloudSave = false

    init() {
        load()
        loadDictionary()
        Task { await restoreBeyondIDSession() }
    }

    var totalChallenges: Int {
        regions.reduce(0) { $0 + $1.challenges.count }
    }

    var progress: Double {
        guard totalChallenges > 0 else { return 0 }
        return Double(completedChallengeIDs.count) / Double(totalChallenges)
    }

    var currentRegion: QuestRegion {
        regions.first { isRegionUnlocked($0) && completedCount(in: $0) < $0.lessonCount } ?? regions.first ?? QuestContent.regions[0]
    }

    func completedCount(in region: QuestRegion) -> Int {
        region.challenges.filter { completedChallengeIDs.contains($0.id) }.count
    }

    func isRegionUnlocked(_ region: QuestRegion) -> Bool {
        guard let index = regions.firstIndex(where: { $0.id == region.id }) else { return false }
        if index == 0 { return true }
        let previous = regions[index - 1]
        return completedCount(in: previous) == previous.lessonCount
    }

    func isChallengeUnlocked(_ challenge: QuestChallenge, in region: QuestRegion) -> Bool {
        guard isRegionUnlocked(region),
              let index = region.challenges.firstIndex(of: challenge) else { return false }
        if index == 0 { return true }
        return completedChallengeIDs.contains(region.challenges[index - 1].id)
    }

    func submit(_ choice: String, for challenge: QuestChallenge, in region: QuestRegion) {
        if normalized(choice) == normalized(challenge.answer) {
            let isFirstClear = !completedChallengeIDs.contains(challenge.id)
            completedChallengeIDs.insert(challenge.id)
            xp += isFirstClear ? region.reward / max(region.lessonCount, 1) : 5
            streak += 1
            hearts = min(5, hearts + 1)
            lastResult = QuestResult(correct: true, message: isFirstClear ? "Quest cleared. +XP" : "Perfect recall. +5 XP")
        } else {
            hearts = max(0, hearts - 1)
            streak = 0
            lastResult = QuestResult(correct: false, message: "Not yet. Listen, then try again.")
        }
        save()
        scheduleCloudSave()
    }

    func resetResult() {
        lastResult = nil
    }

    func refillHearts() {
        hearts = 5
        save()
        scheduleCloudSave()
    }

    func resetProgress() {
        completedChallengeIDs = []
        xp = 0
        hearts = 5
        streak = 0
        lastResult = nil
        save()
        scheduleCloudSave()
    }

    func signInToBeyondID() async {
        guard !isCloudBusy else { return }
        isCloudBusy = true
        cloudMessage = "Opening Beyond ID…"
        defer { isCloudBusy = false }
        do {
            let callbackURL = try await webAuthenticator.authenticate(
                url: beyondID.signInURL(),
                callbackScheme: "frenchquest"
            )
            let token = try beyondID.token(from: callbackURL)
            let account = try await beyondID.account(for: token)
            try FrenchQuestKeychain.save(token)
            mobileToken = token
            beyondIDAccount = account
            cloudMessage = "Signed in as \(account.displayName). Choose Load to restore a save or Save to upload this device."
        } catch {
            cloudMessage = error.localizedDescription
        }
    }

    func signOutOfBeyondID() {
        cloudSaveTask?.cancel()
        mobileToken = nil
        beyondIDAccount = nil
        FrenchQuestKeychain.deleteToken()
        cloudMessage = "Signed out. Your progress remains saved on this device."
    }

    func saveToCloud() async {
        guard let mobileToken else {
            cloudMessage = "Sign in with Beyond ID before saving to the cloud."
            return
        }
        isCloudBusy = true
        cloudMessage = "Saving quest…"
        defer { isCloudBusy = false }
        do {
            try await beyondID.save(snapshot: cloudSnapshot, token: mobileToken)
            cloudMessage = "Quest saved to Beyond ID."
        } catch {
            handleCloudError(error)
        }
    }

    func loadFromCloud() async {
        guard let mobileToken else {
            cloudMessage = "Sign in with Beyond ID before loading a cloud save."
            return
        }
        isCloudBusy = true
        cloudMessage = "Loading quest…"
        defer { isCloudBusy = false }
        do {
            guard let snapshot = try await beyondID.loadSave(token: mobileToken) else {
                cloudMessage = "No cloud save exists yet. Choose Save to create one."
                return
            }
            isApplyingCloudSave = true
            completedChallengeIDs = Set(snapshot.completedChallengeIDs)
            xp = max(0, snapshot.xp)
            hearts = min(5, max(0, snapshot.hearts))
            streak = max(0, snapshot.streak)
            if let savedTheme = QuestTheme(rawValue: snapshot.theme) { theme = savedTheme }
            lastResult = nil
            save()
            isApplyingCloudSave = false
            cloudMessage = "Cloud save loaded."
        } catch {
            isApplyingCloudSave = false
            handleCloudError(error)
        }
    }

    func speak(_ text: String) {
        speaker.stopSpeaking(at: .immediate)
        prerecordedPlayer?.stop()
        configureAudioSession()

        if let url = bundledQuestAudioURL(for: text),
           let player = try? AVAudioPlayer(contentsOf: url) {
            player.prepareToPlay()
            prerecordedPlayer = player
            if player.play() { return }
        }

        speakWithDeviceVoice(text, language: "fr-FR")
    }

    private func speakWithDeviceVoice(_ text: String, language: String) {
        let utterance = AVSpeechUtterance(string: text)
        utterance.voice = bestVoice(for: language)
        utterance.rate = speechRate(for: language)
        utterance.pitchMultiplier = 1.05
        speaker.speak(utterance)
    }

    func speakDictionaryWord(_ word: DictionaryWord, language: DictionaryAudioLanguage) {
        let text = word.text(for: language)
        guard !text.isEmpty else { return }
        speaker.stopSpeaking(at: .immediate)
        prerecordedPlayer?.stop()
        configureAudioSession()

        if let url = bundledDictionaryAudioURL(for: word, language: language),
           let player = try? AVAudioPlayer(contentsOf: url) {
            player.prepareToPlay()
            prerecordedPlayer = player
            if player.play() { return }
        }

        speakWithDeviceVoice(text, language: language.locale)
    }

    func startBackgroundMusicIfNeeded() {
        guard musicEnabled else { return }
        if let musicPlayer {
            if !musicPlayer.isPlaying {
                musicPlayer.play()
            }
            return
        }
        guard let url = Bundle.main.url(
            forResource: "french-accordion",
            withExtension: "mp3",
            subdirectory: "Audio/Music"
        ), let player = try? AVAudioPlayer(contentsOf: url) else { return }

        try? AVAudioSession.sharedInstance().setCategory(.ambient, mode: .default, options: [.mixWithOthers])
        try? AVAudioSession.sharedInstance().setActive(true)
        player.numberOfLoops = -1
        player.volume = 0.16
        player.prepareToPlay()
        musicPlayer = player
        player.play()
    }

    func pauseBackgroundMusic() {
        musicPlayer?.pause()
    }

    func toggleBackgroundMusic() {
        musicEnabled.toggle()
        UserDefaults.standard.set(musicEnabled, forKey: musicKey)
        if musicEnabled {
            startBackgroundMusicIfNeeded()
        } else {
            musicPlayer?.pause()
        }
    }

    private func load() {
        hasSavedGame = UserDefaults.standard.bool(forKey: saveExistsKey)
            || UserDefaults.standard.object(forKey: completedKey) != nil
            || UserDefaults.standard.object(forKey: xpKey) != nil
            || UserDefaults.standard.object(forKey: heartsKey) != nil
            || UserDefaults.standard.object(forKey: streakKey) != nil
        completedChallengeIDs = Set(UserDefaults.standard.stringArray(forKey: completedKey) ?? [])
        xp = UserDefaults.standard.integer(forKey: xpKey)
        let savedHearts = UserDefaults.standard.object(forKey: heartsKey) as? Int
        hearts = savedHearts ?? 5
        streak = UserDefaults.standard.integer(forKey: streakKey)
        theme = UserDefaults.standard.string(forKey: themeKey).flatMap(QuestTheme.init(rawValue:)) ?? .riviera
        if UserDefaults.standard.object(forKey: musicKey) != nil {
            musicEnabled = UserDefaults.standard.bool(forKey: musicKey)
        }
    }

    private func loadDictionary() {
        guard let url = Bundle.main.url(forResource: "dictionary", withExtension: "json"),
              let data = try? Data(contentsOf: url),
              let words = try? JSONDecoder().decode([DictionaryWord].self, from: data) else { return }
        dictionary = words
    }

    private func save() {
        UserDefaults.standard.set(Array(completedChallengeIDs), forKey: completedKey)
        UserDefaults.standard.set(xp, forKey: xpKey)
        UserDefaults.standard.set(hearts, forKey: heartsKey)
        UserDefaults.standard.set(streak, forKey: streakKey)
        UserDefaults.standard.set(true, forKey: saveExistsKey)
        hasSavedGame = true
    }

    private var cloudSnapshot: FrenchQuestCloudSave {
        FrenchQuestCloudSave(
            completedChallengeIDs: Array(completedChallengeIDs).sorted(),
            xp: xp,
            hearts: hearts,
            streak: streak,
            theme: theme.rawValue
        )
    }

    private func scheduleCloudSave() {
        guard mobileToken != nil, !isApplyingCloudSave else { return }
        cloudSaveTask?.cancel()
        cloudSaveTask = Task { [weak self] in
            try? await Task.sleep(for: .milliseconds(700))
            guard !Task.isCancelled, let self else { return }
            await self.saveToCloud()
        }
    }

    private func restoreBeyondIDSession() async {
        guard let token = FrenchQuestKeychain.token else { return }
        do {
            beyondIDAccount = try await beyondID.account(for: token)
            mobileToken = token
            cloudMessage = "Beyond ID connected."
        } catch {
            FrenchQuestKeychain.deleteToken()
            cloudMessage = "Your Beyond ID session expired. Sign in again to use cloud saves."
        }
    }

    private func handleCloudError(_ error: Error) {
        if let beyondIDError = error as? FrenchQuestBeyondIDError,
           case .unauthorized = beyondIDError {
            mobileToken = nil
            beyondIDAccount = nil
            FrenchQuestKeychain.deleteToken()
        }
        cloudMessage = error.localizedDescription
    }

    private func normalized(_ value: String) -> String {
        value
            .folding(options: [.caseInsensitive, .diacriticInsensitive], locale: .current)
            .replacingOccurrences(of: "[^a-z0-9]+", with: "", options: .regularExpression)
    }

    private func configureAudioSession() {
        try? AVAudioSession.sharedInstance().setCategory(.playback, mode: .spokenAudio, options: [.duckOthers])
        try? AVAudioSession.sharedInstance().setActive(true)
    }

    private func bundledQuestAudioURL(for text: String) -> URL? {
        Bundle.main.url(
            forResource: text.audioResourceName,
            withExtension: "mp3",
            subdirectory: "Audio/quest/fr-FR"
        )
    }

    private func bundledDictionaryAudioURL(for word: DictionaryWord, language: DictionaryAudioLanguage) -> URL? {
        Bundle.main.url(
            forResource: word.audioResourceName,
            withExtension: "mp3",
            subdirectory: "Audio/dictionary/\(language.locale)"
        )
    }

    private func bestVoice(for language: String) -> AVSpeechSynthesisVoice? {
        let fallbacks: [String] = switch language {
        case "ht-HT": ["ht-HT", "fr-FR", "fr-CA", "en-US"]
        case "en-JM": ["en-JM", "en-GB", "en-US"]
        case "es-ES": ["es-ES", "es-MX", "en-US"]
        default: [language, "fr-FR", "fr-CA"]
        }
        let voices = AVSpeechSynthesisVoice.speechVoices()
        for code in fallbacks {
            if let voice = voices
                .filter({ $0.language == code })
                .sorted(by: voiceSort)
                .first {
                return voice
            }
        }
        return AVSpeechSynthesisVoice(language: language)
    }

    private func voiceSort(_ lhs: AVSpeechSynthesisVoice, _ rhs: AVSpeechSynthesisVoice) -> Bool {
        if lhs.quality != rhs.quality {
            return lhs.quality.rawValue > rhs.quality.rawValue
        }
        return lhs.name.localizedCaseInsensitiveCompare(rhs.name) == .orderedAscending
    }

    private func speechRate(for language: String) -> Float {
        switch language {
        case "es-ES": 0.45
        case "en-JM": 0.44
        case "ht-HT": 0.41
        default: 0.42
        }
    }
}

struct BeyondIDAccount: Decodable, Equatable {
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
        for candidate in [displayNameValue, name, firstName] {
            if let candidate, !candidate.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                return candidate
            }
        }
        return email
    }
}

struct FrenchQuestCloudSave: Codable, Equatable {
    let completedChallengeIDs: [String]
    let xp: Int
    let hearts: Int
    let streak: Int
    let theme: String

    enum CodingKeys: String, CodingKey {
        case completedChallengeIDs = "completed_challenge_ids"
        case xp, hearts, streak, theme
    }
}

private struct FrenchQuestSessionResponse: Decodable {
    let ok: Bool
    let authenticated: Bool
    let user: BeyondIDAccount?
    let error: String?
}

private struct FrenchQuestSaveResponse: Decodable {
    let ok: Bool
    let save: FrenchQuestCloudSave?
    let error: String?
}

private struct FrenchQuestAPIResponse: Decodable {
    let ok: Bool
    let error: String?
}

enum FrenchQuestBeyondIDError: LocalizedError {
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

private struct FrenchQuestBeyondIDService: Sendable {
    private let baseURL = URL(string: "https://beyondimagination.co.technology")!

    func signInURL() -> URL {
        var components = URLComponents(url: baseURL.appending(path: "beyond-id/auth/login.php"), resolvingAgainstBaseURL: false)!
        components.queryItems = [
            URLQueryItem(name: "app", value: "beyond-french"),
            URLQueryItem(name: "return", value: "/beyond-id/auth/mobile-complete.php?scheme=frenchquest")
        ]
        return components.url!
    }

    func token(from callbackURL: URL) throws -> String {
        let items = URLComponents(url: callbackURL, resolvingAgainstBaseURL: false)?.queryItems ?? []
        if let message = items.first(where: { $0.name == "error" })?.value, !message.isEmpty {
            throw FrenchQuestBeyondIDError.server(message)
        }
        guard let token = items.first(where: { $0.name == "token" })?.value, !token.isEmpty else {
            throw FrenchQuestBeyondIDError.missingCallbackToken
        }
        return token
    }

    func account(for token: String) async throws -> BeyondIDAccount {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/mobile-session.php"))
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        let (data, response) = try await URLSession.shared.data(for: request)
        try validate(response, data: data)
        let payload = try JSONDecoder().decode(FrenchQuestSessionResponse.self, from: data)
        guard payload.ok, payload.authenticated, let account = payload.user else {
            throw FrenchQuestBeyondIDError.server(payload.error ?? "Beyond ID sign-in failed.")
        }
        return account
    }

    func loadSave(token: String) async throws -> FrenchQuestCloudSave? {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/french-quest-save.php"))
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        let (data, response) = try await URLSession.shared.data(for: request)
        try validate(response, data: data)
        let payload = try JSONDecoder().decode(FrenchQuestSaveResponse.self, from: data)
        guard payload.ok else { throw FrenchQuestBeyondIDError.server(payload.error ?? "Could not load the cloud save.") }
        return payload.save
    }

    func save(snapshot: FrenchQuestCloudSave, token: String) async throws {
        var request = URLRequest(url: baseURL.appending(path: "beyond-id/api/french-quest-save.php"))
        request.httpMethod = "PUT"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONEncoder().encode(snapshot)
        let (data, response) = try await URLSession.shared.data(for: request)
        try validate(response, data: data)
        let payload = try JSONDecoder().decode(FrenchQuestAPIResponse.self, from: data)
        guard payload.ok else { throw FrenchQuestBeyondIDError.server(payload.error ?? "Could not save the quest.") }
    }

    private func validate(_ response: URLResponse, data: Data) throws {
        guard let response = response as? HTTPURLResponse else {
            throw FrenchQuestBeyondIDError.server("Beyond ID returned an invalid response.")
        }
        if response.statusCode == 401 { throw FrenchQuestBeyondIDError.unauthorized }
        guard (200..<300).contains(response.statusCode) else {
            let message = (try? JSONDecoder().decode(FrenchQuestAPIResponse.self, from: data).error)
            throw FrenchQuestBeyondIDError.server(message ?? "Beyond ID returned HTTP \(response.statusCode).")
        }
    }
}

@MainActor
private final class FrenchQuestWebAuthenticator: NSObject, ASWebAuthenticationPresentationContextProviding {
    private var session: ASWebAuthenticationSession?

    func authenticate(url: URL, callbackScheme: String) async throws -> URL {
        try await withCheckedThrowingContinuation { continuation in
            let session = ASWebAuthenticationSession(url: url, callbackURLScheme: callbackScheme) { [weak self] callbackURL, error in
                self?.session = nil
                if let callbackURL {
                    continuation.resume(returning: callbackURL)
                } else {
                    continuation.resume(throwing: error ?? FrenchQuestBeyondIDError.server("Beyond ID sign-in was cancelled."))
                }
            }
            session.presentationContextProvider = self
            session.prefersEphemeralWebBrowserSession = false
            self.session = session
            if !session.start() {
                self.session = nil
                continuation.resume(throwing: FrenchQuestBeyondIDError.server("Could not open Beyond ID sign-in."))
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

private enum FrenchQuestKeychain {
    private static let service = "technology.co.beyondimagination.frenchquest"
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
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess,
              let data = result as? Data else { return nil }
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
        let status = SecItemAdd(query as CFDictionary, nil)
        guard status == errSecSuccess else {
            throw FrenchQuestBeyondIDError.server("Could not securely store the Beyond ID session.")
        }
    }

    static func deleteToken() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account
        ]
        _ = SecItemDelete(query as CFDictionary)
    }
}
