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
    @Published private(set) var bibleScriptureLibrary = SacredTextLibrary(tradition: .bible, translation: "World English Bible", books: [])
    @Published private(set) var torahScriptureLibrary = SacredTextLibrary(tradition: .torah, translation: "World English Bible — Hebrew Scriptures", books: [])
    @Published private(set) var quranScriptureLibrary = SacredTextLibrary(tradition: .quran, translation: "Pickthall English Meaning", books: [])
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
            title: "Joining the Christian Faith with Chris",
            subtitle: "A starter journey for following Jesus through Scripture, prayer, baptism, and church community.",
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
                ),
                AcademyLesson(
                    id: 103,
                    title: "Exploring the Christian Path",
                    duration: "7 min",
                    scripture: "Romans 10:9-10",
                    summary: "Learn a starter path of trust in Jesus, prayer, Scripture, baptism, and church community.",
                    teaching: "Christian faith centers on trusting and following Jesus Christ. Begin with honest prayer, read one Gospel, and connect with a healthy local church where questions are welcome. Christians commonly mark entry into the faith through baptism, with preparation varying by tradition. Daily Breath can support learning and recovery, but a pastor, priest, or mature local community should guide sacramental and membership steps.",
                    practice: "Write what you believe and what you are unsure about. Read Mark 1 this week and identify a trustworthy local Christian community for a conversation.",
                    reflectionPrompt: "What draws me toward following Jesus, and what question do I want to explore honestly?",
                    checkPrompt: "Who should help guide baptism and church belonging?",
                    checkAnswer: "local church"
                ),
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
            title: "Christian Recovery with Chris",
            subtitle: "Grace-centered practices for cravings, support, prayer, and the next healthy step.",
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
                ),
                AcademyLesson(
                    id: 302,
                    title: "Cravings, Community, and Grace",
                    duration: "6 min",
                    scripture: "Galatians 6:2",
                    summary: "Interrupt isolation by carrying recovery with trusted people and practical support.",
                    teaching: "Recovery is not a test of whether you can carry everything alone. Galatians calls believers to carry one another’s burdens. Grace makes honesty possible, while wise support turns honesty into action. A pastor or trusted Christian community can walk with you, and licensed treatment or a recovery group may be essential. Asking for help is a faithful step, not a failure of faith.",
                    practice: "Choose one trusted person or recovery resource. Send a simple message that names what support you need today.",
                    reflectionPrompt: "What burden have I been trying to carry alone?",
                    checkPrompt: "According to Galatians 6:2, whose burdens should believers carry?",
                    checkAnswer: "one another's"
                )
            ]
        ),
        AcademyPath(
            id: 4,
            title: "Joining the Jewish Faith with Dovi",
            subtitle: "A respectful starter journey through Torah, mitzvot, prayer, community, and Jewish life.",
            systemImage: "star.circle.fill",
            lessons: [
                AcademyLesson(
                    id: 401,
                    title: "Begin with Shema",
                    duration: "5 min",
                    scripture: "Deuteronomy 6:4-5",
                    summary: "Start with Jewish faith’s call to listen, love God, and live attentively.",
                    teaching: "Shema means listen. Deuteronomy joins listening to love and daily action: faith is carried into ordinary choices, relationships, and habits. Dovi’s first invitation is simple—listen before reacting, remember that your life has sacred direction, and let one action embody what you are learning.",
                    practice: "Read Deuteronomy 6:4-5 slowly. Take one quiet breath after each line and name one loving action for today.",
                    reflectionPrompt: "What would attentive listening change in my next decision?",
                    checkPrompt: "What does the word Shema invite you to do?",
                    checkAnswer: "listen"
                ),
                AcademyLesson(
                    id: 402,
                    title: "Exploring Jewish Life Respectfully",
                    duration: "7 min",
                    scripture: "Ruth 1:16",
                    summary: "Learn a respectful first path for community, study, and possible conversion.",
                    teaching: "Jewish life is lived in community, not assembled from an app. Begin by learning, visiting a welcoming synagogue, and speaking with a rabbi about practice, belonging, and conversion if that is your sincere direction. Different Jewish movements guide conversion differently. Daily Breath can support reflection, but it cannot replace a rabbi, congregation, or formal process.",
                    practice: "Write down two questions, research a nearby welcoming congregation, and plan a respectful conversation with a rabbi.",
                    reflectionPrompt: "What draws me toward Jewish life, and what am I ready to learn with humility?",
                    checkPrompt: "Who should guide a formal Jewish conversion path?",
                    checkAnswer: "rabbi"
                )
            ],
            tradition: .torah,
            guideName: "Dovi",
            guideAssetName: "DoviGuide"
        ),
        AcademyPath(
            id: 5,
            title: "Jewish Recovery with Dovi",
            subtitle: "Teshuvah, community, honest repair, and daily choices for recovery.",
            systemImage: "arrow.uturn.backward.circle.fill",
            lessons: [
                AcademyLesson(
                    id: 501,
                    title: "Teshuvah: Return, Don’t Disappear",
                    duration: "6 min",
                    scripture: "Psalm 51:12",
                    summary: "Approach a setback as a call to return, repair, and reconnect.",
                    teaching: "Teshuvah is often translated as repentance, but its movement is return. Recovery grows when shame no longer drives isolation. Name what happened truthfully, return to God and your supports, repair what you safely can, and take the next concrete step. A lapse is serious, but it does not erase your capacity to return.",
                    practice: "Use four lines: what happened, who can support me, what needs repair, and what is my next safe action?",
                    reflectionPrompt: "Where can I choose return instead of hiding today?",
                    checkPrompt: "What movement is at the heart of teshuvah?",
                    checkAnswer: "return"
                ),
                AcademyLesson(
                    id: 502,
                    title: "Choose Life with Support",
                    duration: "6 min",
                    scripture: "Deuteronomy 30:19",
                    summary: "Build recovery around life-giving choices, community, and a concrete safety plan.",
                    teaching: "The Torah’s call to choose life can guide one decision at a time. Recovery becomes more durable when a life-giving choice is made easier before a difficult moment arrives. Trusted community, professional care, and peer support can stand alongside prayer and teshuvah. Prepare the next right choice by reducing access to harm and increasing access to people who know how to help.",
                    practice: "Write one safeguard, one person to contact, and one life-giving action you can take when risk rises.",
                    reflectionPrompt: "What practical choice would make the path toward life easier today?",
                    checkPrompt: "What does Deuteronomy 30:19 call you to choose?",
                    checkAnswer: "life"
                )
            ],
            tradition: .torah,
            guideName: "Dovi",
            guideAssetName: "DoviGuide"
        ),
        AcademyPath(
            id: 6,
            title: "Joining the Muslim Faith with Moe",
            subtitle: "A starter journey through tawhid, prayer, Quran, community, and becoming Muslim.",
            systemImage: "moon.stars.fill",
            lessons: [
                AcademyLesson(
                    id: 601,
                    title: "Begin with Intention",
                    duration: "5 min",
                    scripture: "Quran 1:1-7",
                    summary: "Begin with sincere intention, mercy, worship, and the straight path.",
                    teaching: "Islam begins with surrender to the One God and a sincere intention to seek what is right. Al-Fatiha centers praise, mercy, accountability, worship, help, and guidance. Moe’s invitation is to slow down, ask Allah for guidance, and let intention become action rather than pressure for instant perfection.",
                    practice: "Read Al-Fatiha in translation, pause for three breaths, and name one action that aligns with a sincere intention today.",
                    reflectionPrompt: "What guidance am I asking Allah for in this season?",
                    checkPrompt: "What should turn a sincere intention into lived faith?",
                    checkAnswer: "action"
                ),
                AcademyLesson(
                    id: 602,
                    title: "A Respectful Path to Islam",
                    duration: "7 min",
                    scripture: "Quran 2:256",
                    summary: "Learn the starter steps of belief, shahada, prayer, study, and community.",
                    teaching: "Becoming Muslim begins with sincere belief in Allah and acceptance of Muhammad as His messenger, expressed in the shahada. Learn its meaning rather than rushing the words. Connect with a trustworthy local mosque or imam, ask questions, begin learning salah, and build community. Daily Breath can help you reflect, but it does not replace qualified local guidance.",
                    practice: "Write your questions about belief and practice, then identify a welcoming mosque or imam for a conversation.",
                    reflectionPrompt: "What do I believe, and what questions do I want to explore honestly?",
                    checkPrompt: "Who can provide trustworthy local guidance as you explore Islam?",
                    checkAnswer: "imam"
                )
            ],
            tradition: .quran,
            guideName: "Moe",
            guideAssetName: "MoeGuide"
        ),
        AcademyPath(
            id: 7,
            title: "Muslim Recovery with Moe",
            subtitle: "Mercy, tawbah, prayer, support, and practical safeguards for recovery.",
            systemImage: "shield.lefthalf.filled",
            lessons: [
                AcademyLesson(
                    id: 701,
                    title: "Never Despair of Mercy",
                    duration: "6 min",
                    scripture: "Quran 39:53",
                    summary: "Meet shame with Allah’s mercy, honest repentance, and a supported next step.",
                    teaching: "Quran 39:53 warns against despairing of Allah’s mercy. Recovery still asks for honesty, safeguards, treatment when needed, and repair—but despair is not the guide. Tawbah turns you back toward Allah. Reach out early, remove immediate access to the harmful pattern, and choose the next safe action with support.",
                    practice: "Make a three-part plan: who I will contact, what access I will remove, and what healthy action I will take in the next ten minutes.",
                    reflectionPrompt: "What would returning to mercy change about my next recovery choice?",
                    checkPrompt: "What should a believer not despair of in Quran 39:53?",
                    checkAnswer: "mercy"
                ),
                AcademyLesson(
                    id: 702,
                    title: "Patience, Prayer, and Safeguards",
                    duration: "6 min",
                    scripture: "Quran 2:153",
                    summary: "Pair spiritual steadiness with people and safeguards that protect recovery.",
                    teaching: "The Quran teaches believers to seek help through patience and prayer. Patience is active steadiness, not silent isolation. Recovery may also require professional treatment, a peer group, an imam who understands the situation, and firm limits around access to harm. Spiritual practice and practical care can reinforce one another as you take the next safe step.",
                    practice: "Pray for steadiness, contact one trustworthy support, and remove one immediate pathway back to the harmful pattern.",
                    reflectionPrompt: "Which support or safeguard would strengthen my patience today?",
                    checkPrompt: "Quran 2:153 says to seek help through patience and what?",
                    checkAnswer: "prayer"
                )
            ],
            tradition: .quran,
            guideName: "Moe",
            guideAssetName: "MoeGuide"
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
        let quranTask = Task.detached(priority: .userInitiated) {
            SacredTextLibrary.loadPickthallQuran()
        }
        await refreshToday()
        bibleLibrary = await bibleTask.value
        bibleScriptureLibrary = SacredTextLibrary.bible(from: bibleLibrary)
        torahScriptureLibrary = SacredTextLibrary.torah(from: bibleLibrary)
        quranScriptureLibrary = await quranTask.value
    }

    func scriptureLibrary(for tradition: FaithTradition) -> SacredTextLibrary {
        switch tradition {
        case .bible: bibleScriptureLibrary
        case .torah: torahScriptureLibrary
        case .quran: quranScriptureLibrary
        }
    }

    func dailyVerse(for tradition: FaithTradition, date: Date = Date()) -> Verse {
        InterfaithDailyContent.verse(
            for: tradition,
            date: date,
            bibleVerse: verse,
            bible: bibleScriptureLibrary,
            torah: torahScriptureLibrary,
            quran: quranScriptureLibrary
        )
    }

    func refreshToday() async {
        isRefreshing = true
        defer { isRefreshing = false }
        let requestedDate = Date()
        let requestedDateKey = Self.dateKey(requestedDate)

        do {
            let today = try await apiClient.fetch(dateKey: requestedDateKey)
            verse = RecoveryContent.resolvedVerseOfTheDay(for: requestedDate, remoteVerse: today.verse)
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
        let tradition = FaithTradition(rawValue: UserDefaults.standard.string(forKey: "selectedFaithTradition") ?? "") ?? .bible
        let sharedVerse = dailyVerse(for: tradition)
        let sharedDevotional = InterfaithDailyContent.devotional(for: tradition, base: devotional, verse: sharedVerse)
        let challengeCopy = challenge.map { item in
            "Weekly challenge: \(item.title). \(item.description) \(item.scriptureReference)"
        } ?? ""
        return "Recovery Newsletter\n\n\(sharedVerse.reference)\n\(sharedVerse.text)\n\n\(sharedDevotional.title)\n\(sharedDevotional.body)\n\n\(tradition.prayerName)\n\(sharedDevotional.prayer)\n\n\(challengeCopy)"
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
