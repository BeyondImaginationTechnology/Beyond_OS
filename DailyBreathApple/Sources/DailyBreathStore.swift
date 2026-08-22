import AVFoundation
import Foundation
#if canImport(WidgetKit)
import WidgetKit
#endif

enum DailyBreathAPIError: Error, Equatable {
    case invalidURL
    case badResponse
    case staleDate(expected: String, received: String)
}

struct DailyBreathTodayResponse: Decodable, Equatable, Sendable {
    let date: String
    let verse: Verse
    let devotional: Devotional
    let challenge: RecoveryChallenge?
}

struct DailyBreathAPIClient: Sendable {
    let endpoint: URL
    let session: URLSession
    let timeoutInterval: TimeInterval

    init(
        endpoint: URL = URL(string: "https://beyondimagination.co.technology/dailybreath/api/today.php")!,
        session: URLSession = .shared,
        timeoutInterval: TimeInterval = 10
    ) {
        self.endpoint = endpoint
        self.session = session
        self.timeoutInterval = timeoutInterval
    }

    func fetch(dateKey: String) async throws -> DailyBreathTodayResponse {
        guard var components = URLComponents(url: endpoint, resolvingAgainstBaseURL: false) else {
            throw DailyBreathAPIError.invalidURL
        }
        components.queryItems = [URLQueryItem(name: "date", value: dateKey)]
        guard let requestURL = components.url else { throw DailyBreathAPIError.invalidURL }

        var request = URLRequest(url: requestURL)
        request.cachePolicy = .reloadIgnoringLocalCacheData
        request.timeoutInterval = timeoutInterval
        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse, http.statusCode == 200 else {
            throw DailyBreathAPIError.badResponse
        }

        let today = try JSONDecoder().decode(DailyBreathTodayResponse.self, from: data)
        guard today.date == dateKey else {
            throw DailyBreathAPIError.staleDate(expected: dateKey, received: today.date)
        }
        return today
    }
}

@MainActor
final class DailyBreathStore: ObservableObject {
    @Published private(set) var verse = RecoveryContent.verseOfTheDay() ?? .daily
    @Published private(set) var devotional = RecoveryContent.devotionalOfTheDay() ?? .today
    @Published private(set) var challenge = RecoveryContent.challengeOfTheDay()
    @Published private(set) var isRefreshing = false
    @Published private(set) var statusMessage = "Bundled daily content"
    @Published var breathPhase = "Inhale"
    @Published var journalText = ""
    @Published var journalPrompt = DailyBreathStore.promptOfTheDay()
    @Published var journalMood = "Peaceful"
    @Published private(set) var entries: [JournalEntry] = []
    @Published private(set) var bibleLibrary = BibleLibrary(translation: "World English Bible", books: [])
    @Published private(set) var isBibleLoading = true
    @Published private(set) var challengeCompletedDayKeys: [String] = []
    @Published private(set) var bibleAnnotations: [String: BibleAnnotation] = [:]
    @Published private(set) var dailyHistory: [String: DailyHistoryRecord] = [:]
    @Published private(set) var iCloudStatusMessage = "iCloud sync is off"

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
    private let apiClient: DailyBreathAPIClient
    private let userDataStore: DailyBreathUserDataStore
    private var challengeCompletionDayKeys: [String: [String]] = [:]
    private var localModifiedAt = Date.distantPast
    private var deletedJournalEntryIDs: [UUID: Date] = [:]

    init(
        apiClient: DailyBreathAPIClient = DailyBreathAPIClient(),
        userDataStore: DailyBreathUserDataStore = .live
    ) {
        self.apiClient = apiClient
        self.userDataStore = userDataStore
        if let userData = try? userDataStore.load() {
            entries = userData.journalEntries.sorted { $0.createdAt > $1.createdAt }
            challengeCompletionDayKeys = userData.challengeCompletionDayKeys
            bibleAnnotations = userData.bibleAnnotations
            dailyHistory = userData.dailyHistory
            deletedJournalEntryIDs = userData.deletedJournalEntryIDs
            localModifiedAt = userData.modifiedAt
        }
        migrateLegacyFavorites()
        updateCurrentChallengeProgress()
    }

    func load() async {
        if UserDefaults.standard.bool(forKey: "encryptedICloudSyncEnabled") {
            await refreshFromICloud()
        }
        loadBundledDailyContent(for: Date())
        isBibleLoading = true
        defer { isBibleLoading = false }
        let bibleTask = Task.detached(priority: .userInitiated) {
            BibleLibrary.loadWorldEnglishBible()
        }
        await refreshToday()
        bibleLibrary = await bibleTask.value
    }

