import SwiftUI

struct SettingsAboutView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("narrationRate") private var narrationRate = 0.38
    @AppStorage("narrationVoiceLanguage") private var narrationVoiceLanguage = "en-US"
    @AppStorage("encryptedICloudSyncEnabled") private var encryptedICloudSyncEnabled = false

    private var versionText: String {
        let version = Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "—"
        let build = Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String ?? "—"
        return "Version \(version) (\(build))"
    }

    var body: some View {
        List {
            Section("Daily Rhythm") {
                NavigationLink { ReminderSettingsView() } label: {
                    Label("Reminders", systemImage: "bell.badge")
                }
                NavigationLink { DailyHistoryView() } label: {
                    Label("Daily History", systemImage: "calendar")
                }
                NavigationLink { RecoverySupportView() } label: {
                    Label("Recovery Support", systemImage: "lifepreserver")
                }
            }

            Section("Appearance") {
                Picker("Theme", selection: $selectedThemeID) {
                    ForEach(DailyBreathTheme.allCases) { theme in
                        Label(theme.name, systemImage: theme.symbolName).tag(theme.id)
                    }
                }
            }

            Section("Narration") {
                Picker("Voice", selection: $narrationVoiceLanguage) {
                    Text("English (United States)").tag("en-US")
                    Text("English (Canada)").tag("en-CA")
                    Text("English (United Kingdom)").tag("en-GB")
                }
                VStack(alignment: .leading, spacing: 8) {
                    HStack {
                        Text("Speaking Speed")
                        Spacer()
                        Text(narrationRate < 0.36 ? "Gentle" : narrationRate > 0.47 ? "Brisk" : "Balanced")
                            .foregroundStyle(.secondary)
                    }
                    Slider(value: $narrationRate, in: 0.30...0.55, step: 0.01)
                }
                Button("Stop Narration") { store.stopNarration() }
            }

            Section {
                Toggle("Encrypted iCloud Sync", isOn: Binding(
                    get: { encryptedICloudSyncEnabled },
                    set: { value in
                        encryptedICloudSyncEnabled = value
                        Task {
                            await store.setICloudSyncEnabled(value)
                            encryptedICloudSyncEnabled = UserDefaults.standard.bool(forKey: "encryptedICloudSyncEnabled")
                        }
                    }
                ))
                Text(store.iCloudStatusMessage)
                    .font(.caption)
                    .foregroundStyle(.secondary)
                if encryptedICloudSyncEnabled {
                    Button("Sync Now") { Task { await store.syncICloudNow() } }
                }
            } header: {
                Text("Private Sync")
            } footer: {
                Text("Optional. Your protected local data is encrypted before upload. The encryption key uses iCloud Keychain. Turning sync off leaves local data intact.")
            }

            Section {
                LabeledContent("The Daily Breath", value: versionText)
                Link(destination: URL(string: "https://ebible.org/web/")!) {
                    Label("World English Bible attribution", systemImage: "book.closed")
                }
                Link(destination: URL(string: "https://www.gutenberg.org/ebooks/16955")!) {
                    Label("Pickthall Quran translation source", systemImage: "moon.stars")
                }
                Link(destination: URL(string: "https://beyondimagination.co.technology/legal/privacy.php")!) {
                    Label("Privacy Policy", systemImage: "hand.raised")
                }
                Link(destination: URL(string: "https://beyondimagination.co.technology/contact.php")!) {
                    Label("Support", systemImage: "envelope")
                }
            } header: {
                Text("About")
            } footer: {
                Text("Bible and Torah/Tanakh text: World English Bible, Public Domain. Quran English meaning: Marmaduke Pickthall, Project Gutenberg eBook 16955, Public Domain in the USA.")
            }
        }
        .navigationTitle("Settings & About")
    }
}

