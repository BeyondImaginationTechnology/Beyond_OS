import AVFoundation
import Combine
import Foundation

private struct PremiumVoiceRequest: Encodable {
    let text: String
    let locale: String
}

private struct PremiumVoiceResult {
    let audio: Data
    let provider: String?
}

private struct LocalProgress {
    let lessons: Set<String>
    let daily: Set<Int>
    let practice: Int
}

private struct CloudProgressWrite: Encodable {
    let completedLessonIDs: [String]
    let completedDailyLessonIDs: [Int]
    let correctPracticeCount: Int

    enum CodingKeys: String, CodingKey {
        case completedLessonIDs = "completed_lesson_ids"
        case completedDailyLessonIDs = "completed_daily_lesson_ids"
        case correctPracticeCount = "correct_practice_count"
    }
}

private struct CloudProgressResponse: Decodable {
    let ok: Bool
    let completedLessonIDs: [String]?
    let completedDailyLessonIDs: [Int]?
    let correctPracticeCount: Int?

    enum CodingKeys: String, CodingKey {
        case ok
        case completedLessonIDs = "completed_lesson_ids"
        case completedDailyLessonIDs = "completed_daily_lesson_ids"
        case correctPracticeCount = "correct_practice_count"
    }
}

private enum CloudProgressError: Error {
    case unauthorized
    case invalidResponse
}

private enum PremiumVoiceError: LocalizedError {
    case invalidResponse
    case serverStatus(Int, String)
    case invalidAudio

    var errorDescription: String? {
        switch self {
        case .invalidResponse:
            return "Premium voice response failed"
        case let .serverStatus(status, message):
            return message.isEmpty ? "Premium server \(status)" : "Premium server \(status): \(message)"
        case .invalidAudio:
            return "Premium voice returned invalid audio"
        }
    }
}

@MainActor
final class AppStore: ObservableObject {
    @Published private(set) var lesson = FrenchLesson.fallback
    @Published private(set) var dictionary: [DictionaryWord] = []
    @Published private(set) var academy = AcademyCatalog.fallback
    @Published private(set) var completedLessonIDs: Set<String> = []
    @Published private(set) var completedDailyLessonIDs: Set<Int> = []
    @Published private(set) var correctPracticeCount = 0
    @Published private(set) var cloudStatus: String?
    @Published private(set) var isRefreshing = false
    @Published private(set) var statusMessage = "Daily lesson"
    @Published var appTheme = AppTheme.classic {
        didSet { UserDefaults.standard.set(appTheme.rawValue, forKey: themeKey) }
    }

    private let endpoint = URL(string: "https://beyondimagination.co.technology/beyond-french/api/today.php")!
    private let voiceEndpoint = URL(string: "https://beyondimagination.co.technology/beyond-french/api/voice.php")!
    private let progressEndpoint = URL(string: "https://beyondimagination.co.technology/beyond-french/api/progress.php")!
    private let siteEndpoint = URL(string: "https://beyondimagination.co.technology")!
    private let speaker = AVSpeechSynthesizer()
    private var premiumVoicePlayer: AVAudioPlayer?
    private let completedKey = "BeyondFrench.completedLessonIDs"
    private let practiceKey = "BeyondFrench.correctPracticeCount"
    private let themeKey = "BeyondFrench.appTheme"
    private weak var auth: BeyondFrenchAuth?
    private var activeProgressOwner: Int?
    private var isSyncing = false
    private var syncAgain = false

    var completedAcademyLessonCount: Int { completedLessonIDs.count }

    func configureAuth(_ auth: BeyondFrenchAuth) {
        self.auth = auth
    }

    var totalAcademyLessons: Int {
        academy.modules.reduce(0) { $0 + $1.lessons.count }
    }

    var unlockedAcademyLessons: Int {
        academy.modules.reduce(0) { total, module in
            total + module.lessons.indices.filter { isLessonUnlocked(module: module, lessonIndex: $0) }.count
        }
    }

    func completedAcademyLessons(ageGroup: AgeGroup) -> Int {
        academy.modules.reduce(0) { total, module in
            total + module.lessons.indices.filter { isLessonCompleted(module: module, lessonIndex: $0, ageGroup: ageGroup) }.count
        }
    }

    func unlockedAcademyLessons(ageGroup: AgeGroup) -> Int {
        academy.modules.reduce(0) { total, module in
            total + module.lessons.indices.filter { isLessonUnlocked(module: module, lessonIndex: $0, ageGroup: ageGroup) }.count
        }
    }

