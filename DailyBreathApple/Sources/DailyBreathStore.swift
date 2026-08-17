import AVFoundation
import Foundation

@MainActor
final class DailyBreathStore: ObservableObject {
    @Published private(set) var verse = Verse.daily
    @Published private(set) var devotional = Devotional.today
    @Published private(set) var isRefreshing = false
    @Published private(set) var statusMessage = "Bundled daily verse"
    @Published var breathPhase = "Inhale"
    @Published var journalText = ""
    @Published var journalPrompt = DailyBreathStore.promptOfTheDay()
    @Published var journalMood = "Peaceful"
    @Published private(set) var entries: [JournalEntry] = []
    @Published private(set) var bibleLibrary = BibleLibrary.loadWorldEnglishBible()

    let practices = [
        PrayerPractice(id: 1, title: "Peace Breath", subtitle: "A four-count rhythm for calm and focus.", systemImage: "wind"),
        PrayerPractice(id: 2, title: "Guidance Prayer", subtitle: "Pause before decisions and ask for wisdom.", systemImage: "hands.sparkles.fill"),
        PrayerPractice(id: 3, title: "Gratitude Reset", subtitle: "Name what is good before the day moves on.", systemImage: "heart.fill"),
        PrayerPractice(id: 4, title: "Weekly Challenge", subtitle: "Turn faith into one practical action.", systemImage: "calendar.badge.checkmark")
    ]

    let academyPaths = [
        AcademyPath(
            id: 1,
            title: "Foundations of Faith",
            subtitle: "A practical starter path for Scripture, prayer, and daily practice.",
            systemImage: "book.pages.fill",
            lessons: [
                AcademyLesson(
                    id: 101,
                    title: "Start With Stillness",
                    duration: "4 min",
                    scripture: "Psalm 46:10",
                    summary: "Learn why Daily Breath begins with a pause before action.",
                    teaching: "Stillness is not a delay in your faith. It is often the doorway into a clearer response. Psalm 46 invites you to stop striving long enough to remember that God is present before the pressure, before the decision, and before the next task. A daily rhythm of stillness helps faith move from an idea into the body: one breath, one verse, one faithful step.",
                    practice: "Set a timer for one minute. Breathe slowly, repeat Psalm 46:10 once, and name one pressure you can release before moving on.",
                    reflectionPrompt: "Where do I need to stop rushing and make room for trust today?",
                    checkPrompt: "According to this lesson, what does stillness help you remember?",
                    checkAnswer: "God is present"
                ),
                AcademyLesson(
                    id: 102,
                    title: "Read Before You React",
                    duration: "5 min",
                    scripture: "James 1:19",
                    summary: "Practice letting Scripture shape your first response.",
                    teaching: "A reactive day pulls your attention in every direction. Scripture gives you a different beginning. James teaches a posture of quick listening, slow speaking, and slow anger. That rhythm is not passive. It is strong enough to interrupt hurry and patient enough to choose wisdom. Before you answer, scroll, decide, or defend yourself, let one verse slow the moment down.",
                    practice: "Before one reply today, pause and ask: have I listened well enough to answer with patience?",
                    reflectionPrompt: "What situation today needs listening before speaking?",
                    checkPrompt: "James 1:19 names quick listening, slow speaking, and slow what?",
                    checkAnswer: "anger"
                )
            ]
        ),
        AcademyPath(
            id: 2,
            title: "Life of Jesus",
            subtitle: "Short lessons from the Gospels for attention, compassion, and courage.",
            systemImage: "figure.walk",
            lessons: [
                AcademyLesson(
                    id: 201,
                    title: "The Pace of Jesus",
                    duration: "5 min",
                    scripture: "Mark 1:35",
                    summary: "Notice how Jesus makes space for prayer before public work.",
                    teaching: "Jesus moved toward people with compassion, but he also withdrew to pray. Mark shows him rising early, going to a quiet place, and grounding his day in communion with the Father. This is not escape. It is alignment. The Daily Breath rhythm follows that same pattern in miniature: quiet first, then action.",
                    practice: "Choose one part of tomorrow morning to begin with a short prayer before opening messages or tasks.",
                    reflectionPrompt: "What would change if I began one part of my day from prayer instead of pressure?",
                    checkPrompt: "In Mark 1:35, Jesus went to a quiet place to do what?",
                    checkAnswer: "pray"
                )
            ]
        ),
        AcademyPath(
            id: 3,
            title: "Wisdom and Prayer",
            subtitle: "Simple practices from Psalms and Proverbs for everyday devotion.",
            systemImage: "hands.sparkles.fill",
            lessons: [
                AcademyLesson(
                    id: 301,
                    title: "A Prayer You Can Carry",
                    duration: "3 min",
                    scripture: "Proverbs 3:5-6",
                    summary: "Turn a verse into a short prayer for ordinary decisions.",
                    teaching: "Wisdom often begins with surrender. Proverbs does not ask you to ignore your mind; it asks you not to make your own understanding the final authority. A carryable prayer can be simple: Lord, help me trust you here. Make the next step straight. Repeat it before a meeting, a message, a purchase, or a hard conversation.",
                    practice: "Write one decision in a sentence, then pray: Lord, help me trust you here and make the next step straight.",
                    reflectionPrompt: "What decision can I place before God in one sentence today?",
                    checkPrompt: "This lesson turns Proverbs 3:5-6 into what kind of prayer?",
                    checkAnswer: "carryable"
                )
            ]
        )
    ]