struct DailyHistoryView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id
    @AppStorage("devotionalReadDayKeys") private var devotionalReadDayKeys = ""
    @AppStorage("completedBreathDayKeys") private var completedBreathDayKeys = ""
    @State private var displayedMonth = Calendar.current.date(from: Calendar.current.dateComponents([.year, .month], from: Date())) ?? Date()
    @State private var selectedDate = Date()

    private let columns = Array(repeating: GridItem(.flexible(), spacing: 5), count: 7)

    private var selectedTradition: FaithTradition {
        FaithTradition(rawValue: traditionID) ?? .bible
    }

    var body: some View {
        ScrollView {
            VStack(spacing: 18) {
                HStack {
                    Button { changeMonth(by: -1) } label: { Image(systemName: "chevron.left") }
                    Spacer()
                    Text(displayedMonth.formatted(.dateTime.month(.wide).year()))
                        .font(.title2.bold())
                    Spacer()
                    Button { changeMonth(by: 1) } label: { Image(systemName: "chevron.right") }
                        .disabled(Calendar.current.isDate(displayedMonth, equalTo: Date(), toGranularity: .month))
                }

                LazyVGrid(columns: columns, spacing: 8) {
                    ForEach(weekdaySymbols, id: \.self) { day in
                        Text(day).font(.caption2.bold()).foregroundStyle(.secondary)
                    }
                    ForEach(Array(monthCells.enumerated()), id: \.offset) { _, date in
                        if let date {
                            Button { selectedDate = date } label: {
                                HistoryDayCell(
                                    date: date,
                                    isSelected: Calendar.current.isDate(date, inSameDayAs: selectedDate),
                                    hasDevotional: isContained(Self.dayKey(date), in: devotionalReadDayKeys),
                                    hasBreath: isContained(Self.dayKey(date), in: completedBreathDayKeys),
                                    hasReflection: hasReflection(on: date)
                                )
                            }
                            .buttonStyle(.plain)
                            .disabled(date > Date())
                        } else {
                            Color.clear.frame(height: 54)
                        }
                    }
                }

                HStack(spacing: 12) {
                    HistoryLegend(color: .dailyGold, title: "Verse")
                    HistoryLegend(color: .green, title: "Devotional")
                    HistoryLegend(color: .blue, title: "Breath")
                    HistoryLegend(color: .pink, title: "Reflection")
                }
                .font(.caption2)

                Divider()

                VStack(alignment: .leading, spacing: 12) {
                    Text(selectedDate.formatted(date: .complete, time: .omitted))
                        .font(.headline)
                    if let verse = verse(for: selectedDate) {
                        Text(verse.reference).font(.headline)
                        Text(verse.text).font(.system(.body, design: .serif)).foregroundStyle(.secondary)
                    }
                    if let devotional = devotionalTitle(for: selectedDate) {
                        Label(devotional, systemImage: isContained(Self.dayKey(selectedDate), in: devotionalReadDayKeys) ? "checkmark.circle.fill" : "book.closed")
                    }
                    Label("Breathing practice", systemImage: isContained(Self.dayKey(selectedDate), in: completedBreathDayKeys) ? "checkmark.circle.fill" : "wind")
                    Label("Reflection", systemImage: hasReflection(on: selectedDate) ? "checkmark.circle.fill" : "square.and.pencil")
                }
                .frame(maxWidth: .infinity, alignment: .leading)
                .padding()
                .background(.background, in: RoundedRectangle(cornerRadius: 16))
            }
            .padding()
        }
        .navigationTitle("Daily History")
        .navigationBarTitleDisplayMode(.inline)
        .safeAreaInset(edge: .bottom) {
            Text("This history is a record, not a score. Every return is welcome.")
                .font(.caption)
                .foregroundStyle(.secondary)
                .padding()
                .frame(maxWidth: .infinity)
                .background(.thinMaterial)
        }
    }

    private var monthCells: [Date?] {
        let calendar = Calendar.current
        guard let range = calendar.range(of: .day, in: .month, for: displayedMonth) else { return [] }
        let firstWeekday = calendar.component(.weekday, from: displayedMonth)
        let leading = (firstWeekday - calendar.firstWeekday + 7) % 7
        let dates = range.compactMap { day in
            calendar.date(byAdding: .day, value: day - 1, to: displayedMonth)
        }
        return Array(repeating: nil, count: leading) + dates.map(Optional.some)
    }

    private var weekdaySymbols: [String] {
        let calendar = Calendar.current
        let symbols = calendar.veryShortWeekdaySymbols
        let first = max(0, calendar.firstWeekday - 1)
        return Array(symbols[first...] + symbols[..<first])
    }

    private func changeMonth(by amount: Int) {
        guard let month = Calendar.current.date(byAdding: .month, value: amount, to: displayedMonth) else { return }
        displayedMonth = month
        selectedDate = Calendar.current.isDate(month, equalTo: Date(), toGranularity: .month) ? Date() : month
    }

    private func verse(for date: Date) -> (reference: String, text: String)? {
        let verse = store.dailyVerse(for: selectedTradition, date: date)
        return (verse.reference, verse.text)
    }

    private func devotionalTitle(for date: Date) -> String? {
        if selectedTradition == .bible, let storedTitle = store.dailyHistory[Self.dayKey(date)]?.devotionalTitle {
            return storedTitle
        }
        return store.dailyDevotional(for: selectedTradition, date: date).title
    }

    private func hasReflection(on date: Date) -> Bool {
        let key = Self.dayKey(date)
        return store.entries.contains { Self.dayKey($0.createdAt) == key }
    }

    private func isContained(_ dayKey: String, in value: String) -> Bool {
        value.split(separator: ",").contains(Substring(dayKey))
    }

    private static func dayKey(_ date: Date) -> String {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = .current
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter.string(from: date)
    }
}