    func load() async {
        loadDictionary()
        loadAcademy()
        loadProgress()
        loadTheme()
        await refreshLesson()
    }

    func switchProgressAccount(to userID: Int?) {
        guard activeProgressOwner != userID else { return }
        saveProgress()
        activeProgressOwner = userID
        apply(snapshot: localProgress(for: userID))
        if userID != nil { mergeGuestProgress() }
    }

    func syncProgress() async {
        guard let auth, auth.isSignedIn, let userID = auth.userID,
              let token = await auth.usableAccessToken() else { return }
        if isSyncing { syncAgain = true; return }
        isSyncing = true
        defer {
            isSyncing = false
            if syncAgain {
                syncAgain = false
                Task { await syncProgress() }
            }
        }

        switchProgressAccount(to: userID)
        do {
            let remote = try await cloudProgress(method: "GET", token: token)
            guard auth.isSignedIn, auth.userID == userID, activeProgressOwner == userID else { return }
            completedLessonIDs.formUnion(remote.completedLessonIDs ?? [])
            completedDailyLessonIDs.formUnion(remote.completedDailyLessonIDs ?? [])
            correctPracticeCount = max(correctPracticeCount, remote.correctPracticeCount ?? 0)
            saveProgress()
            let merged = try await cloudProgress(method: "PUT", token: token)
            guard auth.isSignedIn, auth.userID == userID, activeProgressOwner == userID else { return }
            completedLessonIDs.formUnion(merged.completedLessonIDs ?? [])
            completedDailyLessonIDs.formUnion(merged.completedDailyLessonIDs ?? [])
            correctPracticeCount = max(correctPracticeCount, merged.correctPracticeCount ?? 0)
            saveProgress()
            cloudStatus = "Progress synced with Beyond ID."
        } catch CloudProgressError.unauthorized {
            guard auth.isSignedIn, auth.userID == userID, activeProgressOwner == userID else { return }
            cloudStatus = "Sign in again to sync. Progress is saved on this device."
            auth.handleExpiredToken()
            switchProgressAccount(to: nil)
        } catch {
            cloudStatus = "Offline. Progress is saved on this device and will sync later."
        }
    }

    func refreshLesson() async {
        isRefreshing = true
        defer { isRefreshing = false }
        do {
            let (data, response) = try await URLSession.shared.data(from: endpoint)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200 else { throw URLError(.badServerResponse) }
            lesson = try JSONDecoder().decode(TodayResponse.self, from: data).lesson
            statusMessage = "Synced daily lesson"
        } catch {
            statusMessage = "Offline lesson"
        }
    }

    func speak(_ text: String, language: String = "fr-FR") {
        speaker.stopSpeaking(at: .immediate)
        premiumVoicePlayer?.stop()
        configureAudioSession()
        statusMessage = "Premium voice..."

        Task {
            do {
                let result = try await premiumVoiceAudio(for: text, language: language)
                let player = try AVAudioPlayer(data: result.audio)
                player.prepareToPlay()
                premiumVoicePlayer = player
                if player.play() {
                    statusMessage = result.provider.map { "\($0.capitalized) voice" } ?? "Premium voice"
                } else {
                    speakWithDeviceVoice(text, language: language, fallbackReason: "Premium playback failed")
                }
            } catch {
                speakWithDeviceVoice(text, language: language, fallbackReason: error.localizedDescription)
            }
        }
    }

    func speakLesson(_ lesson: FrenchLesson, language: DictionaryAudioLanguage = .french) {
        speaker.stopSpeaking(at: .immediate)
        premiumVoicePlayer?.stop()
        configureAudioSession()
        let text = lesson.text(for: language)

        if language.usesLiveProviderDemoVoice {
            speak(text, language: language.locale)
            return
        }

        if let url = bundledLessonAudioURL(for: lesson, language: language),
           let player = try? AVAudioPlayer(contentsOf: url) {
            player.prepareToPlay()
            premiumVoicePlayer = player
            if player.play() {
                statusMessage = "Prerecorded \(language.title) lesson"
                return
            }
        }
        // Keep compatibility with apps built before lesson audio was separated
        // from the dictionary resource tree.
        if language == .french, let url = Bundle.main.url(
            forResource: lesson.audioResourceName,
            withExtension: "mp3",
            subdirectory: "Audio/dictionary/fr-FR"
        ), let player = try? AVAudioPlayer(contentsOf: url) {
            player.prepareToPlay()
            premiumVoicePlayer = player
            if player.play() {
                statusMessage = "Prerecorded Azure voice"
                return
            }
        }
        guard let remoteURL = remoteLessonAudioURL(for: lesson, language: language) else {
            speak(text, language: language.locale)
            return
        }
        statusMessage = "Loading prerecorded \(language.title) lesson..."
        Task {
            do {
                let (data, response) = try await URLSession.shared.data(from: remoteURL)
                guard let http = response as? HTTPURLResponse,
                      200..<300 ~= http.statusCode,
                      data.count > 128 else { throw PremiumVoiceError.invalidAudio }
                let player = try AVAudioPlayer(data: data)
                player.prepareToPlay()
                premiumVoicePlayer = player
                if player.play() {
                    statusMessage = "Prerecorded \(language.title) lesson"
                } else {
                    speak(text, language: language.locale)
                }
            } catch {
                speak(text, language: language.locale)
            }
        }
    }

