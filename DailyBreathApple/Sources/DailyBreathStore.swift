import AVFoundation
import Foundation

@MainActor
final class DailyBreathStore: ObservableObject {
    @Published private(set) var verse = Verse.daily
    @Published private(set) var devotional = Devotional.today
    @Published var breathPhase = "Inhale"
    @Published var journalText = ""
    @Published private(set) var entries: [JournalEntry] = []
    @Published private(set) var bibleLibrary = BibleLibrary.loadWorldEnglishBible()

    let practices = [
        PrayerPractice(id: 1, title: "Peace Breath", subtitle: "A four-count rhythm for calm and focus.", systemImage: "wind"),
        PrayerPractice(id: 2, title: "Guidance Prayer", subtitle: "Pause before decisions and ask for wisdom.", systemImage: "hands.sparkles.fill"),
        PrayerPractice(id: 3, title: "Gratitude Reset", subtitle: "Name what is good before the day moves on.", systemImage: "heart.fill"),
        PrayerPractice(id: 4, title: "Weekly Challenge", subtitle: "Turn faith into one practical action.", systemImage: "calendar.badge.checkmark")
    ]

    let modules = [
        AcademyModule(id: 1, title: "Foundations of Faith", subtitle: "Prayer, Scripture, reflection, and daily practice.", progress: 0.42, isFree: true),
        AcademyModule(id: 2, title: "Life of Jesus", subtitle: "A guided path through the Gospels.", progress: 0.18, isFree: false),
        AcademyModule(id: 3, title: "Wisdom Books", subtitle: "Psalms, Proverbs, and practical devotion.", progress: 0.0, isFree: false)
    ]

    private let speaker = AVSpeechSynthesizer()

    func speakVerse() {
        speaker.stopSpeaking(at: .immediate)
        let utterance = AVSpeechUtterance(string: "\(verse.text) \(verse.reference)")
        utterance.voice = AVSpeechSynthesisVoice(language: "en-CA")
        utterance.rate = 0.42
        speaker.speak(utterance)
    }

    func saveJournalEntry() {
        let trimmed = journalText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return }
        entries.insert(JournalEntry(id: UUID(), createdAt: Date(), text: trimmed), at: 0)
        journalText = ""
    }
}