private struct HistoryDayCell: View {
    let date: Date
    let isSelected: Bool
    let hasDevotional: Bool
    let hasBreath: Bool
    let hasReflection: Bool

    var body: some View {
        VStack(spacing: 7) {
            Text(date.formatted(.dateTime.day()))
                .font(.subheadline.weight(isSelected ? .bold : .regular))
            HStack(spacing: 3) {
                Circle().fill(Color.dailyGold).frame(width: 4, height: 4)
                Circle().fill(hasDevotional ? Color.green : Color.clear).frame(width: 4, height: 4)
                Circle().fill(hasBreath ? Color.blue : Color.clear).frame(width: 4, height: 4)
                Circle().fill(hasReflection ? Color.pink : Color.clear).frame(width: 4, height: 4)
            }
        }
        .frame(maxWidth: .infinity, minHeight: 50)
        .background(isSelected ? Color.dailyGold.opacity(0.20) : Color.clear, in: RoundedRectangle(cornerRadius: 10))
        .opacity(date > Date() ? 0.3 : 1)
    }
}

private struct HistoryLegend: View {
    let color: Color
    let title: String

    var body: some View {
        HStack(spacing: 4) {
            Circle().fill(color).frame(width: 6, height: 6)
            Text(title).foregroundStyle(.secondary)
        }
    }
}

struct RecoverySupportView: View {
    var body: some View {
        List {
            Section {
                Label("Support beyond the app", systemImage: "heart.text.square.fill")
                    .font(.title2.bold())
                Text("Daily Breath offers spiritual reflection and wellness practices. It is not medical care, diagnosis, treatment, or emergency support.")
                    .foregroundStyle(.secondary)
            }

            Section {
                Link(destination: URL(string: "tel:911")!) {
                    Label("Call local emergency services (U.S. 911)", systemImage: "phone.fill")
                }
                Link(destination: URL(string: "tel:988")!) {
                    Label("Call the 988 Suicide & Crisis Lifeline", systemImage: "phone.fill")
                }
                Link(destination: URL(string: "sms:988")!) {
                    Label("Text 988", systemImage: "message.fill")
                }
                Link(destination: URL(string: "https://988lifeline.org/get-help/")!) {
                    Label("988 chat and accessibility options", systemImage: "safari")
                }
            } header: {
                Text("If you may be in immediate danger")
            } footer: {
                Text("988 services listed here are for the United States and its territories. Elsewhere, contact your local emergency or crisis service.")
            }

            Section {
                Link(destination: URL(string: "tel:18006624357")!) {
                    Label("SAMHSA National Helpline", systemImage: "phone")
                }
                Link(destination: URL(string: "https://www.samhsa.gov/find-help/locators")!) {
                    Label("FindTreatment.gov and treatment locators", systemImage: "map")
                }
            } header: {
                Text("Treatment and recovery resources")
            } footer: {
                Text("SAMHSA: 1-800-662-HELP (4357), free and confidential treatment referral and information in the U.S.")
            }

            Section("A practical next step") {
                Text("If cravings feel unsafe or difficult to manage, contact a licensed clinician, trusted recovery professional, sponsor, or supportive person. Do not rely on this app as your only support.")
            }
        }
        .navigationTitle("Recovery Support")
    }
}
