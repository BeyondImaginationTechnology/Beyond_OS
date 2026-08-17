import AVFoundation
import Combine
import Foundation

@MainActor
final class QuestStore: ObservableObject {
    @Published private(set) var completedChallengeIDs: Set<String> = []
    @Published private(set) var xp = 0
    @Published private(set) var hearts = 5
    @Published private(set) var streak = 0
    @Published private(set) var lastResult: QuestResult?
    @Published private(set) var dictionary: [DictionaryWord] = []
    @Published private(set) var musicEnabled = true
    @Published var theme = QuestTheme.riviera {
        didSet { UserDefaults.standard.set(theme.rawValue, forKey: themeKey) }
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

    init() {
        load()
        loadDictionary()
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
    }

    func resetResult() {
        lastResult = nil
    }

    func refillHearts() {
        hearts = 5
        save()
    }

    func resetProgress() {
        completedChallengeIDs = []
        xp = 0
        hearts = 5
        streak = 0
        lastResult = nil
        save()
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