    func speakDictionaryWord(_ word: DictionaryWord, language: DictionaryAudioLanguage) {
        let text = word.text(for: language)
        guard !text.isEmpty else { return }
        speaker.stopSpeaking(at: .immediate)
        premiumVoicePlayer?.stop()
        configureAudioSession()

        if language.usesLiveProviderDemoVoice {
            speak(text, language: language.locale)
            return
        }

        if let url = bundledDictionaryAudioURL(for: word, language: language),
           let player = try? AVAudioPlayer(contentsOf: url) {
            player.prepareToPlay()
            premiumVoicePlayer = player
            if player.play() {
                statusMessage = "\(language.title) voice"
                return
            }
        }

        speak(text, language: language.locale)
    }

    private func speakWithDeviceVoice(_ text: String, language: String = "fr-FR", fallbackReason: String? = nil) {
        speaker.stopSpeaking(at: .immediate)
        premiumVoicePlayer?.stop()
        configureAudioSession()
        let utterance = AVSpeechUtterance(string: text)
        utterance.voice = bestVoice(for: language)
        utterance.rate = speechRate(for: language)
        utterance.pitchMultiplier = 1.04
        utterance.volume = 1
        if let fallbackReason, !fallbackReason.isEmpty {
            statusMessage = "Apple fallback: \(fallbackReason)"
        } else {
            statusMessage = utterance.voice?.quality == .premium ? "Premium device voice" : "Device voice"
        }
        speaker.speak(utterance)
    }

