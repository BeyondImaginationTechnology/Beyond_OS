import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("devotionalReadDayKeys") private var devotionalReadDayKeys = ""
    @AppStorage("completedBreathDayKeys") private var completedBreathDayKeys = ""
    @AppStorage("dailyReminderEnabled") private var reminderEnabled = false
    @AppStorage("dailyReminderHour") private var reminderHour = 8
    @AppStorage("dailyReminderMinute") private var reminderMinute = 0

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
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

    private var dailyProgressCount: Int {
        [true, didReadDevotionalToday, didBreatheToday, !store.entries.isEmpty].filter(\.self).count
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                themePicker
                dailyRhythmCard
                reminderCard
                verseCard
                devotionalCard
                journalCard
                quickActions
            }
            .padding()
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle("Today")
        .navigationBarTitleDisplayMode(.inline)
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
                RhythmPill(title: "Devote", isComplete: didReadDevotionalToday, theme: selectedTheme)
                RhythmPill(title: "Breathe", isComplete: didBreatheToday, theme: selectedTheme)
                RhythmPill(title: "Reflect", isComplete: !store.entries.isEmpty, theme: selectedTheme)
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

    private var verseCard: some View {
        VStack(alignment: .leading, spacing: 18) {
            Label("Verse of the Day", systemImage: "sun.max.fill")
                .font(.caption.bold())
                .tracking(1.4)
                .foregroundStyle(selectedTheme.accent)
            Text("\"\(store.verse.text)\"")
                .font(.system(size: 36, weight: .semibold, design: .serif))
                .foregroundStyle(.white)
                .fixedSize(horizontal: false, vertical: true)
            Text(store.verse.reference)
                .font(.headline.weight(.black))
                .foregroundStyle(selectedTheme.accent)
            Divider().overlay(.white.opacity(0.22))
            Text(store.verse.reflection)
                .font(.body)
                .foregroundStyle(.white.opacity(0.82))
            HStack(spacing: 10) {
                Button { store.speakVerse() } label: {
                    Label("Listen", systemImage: "speaker.wave.2.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(selectedTheme.accent)

                NavigationLink {
                    VerseDetailView(verse: store.verse)
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
            DevotionalDetailView(devotional: store.devotional)
        } label: {
            HStack(alignment: .top, spacing: 14) {
                VStack(alignment: .leading, spacing: 10) {
                    Text("TODAY'S DEVOTIONAL")
                        .font(.caption.bold())
                        .tracking(1.6)
                        .foregroundStyle(selectedTheme.primary)
                    Text(store.devotional.title)
                        .font(.title2.weight(.bold))
                    Text(store.devotional.excerpt)
                        .foregroundStyle(.secondary)
                    Label("\(store.devotional.scripture) · \(store.devotional.minutes) minute read", systemImage: "clock.fill")
                        .font(.caption.bold())
                        .foregroundStyle(selectedTheme.accent)
                }
                Spacer(minLength: 8)
                Image(systemName: "chevron.right")
                    .font(.footnote.weight(.bold))
                    .foregroundStyle(.tertiary)
                    .padding(.top, 6)
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

    private var quickActions: some View {
        LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
            NavigationLink {
                BibleView()
            } label: {
                QuickAction(title: "Bible Library", subtitle: "Read and reflect", systemImage: "book.closed.fill")
            }
            NavigationLink {
                BreatheView()
            } label: {
                QuickAction(title: "Breath of the Day", subtitle: BreathPattern.breathOfTheDay().title, systemImage: "wind")
            }
            NavigationLink {
                PrayerPracticesView()
            } label: {
                QuickAction(title: "Specific Prayers", subtitle: "Guidance and healing", systemImage: "hands.sparkles.fill")
            }
            NavigationLink {
                WeeklyChallengeView()
            } label: {
                QuickAction(title: "Weekly Challenge", subtitle: "Faith in action", systemImage: "calendar.badge.checkmark")
            }
            NavigationLink {
                AcademyView()
            } label: {
                QuickAction(title: "Bible Academy", subtitle: "Starter lessons", systemImage: "graduationcap.fill")
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
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 9) {
            Image(systemName: systemImage)
                .font(.title2)
                .foregroundStyle(selectedTheme.accent)
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
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                Label("Verse of the Day", systemImage: "sun.max.fill")
                    .font(.caption.bold())
                    .tracking(1.4)
                    .foregroundStyle(selectedTheme.accent)
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
                    BibleView()
                } label: {
                    Label("Open Bible Library", systemImage: "book.closed.fill")
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
                VStack(alignment: .leading, spacing: 12) {
                    Text(devotional.scripture)
                        .font(.caption.bold())
                        .foregroundStyle(selectedTheme.accent)
                    Text(devotional.title)
                        .font(.largeTitle.weight(.black))
                    Label("\(devotional.minutes) minute read", systemImage: "clock.fill")
                        .font(.caption.bold())
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }

            Section("Reflection") {
                Text(devotional.body)
                    .font(.body)
            }

            Section("Prayer") {
                Text(devotional.prayer)
                    .font(.body)
                Button {
                    markRead()
                    store.prepareJournalReflection(
                        prompt: "Prayer from \(devotional.title)",
                        text: devotional.prayer,
                        mood: "Hopeful"
                    )
                } label: {
                    Label("Pray This in Journal", systemImage: "hands.sparkles.fill")
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
        .navigationTitle("Devotional")
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
        devotionalReadDayKeys = keys.suffix(14).joined(separator: ",")
    }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

private struct PrayerPracticesView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                ForEach(store.practices.filter { $0.title != "Peace Breath" && $0.title != "Weekly Challenge" }) { practice in
                    NavigationLink {
                        PrayerPracticeDetailView(practice: practice)
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
        .navigationTitle("Specific Prayers")
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }
}

private struct PrayerPracticeDetailView: View {
    let practice: PrayerPractice
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Image(systemName: practice.systemImage)
                        .font(.largeTitle)
                        .foregroundStyle(selectedTheme.accent)
                    Text(practice.title)
                        .font(.largeTitle.weight(.black))
                    Text(practice.subtitle)
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }

            Section("Prayer") {
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
        switch practice.title {
        case "Guidance Prayer":
            return "Lord, give me wisdom for the decision in front of me. Help me listen before I move and choose what brings peace, truth, and love."
        case "Gratitude Reset":
            return "Lord, open my eyes to what is good. Teach me to receive today with humility and respond with generosity."
        default:
            return "Lord, meet me in this practice and shape my next step with grace."
        }
    }
}

private struct WeeklyChallengeView: View {
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Image(systemName: "calendar.badge.checkmark")
                        .font(.largeTitle)
                        .foregroundStyle(selectedTheme.accent)
                    Text("Faith in Action")
                        .font(.largeTitle.weight(.black))
                    Text("Choose one quiet act of faith this week and make it concrete.")
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }

            Section("This Week") {
                Label("Encourage someone who needs courage.", systemImage: "message.fill")
                Label("Give without needing credit.", systemImage: "gift.fill")
                Label("Return to stillness before reacting.", systemImage: "pause.circle.fill")
            }

            Section("Track It") {
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