    private let speaker = AVSpeechSynthesizer()
    private var prerecordedPlayer: AVAudioPlayer?
    private var streamingPlayer: AVPlayer?
    private let endpoint = URL(string: "https://beyondimagination.co.technology/dailybreath/api/today.php")!

    func load() async {
        loadBundledDailyContent()
        await refreshToday()
    }

    func refreshToday() async {
        isRefreshing = true
        defer { isRefreshing = false }

        do {
            let (data, response) = try await URLSession.shared.data(from: endpoint)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200 else {
                throw URLError(.badServerResponse)
            }

            let today = try JSONDecoder().decode(DailyBreathTodayResponse.self, from: data)
            verse = today.verse
            devotional = today.devotional
            statusMessage = "Synced admin verse"
        } catch {
            loadBundledDailyContent()
            statusMessage = "Offline verse"
        }
    }

    private func loadBundledDailyContent() {
        verse = RecoveryContent.verseOfTheDay() ?? .daily
        devotional = RecoveryContent.devotionalOfTheDay() ?? .today
    }

    func speakVerse() {
        let referenceKey = verse.reference
            .folding(options: [.caseInsensitive, .diacriticInsensitive], locale: .current)
            .lowercased()
            .replacingOccurrences(of: "[^a-z0-9]+", with: "-", options: .regularExpression)
            .trimmingCharacters(in: CharacterSet(charactersIn: "-"))
        if playBundledNarration(named: "verse-\(referenceKey)") {
            statusMessage = "Prerecorded offline verse"
            return
        }
        if let audioURL = verse.audioURL, let url = URL(string: audioURL), url.scheme == "https" {
            speaker.stopSpeaking(at: .immediate)
            prerecordedPlayer?.stop()
            prepareAudioSessionForNarration()
            streamingPlayer = AVPlayer(url: url)
            streamingPlayer?.play()
            statusMessage = "Prerecorded Studio verse"
            return
        }
        speakText("\(verse.text) \(verse.reference)")
    }

    func speakText(_ text: String) {
        speaker.stopSpeaking(at: .immediate)
        prerecordedPlayer?.stop()
        streamingPlayer?.pause()
        prepareAudioSessionForNarration()

        let utterance = AVSpeechUtterance(string: text)
        utterance.voice = preferredNarrationVoice()
        utterance.rate = 0.38
        utterance.pitchMultiplier = 0.96
        utterance.volume = 0.92
        utterance.preUtteranceDelay = 0.08
        utterance.postUtteranceDelay = 0.12
        speaker.speak(utterance)
    }