    private func premiumVoiceAudio(for text: String, language: String) async throws -> PremiumVoiceResult {
        var request = URLRequest(url: voiceEndpoint)
        request.httpMethod = "POST"
        request.timeoutInterval = 20
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("audio/mpeg", forHTTPHeaderField: "Accept")
        request.httpBody = try JSONEncoder().encode(PremiumVoiceRequest(text: text, locale: language))

        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw PremiumVoiceError.invalidResponse
        }
        guard 200..<300 ~= http.statusCode else {
            throw PremiumVoiceError.serverStatus(http.statusCode, premiumVoiceErrorMessage(from: data))
        }
        let contentType = http.value(forHTTPHeaderField: "Content-Type")?.lowercased() ?? ""
        guard contentType.contains("audio"), data.count > 128 else {
            throw PremiumVoiceError.invalidAudio
        }
        let provider = http.value(forHTTPHeaderField: "X-Narration-Provider")
        return PremiumVoiceResult(audio: data, provider: provider)
    }

    private func premiumVoiceErrorMessage(from data: Data) -> String {
        guard let object = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else { return "" }
        return (object["message"] as? String) ?? (object["error"] as? String) ?? ""
    }

    private func bundledDictionaryAudioURL(for word: DictionaryWord, language: DictionaryAudioLanguage) -> URL? {
        Bundle.main.url(
            forResource: word.audioResourceName,
            withExtension: "mp3",
            subdirectory: "Audio/dictionary/\(language.locale)"
        )
    }

    private func bundledLessonAudioURL(for lesson: FrenchLesson, language: DictionaryAudioLanguage) -> URL? {
        Bundle.main.url(
            forResource: lesson.audioResourceName,
            withExtension: "mp3",
            subdirectory: "Audio/lessons/\(language.locale)"
        )
    }

    private func remoteLessonAudioURL(for lesson: FrenchLesson, language: DictionaryAudioLanguage) -> URL? {
        guard let value = lesson.remoteAudioPath(for: language)?.trimmingCharacters(in: .whitespacesAndNewlines),
              !value.isEmpty else { return nil }
        if let absolute = URL(string: value), absolute.scheme != nil { return absolute }
        return URL(string: value, relativeTo: siteEndpoint)?.absoluteURL
    }

    func isLessonCompleted(module: AcademyModule, lessonIndex: Int) -> Bool {
        completedLessonIDs.contains(lessonID(module: module, lessonIndex: lessonIndex))
    }

    func isLessonCompleted(module: AcademyModule, lessonIndex: Int, ageGroup: AgeGroup) -> Bool {
        completedLessonIDs.contains(lessonID(module: module, lessonIndex: lessonIndex, ageGroup: ageGroup))
    }

    func isLessonUnlocked(module: AcademyModule, lessonIndex: Int) -> Bool {
        guard lessonIndex > 0 else { return true }
        return isLessonCompleted(module: module, lessonIndex: lessonIndex - 1)
    }

    func isLessonUnlocked(module: AcademyModule, lessonIndex: Int, ageGroup: AgeGroup) -> Bool {
        if lessonIndex > 0 {
            return isLessonCompleted(module: module, lessonIndex: lessonIndex - 1, ageGroup: ageGroup)
        }
        guard let moduleIndex = academy.modules.firstIndex(where: { $0.slug == module.slug }),
              moduleIndex > 0 else { return true }
        let previous = academy.modules[moduleIndex - 1]
        return previous.lessons.indices.allSatisfy {
            isLessonCompleted(module: previous, lessonIndex: $0, ageGroup: ageGroup)
        }
    }

    func completeLesson(module: AcademyModule, lessonIndex: Int) {
        completedLessonIDs.insert(lessonID(module: module, lessonIndex: lessonIndex))
        saveProgress()
        Task { await syncProgress() }
    }

    func completeLesson(module: AcademyModule, lessonIndex: Int, ageGroup: AgeGroup) {
        completedLessonIDs.insert(lessonID(module: module, lessonIndex: lessonIndex, ageGroup: ageGroup))
        saveProgress()
        Task { await syncProgress() }
    }

    func checkAnswer(_ answer: String, expected: String) -> Bool {
        normalized(answer) == normalized(expected)
    }

    func recordCorrectPractice(dailyLessonID: Int? = nil) {
        correctPracticeCount += 1
        if let dailyLessonID { completedDailyLessonIDs.insert(dailyLessonID) }
        saveProgress()
        Task { await syncProgress() }
    }

    private func loadDictionary() {
        guard let url = Bundle.main.url(forResource: "dictionary", withExtension: "json"),
              let data = try? Data(contentsOf: url),
              let words = try? JSONDecoder().decode([DictionaryWord].self, from: data) else { return }
        dictionary = words
    }

    private func loadAcademy() {
        guard let url = Bundle.main.url(forResource: "academy", withExtension: "json"),
              let data = try? Data(contentsOf: url),
              let catalog = try? JSONDecoder().decode(AcademyCatalog.self, from: data) else { return }
        academy = catalog
    }

    private func loadProgress() {
        let defaults = UserDefaults.standard
        if !defaults.bool(forKey: "BeyondFrench.progressMigrated.v1") {
            defaults.set(defaults.stringArray(forKey: completedKey) ?? [], forKey: progressKey(nil, "lessons"))
            defaults.set(defaults.integer(forKey: practiceKey), forKey: progressKey(nil, "practice"))
            defaults.set(true, forKey: "BeyondFrench.progressMigrated.v1")
        }
        activeProgressOwner = auth?.userID
        apply(snapshot: localProgress(for: activeProgressOwner))
        if activeProgressOwner != nil { mergeGuestProgress() }
    }

    private func loadTheme() {
        let savedTheme = UserDefaults.standard.string(forKey: themeKey).flatMap(AppTheme.init(rawValue:))
        appTheme = savedTheme ?? .classic
    }

    private func saveProgress() {
        save(snapshot: LocalProgress(
            lessons: completedLessonIDs,
            daily: completedDailyLessonIDs,
            practice: correctPracticeCount
        ), for: activeProgressOwner)
    }

    private func progressKey(_ owner: Int?, _ field: String) -> String {
        "BeyondFrench.progress.\(owner.map { String($0) } ?? "guest").\(field)"
    }

    private func localProgress(for owner: Int?) -> LocalProgress {
        let defaults = UserDefaults.standard
        return LocalProgress(
            lessons: Set(defaults.stringArray(forKey: progressKey(owner, "lessons")) ?? []),
            daily: Set(defaults.array(forKey: progressKey(owner, "daily")) as? [Int] ?? []),
            practice: defaults.integer(forKey: progressKey(owner, "practice"))
        )
    }

    private func save(snapshot: LocalProgress, for owner: Int?) {
        let defaults = UserDefaults.standard
        defaults.set(Array(snapshot.lessons), forKey: progressKey(owner, "lessons"))
        defaults.set(Array(snapshot.daily), forKey: progressKey(owner, "daily"))
        defaults.set(snapshot.practice, forKey: progressKey(owner, "practice"))
    }

    private func apply(snapshot: LocalProgress) {
        completedLessonIDs = snapshot.lessons
        completedDailyLessonIDs = snapshot.daily
        correctPracticeCount = snapshot.practice
    }

    private func mergeGuestProgress() {
        let guest = localProgress(for: nil)
        guard !guest.lessons.isEmpty || !guest.daily.isEmpty || guest.practice > 0 else { return }
        completedLessonIDs.formUnion(guest.lessons)
        completedDailyLessonIDs.formUnion(guest.daily)
        correctPracticeCount = max(correctPracticeCount, guest.practice)
        saveProgress()
        save(snapshot: LocalProgress(lessons: [], daily: [], practice: 0), for: nil)
    }

    private func cloudProgress(method: String, token: String) async throws -> CloudProgressResponse {
        var request = URLRequest(url: progressEndpoint)
        request.httpMethod = method
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        if method == "PUT" {
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
            request.httpBody = try JSONEncoder().encode(CloudProgressWrite(
                completedLessonIDs: Array(completedLessonIDs),
                completedDailyLessonIDs: Array(completedDailyLessonIDs),
                correctPracticeCount: correctPracticeCount
            ))
        }
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw CloudProgressError.invalidResponse }
        if http.statusCode == 401 { throw CloudProgressError.unauthorized }
        guard http.statusCode == 200 else { throw CloudProgressError.invalidResponse }
        let progress = try JSONDecoder().decode(CloudProgressResponse.self, from: data)
        guard progress.ok else { throw CloudProgressError.invalidResponse }
        return progress
    }

    private func lessonID(module: AcademyModule, lessonIndex: Int) -> String {
        "\(module.slug)-\(lessonIndex + 1)"
    }

    private func lessonID(module: AcademyModule, lessonIndex: Int, ageGroup: AgeGroup) -> String {
        "\(ageGroup.slug)-\(module.slug)-\(lessonIndex + 1)"
    }

    private func normalized(_ value: String) -> String {
        value
            .folding(options: [.caseInsensitive, .diacriticInsensitive], locale: .current)
            .replacingOccurrences(of: "[^a-z0-9]+", with: "", options: .regularExpression)
    }

    private func configureAudioSession() {
        do {
            try AVAudioSession.sharedInstance().setCategory(.playback, mode: .spokenAudio, options: [.duckOthers])
            try AVAudioSession.sharedInstance().setActive(true)
        } catch {
            statusMessage = "Voice ready"
        }
    }

    private func bestVoice(for language: String) -> AVSpeechSynthesisVoice? {
        let preferredLanguages = voiceFallbacks(for: language)
        let voices = AVSpeechSynthesisVoice.speechVoices().filter {
            !$0.name.localizedCaseInsensitiveContains("Jenny")
        }
        for code in preferredLanguages {
            if let exact = voices
                .filter({ $0.language == code })
                .sorted(by: voiceSort)
                .first {
                return exact
            }
        }
        for code in preferredLanguages {
            let prefix = String(code.prefix(2))
            if let regional = voices
                .filter({ $0.language.hasPrefix(prefix) })
                .sorted(by: voiceSort)
                .first {
                return regional
            }
        }
        return AVSpeechSynthesisVoice(language: language)
    }

    private func voiceFallbacks(for language: String) -> [String] {
        switch language {
        case "ht-HT":
            return ["ht-HT", "fr-FR", "fr-CA", "en-US"]
        case "en-JM":
            return ["en-JM", "en-GB", "en-US"]
        case "fr-CA":
            return ["fr-CA", "fr-FR", "en-US"]
        case "es-ES":
            return ["es-ES", "es-MX", "en-US"]
        default:
            return [language, "fr-FR", "en-US"]
        }
    }

    private func voiceSort(_ lhs: AVSpeechSynthesisVoice, _ rhs: AVSpeechSynthesisVoice) -> Bool {
        if lhs.quality != rhs.quality {
            return lhs.quality.rawValue > rhs.quality.rawValue
        }
        return lhs.name.localizedCaseInsensitiveCompare(rhs.name) == .orderedAscending
    }

    private func speechRate(for language: String) -> Float {
        switch language {
        case "es-ES":
            return 0.45
        case "en-JM":
            return 0.44
        case "ht-HT":
            return 0.41
        default:
            return 0.42
        }
    }
}
