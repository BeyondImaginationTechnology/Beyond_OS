import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @Environment(\.scenePhase) private var scenePhase
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id
    @AppStorage("devotionalReadDayKeys") private var devotionalReadDayKeys = ""
    @AppStorage("completedBreathDayKeys") private var completedBreathDayKeys = ""
    @AppStorage("dailyReminderEnabled") private var reminderEnabled = false
    @AppStorage("dailyReminderHour") private var reminderHour = 8
    @AppStorage("dailyReminderMinute") private var reminderMinute = 0

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var selectedTradition: FaithTradition {
        FaithTradition(rawValue: traditionID) ?? .bible
    }

    private var todayVerse: Verse {
        store.dailyVerse(for: selectedTradition)
    }

    private var todayDevotional: Devotional {
        store.dailyDevotional(for: selectedTradition)
    }

    private var todayKey: String {
        Self.dayFormatter.string(from: Date())
    }

    private var didReadDevotionalToday: Bool {
        devotionalReadDayKeys.split(separator: ",").contains(Substring(todayKey))
    }

    private var didBreatheToday: Bool {
        completedBreathDayKeys.split(separator: ",").contains(Substring(todayKey))
    }

    private var didReflectToday: Bool {
        store.entries.contains { Calendar.current.isDateInToday($0.createdAt) }
    }

    private var dailyProgressCount: Int {
        [true, didReadDevotionalToday, didBreatheToday, didReflectToday].filter(\.self).count
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                themePicker
                dailyRhythmCard
                reminderCard
                traditionPicker
                verseCard
                devotionalCard
                recoveryNewsletterCard
                journalCard
                quickActions
            }
            .padding()
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle("Today")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            ToolbarItem(placement: .topBarTrailing) {
                NavigationLink { SettingsAboutView() } label: {
                    Label("Settings", systemImage: "gearshape.fill")
                }
            }
        }
        .refreshable { await store.refreshToday() }
        .onChange(of: scenePhase) { _, phase in
            guard phase == .active else { return }
            Task {
                await store.syncICloudNow()
                await store.refreshToday()
            }
        }
        .onReceive(NotificationCenter.default.publisher(for: .NSCalendarDayChanged)) { _ in
            Task { await store.refreshToday() }
        }
    }

    private var dailyRhythmCard: some View {
        VStack(alignment: .leading, spacing: 14) {
            HStack {
                Label("Today's Rhythm", systemImage: "checklist.checked")
                    .font(.headline)
                Spacer()
                Text("\(dailyProgressCount) of 4")
                    .font(.caption.bold())
                    .foregroundStyle(selectedTheme.primary)
            }
            HStack(spacing: 8) {
                RhythmPill(title: "Read", isComplete: true, theme: selectedTheme)
                RhythmPill(title: "Study", isComplete: didReadDevotionalToday, theme: selectedTheme)
                RhythmPill(title: "Breathe", isComplete: didBreatheToday, theme: selectedTheme)
                RhythmPill(title: "Reflect", isComplete: didReflectToday, theme: selectedTheme)
            }
            Text("Small faithful steps count. Come back tomorrow, not because you broke a streak, but because peace is worth returning to.")
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .padding(16)
        .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
    }

    private var reminderCard: some View {
        NavigationLink {
            ReminderSettingsView()
        } label: {
            HStack(spacing: 14) {
                Image(systemName: reminderEnabled ? "bell.badge.fill" : "bell.fill")
                    .font(.title2)
                    .foregroundStyle(selectedTheme.accent)
                    .frame(width: 34)
                VStack(alignment: .leading, spacing: 5) {
                    Text("Daily Reminder")
                        .font(.headline)
                    Text(reminderEnabled ? "Scheduled for \(formattedReminderTime)" : "Set a gentle nudge to return tomorrow")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                        .lineLimit(2)
                }
                Spacer()
                Image(systemName: "chevron.right")
                    .font(.footnote.weight(.bold))
                    .foregroundStyle(.tertiary)
            }
            .padding(16)
            .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
        }
        .buttonStyle(.plain)
    }

    private var themePicker: some View {
        Menu {
            ForEach(DailyBreathTheme.allCases) { theme in
                Button {
                    selectedThemeID = theme.id
                } label: {
                    Label(theme.name, systemImage: theme.symbolName)
                }
            }
        } label: {
            Label(selectedTheme.name, systemImage: selectedTheme.symbolName)
                .font(.caption.bold())
                .padding(.horizontal, 12)
                .padding(.vertical, 8)
                .background(.background.opacity(0.86), in: Capsule())
        }
        .tint(selectedTheme.primary)
    }

    private var traditionPicker: some View {
        Picker("Verse tradition", selection: $traditionID) {
            ForEach(FaithTradition.allCases) { tradition in
                Label(tradition.name, systemImage: tradition.symbolName).tag(tradition.id)
            }
        }
        .pickerStyle(.segmented)
        .onChange(of: traditionID) { _, value in
            let tradition = FaithTradition(rawValue: value) ?? .bible
            selectedThemeID = DailyBreathTheme.recommended(for: tradition).id
            store.publishSelectedFaithContent()
        }
    }

    private var verseCard: some View {
        VStack(alignment: .leading, spacing: 18) {
            Label("\(selectedTradition.dailyReadingName) of the Day", systemImage: selectedTradition.symbolName)
                .font(.caption.bold())
                .tracking(1.4)
                .foregroundStyle(selectedTheme.accent)
            Text("\"\(todayVerse.text)\"")
                .font(.system(size: 36, weight: .semibold, design: .serif))
                .foregroundStyle(.white)
                .fixedSize(horizontal: false, vertical: true)
            Text(todayVerse.reference)
                .font(.headline.weight(.black))
                .foregroundStyle(selectedTheme.accent)
            Divider().overlay(.white.opacity(0.22))
            Text(todayVerse.reflection)
                .font(.body)
                .foregroundStyle(.white.opacity(0.82))
            HStack(spacing: 10) {
                NavigationLink {
                    VerseDetailView(verse: todayVerse, tradition: selectedTradition)
                } label: {
                    Label("Open", systemImage: "book.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)
                .tint(.white)
            }
            .controlSize(.large)
        }
        .padding(24)
        .background(
            LinearGradient(colors: [selectedTheme.primary, selectedTheme.secondary], startPoint: .topLeading, endPoint: .bottomTrailing),
            in: RoundedRectangle(cornerRadius: 26)
        )
    }

    private var devotionalCard: some View {
        NavigationLink {
            DevotionalDetailView(devotional: todayDevotional, tradition: selectedTradition)
        } label: {
            HStack(alignment: .top, spacing: 14) {
                VStack(alignment: .leading, spacing: 10) {
                    Text("TODAY'S \(selectedTradition.devotionalName.uppercased())")
                        .font(.caption.bold())
                        .tracking(1.6)
                        .foregroundStyle(selectedTheme.primary)
                    Text(todayDevotional.title)
                        .font(.title2.weight(.bold))
                    Text(todayDevotional.excerpt)
                        .foregroundStyle(.secondary)
                    Label("\(todayDevotional.scripture) · \(todayDevotional.minutes) minute read", systemImage: "clock.fill")
                        .font(.caption.bold())
                        .foregroundStyle(selectedTheme.accent)
                }
                Spacer(minLength: 8)
                VStack(spacing: 8) {
                    FaithGuidePortrait(tradition: selectedTradition, width: 54, height: 64, cornerRadius: 11)
                    Image(systemName: "chevron.right")
                        .font(.footnote.weight(.bold))
                        .foregroundStyle(.tertiary)
                }
            }
            .padding(20)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(.background, in: RoundedRectangle(cornerRadius: 22))
        }
        .buttonStyle(.plain)
    }

    private var journalCard: some View {
        NavigationLink {
            JournalView()
        } label: {
            HStack(spacing: 14) {
                Image(systemName: "square.and.pencil")
                    .font(.title2)
                    .foregroundStyle(selectedTheme.accent)
                    .frame(width: 34)
                VStack(alignment: .leading, spacing: 5) {
                    Text("Reflection Journal")
                        .font(.headline)
                    Text(store.entries.first?.text ?? DailyBreathStore.promptOfTheDay())
                        .font(.caption)
                        .foregroundStyle(.secondary)
                        .lineLimit(2)
                }
                Spacer()
                Image(systemName: "chevron.right")
                    .font(.footnote.weight(.bold))
                    .foregroundStyle(.tertiary)
            }
            .padding(16)
            .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
        }
        .buttonStyle(.plain)
    }

    private var recoveryNewsletterCard: some View {
        NavigationLink {
            RecoveryNewsletterView()
        } label: {
            HStack(alignment: .top, spacing: 14) {
                Image(systemName: "newspaper.fill")
                    .font(.title2)
                    .foregroundStyle(selectedTheme.accent)
                    .frame(width: 44, height: 44)
                    .background(selectedTheme.accent.opacity(0.12), in: RoundedRectangle(cornerRadius: 8))
                VStack(alignment: .leading, spacing: 6) {
                    Text("RECOVERY NEWSLETTER")
                        .font(.caption.bold())
                        .tracking(1.4)
                        .foregroundStyle(selectedTheme.primary)
                    Text("One verse, one reflection, and this week’s recovery practice.")
                        .font(.headline)
                    Text("Automatically refreshed with today’s Daily Breath content.")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Spacer()
                Image(systemName: "chevron.right")
                    .foregroundStyle(.tertiary)
            }
            .padding(16)
            .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
        }
        .buttonStyle(.plain)
    }

    private var quickActions: some View {
        LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
            NavigationLink {
                ScriptureLibraryView()
            } label: {
                QuickAction(title: selectedTradition.libraryName, subtitle: "Read and reflect", systemImage: "book.closed.fill")
            }
            NavigationLink {
                BreatheView()
            } label: {
                QuickAction(title: "Breath of the Day", subtitle: BreathPattern.breathOfTheDay().title, systemImage: "wind")
            }
            NavigationLink {
                PrayerPracticesView(tradition: selectedTradition)
            } label: {
                QuickAction(title: selectedTradition.prayerCollectionName, subtitle: "Guidance and healing", systemImage: "hands.sparkles.fill")
            }
            NavigationLink {
                WeeklyChallengeView()
            } label: {
                QuickAction(title: "Weekly Challenge", subtitle: store.challenge?.title ?? "Faith in action", systemImage: "calendar.badge.checkmark")
            }
            NavigationLink {
                AcademyView()
            } label: {
                QuickAction(
                    title: selectedTradition.academyName,
                    subtitle: "Learn with \(selectedTradition.guideName)",
                    systemImage: "graduationcap.fill",
                    guide: selectedTradition
                )
            }
            NavigationLink {
                JournalView()
            } label: {
                QuickAction(title: "One Sentence", subtitle: "Reflect today", systemImage: "pencil.and.list.clipboard")
            }
            NavigationLink {
                ReminderSettingsView()
            } label: {
                QuickAction(title: "Reminder", subtitle: reminderEnabled ? formattedReminderTime : "Daily nudge", systemImage: "bell.badge.fill")
            }
        }
        .buttonStyle(.plain)
    }

    private var formattedReminderTime: String {
        let date = Calendar.current.date(from: DateComponents(hour: reminderHour, minute: reminderMinute)) ?? Date()
        return date.formatted(date: .omitted, time: .shortened)
    }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

private struct RhythmPill: View {
    let title: String
    let isComplete: Bool
    let theme: DailyBreathTheme

    var body: some View {
        VStack(spacing: 5) {
            Image(systemName: isComplete ? "checkmark.circle.fill" : "circle")
                .font(.subheadline)
            Text(title)
                .font(.caption2.bold())
                .lineLimit(1)
                .minimumScaleFactor(0.75)
        }
        .foregroundStyle(isComplete ? theme.primary : .secondary)
            .frame(maxWidth: .infinity)
            .padding(.vertical, 10)
            .background(theme.primary.opacity(isComplete ? 0.13 : 0.05), in: RoundedRectangle(cornerRadius: 8))
            .accessibilityLabel("\(title) \(isComplete ? "complete" : "not complete")")
    }
}

private struct QuickAction: View {
    let title: String
    let subtitle: String
    let systemImage: String
    var guide: FaithTradition? = nil
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 9) {
            if let guide {
                FaithGuidePortrait(tradition: guide, width: 44, height: 50, cornerRadius: 9)
            } else {
                Image(systemName: systemImage)
                    .font(.title2)
                    .foregroundStyle(selectedTheme.accent)
            }
            Text(title)
                .font(.headline)
            Text(subtitle)
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .padding()
        .frame(maxWidth: .infinity, minHeight: 126, alignment: .topLeading)
        .background(.background, in: RoundedRectangle(cornerRadius: 20))
    }
}

private struct VerseDetailView: View {
    let verse: Verse
    let tradition: FaithTradition
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                HStack(spacing: 12) {
                    FaithGuidePortrait(tradition: tradition, width: 56, height: 68, cornerRadius: 12)
                    VStack(alignment: .leading, spacing: 4) {
                        Label("\(tradition.dailyReadingName) of the Day", systemImage: tradition.symbolName)
                            .font(.caption.bold())
                            .tracking(1.4)
                            .foregroundStyle(selectedTheme.accent)
                        Text("Read with \(tradition.guideName)")
                            .font(.headline)
                    }
                }
                Text("\"\(verse.text)\"")
                    .font(.system(size: 38, weight: .semibold, design: .serif))
                    .fixedSize(horizontal: false, vertical: true)
                Text(verse.reference)
                    .font(.title3.weight(.black))
                    .foregroundStyle(selectedTheme.primary)
                Divider()
                Text(verse.reflection)
                    .font(.body)
                    .foregroundStyle(.secondary)
                NavigationLink {
                    ScriptureLibraryView()
                } label: {
                    Label("Open \(tradition.libraryName)", systemImage: "book.closed.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(selectedTheme.primary)
                .controlSize(.large)
            }
            .padding()
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle(verse.reference)
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct DevotionalDetailView: View {
    @EnvironmentObject private var store: DailyBreathStore
    let devotional: Devotional
    let tradition: FaithTradition
    @AppStorage("devotionalReadDayKeys") private var devotionalReadDayKeys = ""
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var todayKey: String {
        Self.dayFormatter.string(from: Date())
    }

    private var isReadToday: Bool {
        devotionalReadDayKeys.split(separator: ",").contains(Substring(todayKey))
    }

    var body: some View {
        List {
            Section {
                HStack(alignment: .top, spacing: 14) {
                    FaithGuidePortrait(tradition: tradition, width: 76, height: 94, cornerRadius: 15)
                    VStack(alignment: .leading, spacing: 8) {
                        Text("WITH \(tradition.guideName.uppercased()) · \(devotional.scripture)")
                            .font(.caption2.bold())
                            .foregroundStyle(selectedTheme.accent)
                        Text(devotional.title)
                            .font(.largeTitle.weight(.black))
                            .minimumScaleFactor(0.75)
                        Label("\(devotional.minutes) minute read", systemImage: "clock.fill")
                            .font(.caption.bold())
                            .foregroundStyle(.secondary)
                    }
                }
                .padding(.vertical, 8)
            }

            Section("Reflection") {
                Text(devotional.body)
                    .font(.body)
            }

            Section(tradition.prayerName) {
                Text(devotional.prayer)
                    .font(.body)
                Button {
                    markRead()
                    store.prepareJournalReflection(
                        prompt: "\(tradition.prayerName) from \(devotional.title)",
                        text: devotional.prayer,
                        mood: "Hopeful"
                    )
                } label: {
                    Label("Save This \(tradition.prayerName)", systemImage: "hands.sparkles.fill")
                }
            }

            Section("Practice") {
                Text(devotional.practice)
                    .font(.body)
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Reflect in Journal", systemImage: "square.and.pencil")
                }
            }

            Section {
                Button {
                    markRead()
                } label: {
                    Label(isReadToday ? "Read Today" : "Mark as Read", systemImage: isReadToday ? "checkmark.circle.fill" : "circle")
                }
                .foregroundStyle(selectedTheme.primary)
            }
        }
        .navigationTitle(tradition.devotionalName)
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }

    private func markRead() {
        var keys = devotionalReadDayKeys
            .split(separator: ",")
            .map(String.init)
            .filter { !$0.isEmpty }
        if !keys.contains(todayKey) {
            keys.append(todayKey)
        }
        devotionalReadDayKeys = keys.suffix(366).joined(separator: ",")
    }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

private struct RecoveryNewsletterView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var selectedTradition: FaithTradition {
        FaithTradition(rawValue: traditionID) ?? .bible
    }

    private var dailyVerse: Verse { store.dailyVerse(for: selectedTradition) }
    private var dailyDevotional: Devotional {
        store.dailyDevotional(for: selectedTradition)
    }
    private var dailyChallenge: RecoveryChallenge? { store.dailyChallenge(for: selectedTradition) }

    var body: some View {
        List {
            Section {
                HStack(alignment: .top, spacing: 14) {
                    FaithGuidePortrait(tradition: selectedTradition, width: 76, height: 94, cornerRadius: 15)
                    VStack(alignment: .leading, spacing: 8) {
                        Label("Recovery Newsletter with \(selectedTradition.guideName)", systemImage: "newspaper.fill")
                            .font(.caption.bold())
                            .foregroundStyle(selectedTheme.accent)
                        Text(selectedTradition.newsletterTagline)
                            .font(.title.weight(.black))
                        Text(Date(), format: .dateTime.weekday(.wide).month(.wide).day().year())
                            .foregroundStyle(.secondary)
                    }
                }
                .padding(.vertical, 8)
            }

            Section("Today’s \(selectedTradition.dailyReadingName)") {
                Text("“\(dailyVerse.text)”")
                    .font(.system(.title3, design: .serif).weight(.semibold))
                Text(dailyVerse.reference)
                    .font(.headline)
                    .foregroundStyle(selectedTheme.primary)
                Text(dailyVerse.reflection)
                    .foregroundStyle(.secondary)
            }

            Section(dailyDevotional.title) {
                Text(dailyDevotional.body)
                Label(dailyDevotional.scripture, systemImage: "book.closed.fill")
                    .foregroundStyle(selectedTheme.primary)
            }

            Section(selectedTradition.prayerName) {
                Text(dailyDevotional.prayer)
            }

            Section("Practice") {
                Text(dailyDevotional.practice)
            }

            if let challenge = dailyChallenge {
                Section("This Week: \(challenge.title)") {
                    Text(challenge.description)
                    ForEach(challenge.steps, id: \.self) { step in
                        Label(step, systemImage: "checkmark.circle")
                    }
                    ProgressView(
                        value: Double(store.challengeProgressCount),
                        total: Double(max(challenge.targetCount, 1))
                    )
                    Text("\(store.challengeProgressCount) of \(challenge.targetCount) days complete")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }

            Section("Support") {
                NavigationLink { RecoverySupportView() } label: {
                    Label("Professional and crisis resources", systemImage: "lifepreserver")
                }
                Text("Daily Breath is not medical care or emergency support.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Section {
                ShareLink(item: store.recoveryNewsletterShareText) {
                    Label("Share Recovery Newsletter", systemImage: "square.and.arrow.up")
                }
            }
        }
        .navigationTitle("Recovery Newsletter")
        .navigationBarTitleDisplayMode(.inline)
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }
}

private struct PrayerPracticesView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    let tradition: FaithTradition

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                HStack(spacing: 14) {
                    FaithGuidePortrait(tradition: tradition, width: 64, height: 78, cornerRadius: 13)
                    VStack(alignment: .leading, spacing: 4) {
                        Text("Practice with \(tradition.guideName)")
                            .font(.headline)
                        Text("Choose a guided \(tradition.prayerName.lowercased()) for this moment.")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                }
            }
            Section {
                ForEach(store.practices.filter { $0.title != "Peace Breath" && $0.title != "Weekly Challenge" }) { practice in
                    NavigationLink {
                        PrayerPracticeDetailView(practice: practice, tradition: tradition)
                    } label: {
                        Label {
                            VStack(alignment: .leading, spacing: 3) {
                                Text(practice.title)
                                Text(practice.subtitle)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                        } icon: {
                            Image(systemName: practice.systemImage)
                                .foregroundStyle(selectedTheme.accent)
                        }
                    }
                }
            }
        }
        .navigationTitle(tradition.prayerCollectionName)
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }
}

private struct PrayerPracticeDetailView: View {
    let practice: PrayerPractice
    let tradition: FaithTradition
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                HStack(alignment: .top, spacing: 14) {
                    FaithGuidePortrait(tradition: tradition, width: 76, height: 94, cornerRadius: 15)
                    VStack(alignment: .leading, spacing: 8) {
                        Label("With \(tradition.guideName)", systemImage: practice.systemImage)
                            .font(.caption.bold())
                            .foregroundStyle(selectedTheme.accent)
                        Text(practice.title)
                            .font(.largeTitle.weight(.black))
                            .minimumScaleFactor(0.75)
                        Text(practice.subtitle)
                            .foregroundStyle(.secondary)
                    }
                }
                .padding(.vertical, 8)
            }

            Section(tradition.prayerName) {
                Text(prayerText)
            }

            Section("Next Step") {
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Write a Reflection", systemImage: "square.and.pencil")
                }
            }
        }
        .navigationTitle(practice.title)
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }

    private var prayerText: String {
        switch (tradition, practice.title) {
        case (.bible, "Guidance Prayer"):
            "Lord, give me wisdom for the decision in front of me. Help me listen before I move and choose what brings peace, truth, and love."
        case (.bible, "Gratitude Reset"):
            "Lord, open my eyes to what is good. Teach me to receive today with humility and respond with generosity."
        case (.torah, "Guidance Prayer"):
            "Source of wisdom, help me listen honestly and choose the path of truth, responsibility, and life. Guide my next step and help me seek wise counsel."
        case (.torah, "Gratitude Reset"):
            "Holy One, help me notice the gifts, people, and responsibilities entrusted to me today. May gratitude lead me toward generosity and acts of lovingkindness."
        case (.quran, "Guidance Prayer"):
            "Allah, guide me to what is right, grant me clear judgment, and keep me away from what causes harm. Help me trust You and seek wise counsel. Amin."
        case (.quran, "Gratitude Reset"):
            "Alhamdulillah for every blessing I recognize and every blessing I overlook. Allah, make me grateful in heart, word, and action. Amin."
        case (.bible, _):
            "Lord, meet me in this practice and shape my next step with grace."
        case (.torah, _):
            "Holy One, meet me in this practice and guide my next step toward truth, repair, and peace."
        case (.quran, _):
            "Allah, meet me with mercy, guide my next step, and strengthen me to do what is right. Amin."
        }
    }
}