    func refreshToday() async {
        isRefreshing = true
        defer { isRefreshing = false }
        let requestedDate = Date()
        let requestedDateKey = Self.dateKey(requestedDate)

        do {
            let today = try await apiClient.fetch(dateKey: requestedDateKey)
            verse = today.verse
            devotional = today.devotional
            challenge = today.challenge ?? RecoveryContent.challengeOfTheDay(for: requestedDate)
            updateCurrentChallengeProgress()
            recordDailyContent(for: requestedDate)
            statusMessage = "Synced daily content"
        } catch {
            loadBundledDailyContent(for: requestedDate)
            statusMessage = "Offline daily content"
        }
    }

    private func loadBundledDailyContent(for date: Date) {
        verse = RecoveryContent.verseOfTheDay(for: date) ?? .daily
        devotional = RecoveryContent.devotionalOfTheDay(for: date) ?? .today
        challenge = RecoveryContent.challengeOfTheDay(for: date)
        updateCurrentChallengeProgress()
        journalPrompt = Self.promptOfTheDay(for: date)
        recordDailyContent(for: date)
    }

    private static func dateKey(_ date: Date) -> String {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = .current
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter.string(from: date)
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
        let savedRate = UserDefaults.standard.double(forKey: "narrationRate")
        utterance.rate = Float(savedRate == 0 ? 0.38 : savedRate)
        if let language = UserDefaults.standard.string(forKey: "narrationVoiceLanguage"), !language.isEmpty {
            utterance.voice = AVSpeechSynthesisVoice(language: language) ?? utterance.voice
        }
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

    func speakBibleVerse(_ verse: BibleVerse) {
        speakText("\(verse.reference). \(verse.text)")
    }

    func speakBibleChapter(_ chapter: BibleChapter) {
        let verses = chapter.verses.map { "Verse \($0.verse). \($0.text)" }.joined(separator: " ")
        speakText("\(chapter.title). \(verses)")
    }

    func stopNarration() {
        speaker.stopSpeaking(at: .immediate)
        prerecordedPlayer?.stop()
        streamingPlayer?.pause()
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
                mood: journalMood,
                updatedAt: Date()
            ),
            at: 0
        )
        journalText = ""
        persistUserData()
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
            mood: mood,
            updatedAt: Date()
        )
        persistUserData()
    }

    func deleteJournalEntries(at offsets: IndexSet) {
        for index in offsets where entries.indices.contains(index) {
            deletedJournalEntryIDs[entries[index].id] = Date()
        }
        entries.remove(atOffsets: offsets)
        persistUserData()
    }

    var challengeProgressCount: Int {
        challengeCompletedDayKeys.count
    }

    var isChallengeCompleteToday: Bool {
        challengeCompletedDayKeys.contains(Self.dateKey(Date()))
    }

    func completeChallengeToday() {
        guard let challenge else { return }
        let todayKey = Self.dateKey(Date())
        var keys = challengeCompletionDayKeys[challenge.id, default: []]
        guard !keys.contains(todayKey) else { return }
        keys.append(todayKey)
        challengeCompletionDayKeys[challenge.id] = Array(keys.suffix(max(challenge.targetCount, 14)))
        updateCurrentChallengeProgress()
        persistUserData()
    }

    func annotation(for verse: BibleVerse) -> BibleAnnotation? {
        bibleAnnotations[verse.id]
    }

    func toggleFavorite(_ verse: BibleVerse) {
        var annotation = editableAnnotation(for: verse)
        annotation.isFavorite.toggle()
        if annotation.isFavorite, annotation.collections.isEmpty {
            annotation.collections = ["Favorites"]
        } else if !annotation.isFavorite {
            annotation.collections = []
        }
        saveAnnotation(annotation)
    }

    func toggleVerse(_ verse: BibleVerse, inCollection collection: String) {
        let cleanName = collection.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !cleanName.isEmpty else { return }
        var annotation = editableAnnotation(for: verse)
        if annotation.collections.contains(cleanName) {
            annotation.collections.removeAll { $0 == cleanName }
        } else {
            annotation.collections.append(cleanName)
            annotation.collections.sort()
        }
        annotation.isFavorite = !annotation.collections.isEmpty
        saveAnnotation(annotation)
    }

    func setHighlight(_ color: String?, for verse: BibleVerse) {
        var annotation = editableAnnotation(for: verse)
        annotation.highlightColor = color
        saveAnnotation(annotation)
    }

    func setNote(_ note: String, for verse: BibleVerse) {
        var annotation = editableAnnotation(for: verse)
        annotation.note = note.trimmingCharacters(in: .whitespacesAndNewlines)
        saveAnnotation(annotation)
    }

    var favoriteCollections: [String] {
        Array(Set(bibleAnnotations.values.flatMap(\.collections))).sorted()
    }

    private func editableAnnotation(for verse: BibleVerse) -> BibleAnnotation {
        bibleAnnotations[verse.id] ?? BibleAnnotation(
            verseID: verse.id,
            isFavorite: false,
            collections: [],
            highlightColor: nil,
            note: "",
            updatedAt: Date()
        )
    }

    private func saveAnnotation(_ value: BibleAnnotation) {
        var annotation = value
        annotation.updatedAt = Date()
        bibleAnnotations[annotation.verseID] = annotation
        persistUserData()
    }

    private func migrateLegacyFavorites() {
        let defaults = UserDefaults.standard
        let legacyIDs = defaults.string(forKey: "favoriteVerseIDs")?
            .split(separator: ",").map(String.init) ?? []
        guard !legacyIDs.isEmpty else { return }
        for id in legacyIDs where bibleAnnotations[id] == nil {
            bibleAnnotations[id] = BibleAnnotation(
                verseID: id,
                isFavorite: true,
                collections: ["Favorites"],
                highlightColor: nil,
                note: "",
                updatedAt: Date()
            )
        }
        defaults.removeObject(forKey: "favoriteVerseIDs")
        persistUserData(syncToICloud: false)
    }

    private func recordDailyContent(for date: Date) {
        let key = Self.dateKey(date)
        dailyHistory[key] = DailyHistoryRecord(
            dayKey: key,
            verseReference: verse.reference,
            verseText: verse.text,
            devotionalTitle: devotional.title,
            updatedAt: Date()
        )
        publishWidgetVerse(dateKey: key)
        persistUserData()
    }

    private func publishWidgetVerse(dateKey: String) {
        let defaults = UserDefaults(suiteName: "group.technology.co.beyondimagination.thedailybreath")
        defaults?.set(dateKey, forKey: "widgetVerseDate")
        defaults?.set(verse.text, forKey: "widgetVerseText")
        defaults?.set(verse.reference, forKey: "widgetVerseReference")
        _ = defaults?.synchronize()
#if canImport(WidgetKit)
        WidgetCenter.shared.reloadAllTimelines()
#endif
    }

    var recoveryNewsletterShareText: String {
        let challengeCopy = challenge.map { item in
            "Weekly challenge: \(item.title). \(item.description) \(item.scriptureReference)"
        } ?? ""
        return "Recovery Newsletter\n\n\(verse.reference)\n\(verse.text)\n\n\(devotional.title)\n\(devotional.body)\n\n\(challengeCopy)"
    }

    func setICloudSyncEnabled(_ enabled: Bool) async {
        guard enabled else {
            UserDefaults.standard.set(false, forKey: "encryptedICloudSyncEnabled")
            iCloudStatusMessage = "iCloud sync is off. Local data remains on this device."
            return
        }
        do {
            if let cloudData = try EncryptedICloudSyncService.download() {
                let combined = Self.mergeUserData(currentUserData(modifiedAt: localModifiedAt), with: cloudData)
                apply(combined)
                try userDataStore.save(combined)
                try EncryptedICloudSyncService.upload(combined)
            } else {
                let snapshot = currentUserData()
                try userDataStore.save(snapshot)
                try EncryptedICloudSyncService.upload(snapshot)
                localModifiedAt = snapshot.modifiedAt
            }
            UserDefaults.standard.set(true, forKey: "encryptedICloudSyncEnabled")
            iCloudStatusMessage = "Encrypted iCloud sync is on"
        } catch {
            UserDefaults.standard.set(false, forKey: "encryptedICloudSyncEnabled")
            iCloudStatusMessage = error.localizedDescription
        }
    }

    func syncICloudNow() async {
        guard UserDefaults.standard.bool(forKey: "encryptedICloudSyncEnabled") else { return }
        await refreshFromICloud()
    }

    private func refreshFromICloud() async {
        do {
            guard let cloudData = try EncryptedICloudSyncService.download() else {
                let snapshot = currentUserData()
                try userDataStore.save(snapshot)
                try EncryptedICloudSyncService.upload(snapshot)
                localModifiedAt = snapshot.modifiedAt
                iCloudStatusMessage = "Encrypted iCloud sync is on"
                return
            }
            let combined = Self.mergeUserData(currentUserData(modifiedAt: localModifiedAt), with: cloudData)
            apply(combined)
            try userDataStore.save(combined)
            try EncryptedICloudSyncService.upload(combined)
            iCloudStatusMessage = "Encrypted iCloud sync is on"
        } catch {
            iCloudStatusMessage = error.localizedDescription
        }
    }

    private func apply(_ userData: DailyBreathUserData) {
        entries = userData.journalEntries.sorted { $0.createdAt > $1.createdAt }
        challengeCompletionDayKeys = userData.challengeCompletionDayKeys
        bibleAnnotations = userData.bibleAnnotations
        dailyHistory = userData.dailyHistory
        deletedJournalEntryIDs = userData.deletedJournalEntryIDs
        localModifiedAt = userData.modifiedAt
        updateCurrentChallengeProgress()
    }

    private func currentUserData(modifiedAt: Date = Date()) -> DailyBreathUserData {
        DailyBreathUserData(
            journalEntries: entries,
            challengeCompletionDayKeys: challengeCompletionDayKeys,
            bibleAnnotations: bibleAnnotations,
            dailyHistory: dailyHistory,
            deletedJournalEntryIDs: deletedJournalEntryIDs,
            modifiedAt: modifiedAt
        )
    }

    nonisolated static func mergeUserData(_ local: DailyBreathUserData, with cloud: DailyBreathUserData) -> DailyBreathUserData {
        var tombstones = local.deletedJournalEntryIDs
        for (id, date) in cloud.deletedJournalEntryIDs where date > tombstones[id, default: .distantPast] {
            tombstones[id] = date
        }

        var journals = Dictionary(uniqueKeysWithValues: local.journalEntries.map { ($0.id, $0) })
        for entry in cloud.journalEntries where entry.updatedAt > (journals[entry.id]?.updatedAt ?? .distantPast) {
            journals[entry.id] = entry
        }
        for (id, deletionDate) in tombstones {
            if let entry = journals[id], deletionDate >= entry.updatedAt { journals.removeValue(forKey: id) }
        }

        var annotations = local.bibleAnnotations
        for (id, annotation) in cloud.bibleAnnotations where annotation.updatedAt > (annotations[id]?.updatedAt ?? .distantPast) {
            annotations[id] = annotation
        }

        var history = local.dailyHistory
        for (key, record) in cloud.dailyHistory where record.updatedAt > (history[key]?.updatedAt ?? .distantPast) {
            history[key] = record
        }

        var challengeKeys = local.challengeCompletionDayKeys
        for (id, keys) in cloud.challengeCompletionDayKeys {
            challengeKeys[id] = Array(Set(challengeKeys[id, default: []]).union(keys)).sorted()
        }

        return DailyBreathUserData(
            journalEntries: journals.values.sorted { $0.createdAt > $1.createdAt },
            challengeCompletionDayKeys: challengeKeys,
            bibleAnnotations: annotations,
            dailyHistory: history,
            deletedJournalEntryIDs: tombstones,
            modifiedAt: Date()
        )
    }

    private func updateCurrentChallengeProgress() {
        guard let challenge else {
            challengeCompletedDayKeys = []
            return
        }
        challengeCompletedDayKeys = challengeCompletionDayKeys[challenge.id, default: []].sorted()
    }

    private func persistUserData(syncToICloud: Bool = true) {
        let userData = currentUserData()
        do {
            try userDataStore.save(userData)
            localModifiedAt = userData.modifiedAt
            if syncToICloud, UserDefaults.standard.bool(forKey: "encryptedICloudSyncEnabled") {
                do {
                    let synchronizedData: DailyBreathUserData
                    if let cloudData = try EncryptedICloudSyncService.download() {
                        synchronizedData = Self.mergeUserData(userData, with: cloudData)
                        apply(synchronizedData)
                        try userDataStore.save(synchronizedData)
                    } else {
                        synchronizedData = userData
                    }
                    try EncryptedICloudSyncService.upload(synchronizedData)
                    iCloudStatusMessage = "Encrypted iCloud sync is on"
                } catch {
                    iCloudStatusMessage = error.localizedDescription
                }
            }
        } catch {
            statusMessage = "Local data could not be saved"
        }
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
