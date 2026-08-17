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
    @Published var theme = QuestTheme.riviera {
        didSet { UserDefaults.standard.set(theme.rawValue, forKey: themeKey) }
    }

    let regions = QuestContent.regions

    private let speaker = AVSpeechSynthesizer()
    private let completedKey = "FrenchQuest.completedChallengeIDs"
    private let xpKey = "FrenchQuest.xp"
    private let heartsKey = "FrenchQuest.hearts"
    private let streakKey = "FrenchQuest.streak"
    private let themeKey = "FrenchQuest.theme"

    init() {
        load()
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
        configureAudioSession()
        let utterance = AVSpeechUtterance(string: text)
        utterance.voice = bestVoice()
        utterance.rate = 0.42
        utterance.pitchMultiplier = 1.05
        speaker.speak(utterance)
    }

    private func load() {
        completedChallengeIDs = Set(UserDefaults.standard.stringArray(forKey: completedKey) ?? [])
        xp = UserDefaults.standard.integer(forKey: xpKey)
        let savedHearts = UserDefaults.standard.object(forKey: heartsKey) as? Int
        hearts = savedHearts ?? 5
        streak = UserDefaults.standard.integer(forKey: streakKey)
        theme = UserDefaults.standard.string(forKey: themeKey).flatMap(QuestTheme.init(rawValue:)) ?? .riviera
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

    private func bestVoice() -> AVSpeechSynthesisVoice? {
        AVSpeechSynthesisVoice.speechVoices()
            .filter { $0.language == "fr-FR" || $0.language == "fr-CA" }
            .sorted {
                if $0.quality != $1.quality { return $0.quality.rawValue > $1.quality.rawValue }
                return $0.language == "fr-FR"
            }
            .first ?? AVSpeechSynthesisVoice(language: "fr-FR")
    }
}