private struct WeeklyChallengeView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id

    private var selectedTradition: FaithTradition {
        FaithTradition(rawValue: traditionID) ?? .bible
    }

    private var challenge: RecoveryChallenge? { store.dailyChallenge(for: selectedTradition) }

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                HStack(alignment: .top, spacing: 14) {
                    FaithGuidePortrait(tradition: selectedTradition, width: 76, height: 94, cornerRadius: 15)
                    VStack(alignment: .leading, spacing: 8) {
                        Label("Weekly challenge with \(selectedTradition.guideName)", systemImage: "calendar.badge.checkmark")
                            .font(.caption.bold())
                            .foregroundStyle(selectedTheme.accent)
                        Text(challenge?.title ?? "Faith in Action")
                            .font(.largeTitle.weight(.black))
                            .minimumScaleFactor(0.75)
                        Text(challenge?.description ?? "Choose one quiet act of faith this week and make it concrete.")
                            .foregroundStyle(.secondary)
                        if let challenge {
                            Text(challenge.scriptureReference)
                                .font(.caption.bold())
                                .foregroundStyle(selectedTheme.accent)
                        }
                    }
                }
                .padding(.vertical, 8)
            }

            Section("This Week") {
                if let challenge {
                    ForEach(challenge.steps, id: \.self) { step in
                        Label(step, systemImage: "checkmark.circle")
                    }
                } else {
                    Label("Encourage someone who needs courage.", systemImage: "message.fill")
                    Label("Give without needing credit.", systemImage: "gift.fill")
                    Label("Return to stillness before reacting.", systemImage: "pause.circle.fill")
                }
            }

            Section("Track It") {
                if let challenge {
                    ProgressView(
                        value: Double(store.challengeProgressCount),
                        total: Double(max(challenge.targetCount, 1))
                    )
                    Text("\(store.challengeProgressCount) of \(challenge.targetCount) days complete")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Button {
                        store.completeChallengeToday()
                    } label: {
                        Label(
                            store.isChallengeCompleteToday ? "Completed Today" : "Mark Today Complete",
                            systemImage: store.isChallengeCompleteToday ? "checkmark.circle.fill" : "circle"
                        )
                    }
                    .disabled(store.isChallengeCompleteToday)
                }
                NavigationLink { RecoverySupportView() } label: {
                    Label("Recovery Support Resources", systemImage: "lifepreserver")
                }
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Record Your Challenge", systemImage: "square.and.pencil")
                }
            }
        }
        .navigationTitle("Weekly Challenge")
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }
}