    func speakAcademyLesson(_ lesson: AcademyLesson) {
        if playBundledNarration(named: "academy-\(lesson.id)") { return }
        speakText("\(lesson.title). \(lesson.scripture). \(lesson.teaching) Practice. \(lesson.practice)")
    }

    func speakBreathPattern(_ pattern: BreathPattern) {
        if playBundledNarration(named: "breath-pattern-\(pattern.id)") { return }
        speakText("\(pattern.title). \(pattern.intention) \(pattern.instruction)")
    }

    func speakBreathCue(_ cue: String) {
        let key = cue
            .lowercased()
            .replacingOccurrences(of: "[^a-z]+", with: "-", options: .regularExpression)
            .trimmingCharacters(in: CharacterSet(charactersIn: "-"))
        if playBundledNarration(named: "breath-\(key)") { return }
        speakText(cue)
    }

    @discardableResult
    private func playBundledNarration(named resource: String) -> Bool {
        guard let url = Bundle.main.url(
            forResource: resource,
            withExtension: "mp3",
            subdirectory: "Audio/Narration"
        ), let player = try? AVAudioPlayer(contentsOf: url) else {
            return false
        }

        speaker.stopSpeaking(at: .immediate)
        prerecordedPlayer?.stop()
        prepareAudioSessionForNarration()
        player.prepareToPlay()
        prerecordedPlayer = player
        return player.play()
    }

    private func preferredNarrationVoice() -> AVSpeechSynthesisVoice? {
        let preferredLanguages = ["en-US", "en-GB", "en-CA"]
        let voices = AVSpeechSynthesisVoice.speechVoices()

        for quality in [AVSpeechSynthesisVoiceQuality.premium, .enhanced, .default] {
            if let voice = voices.first(where: { preferredLanguages.contains($0.language) && $0.quality == quality }) {
                return voice
            }
        }

        return AVSpeechSynthesisVoice(language: "en-US")
    }

    private func prepareAudioSessionForNarration() {
        try? AVAudioSession.sharedInstance().setCategory(.playback, mode: .spokenAudio, options: [.duckOthers])
        try? AVAudioSession.sharedInstance().setActive(true)
    }

    func saveJournalEntry() {
        let trimmed = journalText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return }
        entries.insert(
            JournalEntry(
                id: UUID(),
                createdAt: Date(),
                prompt: journalPrompt,
                text: trimmed,
                mood: journalMood
            ),
            at: 0
        )
        journalText = ""
    }

    func prepareJournalReflection(prompt: String, text: String = "", mood: String? = nil) {
        journalPrompt = prompt
        journalText = text
        if let mood {
            journalMood = mood
        }
    }

    func updateJournalEntry(_ entry: JournalEntry, text: String, mood: String?) {
        let trimmed = text.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty, let index = entries.firstIndex(where: { $0.id == entry.id }) else { return }
        entries[index] = JournalEntry(
            id: entry.id,
            createdAt: entry.createdAt,
            prompt: entry.prompt,
            text: trimmed,
            mood: mood
        )
    }

    func deleteJournalEntries(at offsets: IndexSet) {
        entries.remove(atOffsets: offsets)
    }

    static func promptOfTheDay(for date: Date = Date(), calendar: Calendar = .current) -> String {
        let prompts = [
            "Where do I need to practice stillness today?",
            "What is one faithful next step I can take?",
            "What am I carrying that I can entrust to God?",
            "Where did I notice grace today?",
            "Who needs patience, courage, or kindness from me?"
        ]
        let day = calendar.ordinality(of: .day, in: .era, for: date) ?? 1
        return prompts[(day - 1) % prompts.count]
    }
}

private struct DailyBreathTodayResponse: Decodable {
    let verse: Verse
    let devotional: Devotional
}
