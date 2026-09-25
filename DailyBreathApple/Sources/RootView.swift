import Foundation
import SwiftUI

enum DailyBreathTab: String, Hashable {
    case home, today, settings, scripture, chat, academy, trivia, breathe, journal
}

struct RootView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @State private var selectedTab: DailyBreathTab? = .home

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var preferredScheme: ColorScheme? {
        switch selectedTheme {
        case .torahLight: .light
        case .quranMoon: .dark
        default: nil
        }
    }

    var body: some View {
        NavigationSplitView {
            List(selection: $selectedTab) {
                Section {
                    BrandHeader()
                        .padding(.vertical, 8)
                        .listRowInsets(EdgeInsets(top: 8, leading: 16, bottom: 12, trailing: 16))
                        .listRowBackground(Color.clear)
                }

                Section("Your practice") {
                    navigationRow(.home, title: "Home", symbol: "house.fill", subtitle: "Your daily space")
                    navigationRow(.today, title: "Today", symbol: "sun.max.fill", subtitle: "A steady beginning")
                    navigationRow(.scripture, title: "Scripture", symbol: "book.closed.fill", subtitle: "Read and reflect")
                    navigationRow(.chat, title: "Chat", symbol: "bubble.left.and.bubble.right.fill", subtitle: "Ask your faith guide")
                    navigationRow(.academy, title: "Academy", symbol: "graduationcap.fill", subtitle: "Learn at your pace")
                    navigationRow(.trivia, title: "Trivia", symbol: "questionmark.circle.fill", subtitle: "Reflect and learn")
                    navigationRow(.breathe, title: "Breathe", symbol: "wind", subtitle: "Find your next breath")
                    navigationRow(.journal, title: "Journal", symbol: "square.and.pencil", subtitle: "Keep what matters")
                }
                Section {
                    navigationRow(.settings, title: "Settings", symbol: "gearshape.fill", subtitle: "Personalize your practice")
                }
            }
            .listStyle(.sidebar)
            .navigationTitle("DailyBreath")
            .navigationBarTitleDisplayMode(.large)
        } detail: {
            detailView(for: selectedTab ?? .today)
        }
        .tint(selectedTheme.accent)
        .navigationSplitViewStyle(.balanced)
        .preferredColorScheme(preferredScheme)
        .onOpenURL(perform: openDeepLink)
        .onAppear {
            if let captureRoute = DailyBreathCaptureRoute.url {
                openDeepLink(captureRoute)
                return
            }
            guard let value = UserDefaults.standard.string(forKey: "pendingDailyBreathDeepLink"),
                  let url = URL(string: value) else { return }
            UserDefaults.standard.removeObject(forKey: "pendingDailyBreathDeepLink")
            openDeepLink(url)
        }
        .onReceive(NotificationCenter.default.publisher(for: .dailyBreathOpenRoute)) { notification in
            guard let value = notification.object as? String, let url = URL(string: value) else { return }
            UserDefaults.standard.removeObject(forKey: "pendingDailyBreathDeepLink")
            openDeepLink(url)
        }
        .onReceive(NotificationCenter.default.publisher(for: NSUbiquitousKeyValueStore.didChangeExternallyNotification)) { _ in
            Task { await store.syncICloudNow() }
        }
    }

    private func openDeepLink(_ url: URL) {
        guard url.scheme == "dailybreath" else { return }
        if let components = URLComponents(url: url, resolvingAgainstBaseURL: false),
           let themeID = components.queryItems?.first(where: { $0.name == "theme" })?.value,
           DailyBreathTheme.allCases.contains(where: { $0.id == themeID }) {
            selectedThemeID = themeID
        }
        let route = (url.host ?? url.pathComponents.last ?? "today").lowercased()
        switch route {
        case "breathe": selectedTab = .breathe
        case "journal": selectedTab = .journal
        case "bible":
            UserDefaults.standard.set(FaithTradition.bible.id, forKey: "selectedFaithTradition")
            store.publishSelectedFaithContent()
            selectedTab = .scripture
        case "torah":
            UserDefaults.standard.set(FaithTradition.torah.id, forKey: "selectedFaithTradition")
            store.publishSelectedFaithContent()
            selectedTab = .scripture
        case "quran":
            UserDefaults.standard.set(FaithTradition.quran.id, forKey: "selectedFaithTradition")
            store.publishSelectedFaithContent()
            selectedTab = .scripture
        case "chat": selectedTab = .chat
        case "academy": selectedTab = .academy
        case "trivia": selectedTab = .trivia
        default: selectedTab = .today
        }
    }

    @ViewBuilder
    private func navigationRow(_ tab: DailyBreathTab, title: String, symbol: String, subtitle: String) -> some View {
        Label {
            VStack(alignment: .leading, spacing: 2) {
                Text(LocalizedStringKey(title))
                Text(LocalizedStringKey(subtitle))
                    .font(.caption2)
                    .foregroundStyle(.secondary)
            }
        } icon: {
            Image(systemName: symbol)
                .symbolRenderingMode(.hierarchical)
                .foregroundStyle(selectedTheme.accent)
        }
        .tag(tab)
        .accessibilityHint("Opens the \(title) space")
    }

    @ViewBuilder
    private func detailView(for tab: DailyBreathTab) -> some View {
        switch tab {
        case .home:
            NavigationStack { DailyBreathHomeView(onNavigate: { selectedTab = $0 }) }
        case .today:
            NavigationStack { TodayView(onNavigate: { selectedTab = $0 }) }
        case .settings:
            NavigationStack { SettingsAboutView() }
        case .scripture:
            NavigationStack { ScriptureLibraryView() }
        case .chat:
            NavigationStack { DailyBreathChatDestination() }
        case .academy:
            NavigationStack { AcademyView() }
        case .trivia:
            NavigationStack { DailyBreathTriviaView() }
        case .breathe:
            NavigationStack { BreatheView() }
        case .journal:
            NavigationStack { JournalView() }
        }
    }
}

private struct DailyBreathHomeView: View {
    var onNavigate: (DailyBreathTab) -> Void = { _ in }
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id
    @AppStorage("dailyReadingDayKeys") private var readDays = ""
    @AppStorage("devotionalReadDayKeys") private var studyDays = ""
    @AppStorage("completedBreathDayKeys") private var breathDays = ""

    private var todayKey: String { DateFormatter.dailyBreathDay.string(from: Date()) }
    private var todayCount: Int {
        [readDays, studyDays, breathDays].filter { $0.split(separator: ",").contains(Substring(todayKey)) }.count
            + (store.entries.contains { Calendar.current.isDateInToday($0.createdAt) } ? 1 : 0)
    }
    private var tradition: FaithTradition { FaithTradition(rawValue: traditionID) ?? .bible }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                Text("A steady beginning").font(.largeTitle.bold())
                Text("Make room for a small faithful step today.")
                    .font(.title3).foregroundStyle(.secondary)
                VStack(alignment: .leading, spacing: 10) {
                    HStack {
                        Label("Today’s rhythm", systemImage: "checklist.checked").font(.headline)
                        Spacer()
                        Text("\(todayCount) of 4").font(.caption.bold())
                    }
                    Text("Read, study, breathe, and reflect at your own pace.")
                        .font(.subheadline).foregroundStyle(.secondary)
                    Label(store.dailyContentAvailability.title, systemImage: store.dailyContentAvailability.systemImage)
                        .font(.caption.weight(.semibold))
                        .accessibilityLabel("Daily content status: \(store.dailyContentAvailability.title)")
                }
                .padding()
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(.background.opacity(0.88), in: RoundedRectangle(cornerRadius: 16))
                Button { onNavigate(.today) } label: {
                    Label("Open Today · \(store.dailyVerse(for: tradition).reference)", systemImage: "sun.max.fill")
                        .font(.headline).frame(maxWidth: .infinity, alignment: .leading)
                        .padding().background(DailyBreathTheme(id: selectedThemeID).primary.opacity(0.12), in: RoundedRectangle(cornerRadius: 16))
                }
                .buttonStyle(.plain)
                ScriptureContinueReadingLink()
                NavigationLink { ScriptureLibraryView() } label: {
                    Label("Explore Scripture", systemImage: "book.closed.fill")
                        .font(.headline).frame(maxWidth: .infinity, alignment: .leading)
                        .padding().background(.background.opacity(0.7), in: RoundedRectangle(cornerRadius: 16))
                }
                NavigationLink { SettingsAboutView() } label: {
                    Label("Settings", systemImage: "gearshape.fill")
                        .font(.headline).frame(maxWidth: .infinity, alignment: .leading)
                        .padding().background(.background.opacity(0.7), in: RoundedRectangle(cornerRadius: 16))
                }
            }
            .padding()
        }
        .background(DailyBreathThemeBackground(theme: DailyBreathTheme(id: selectedThemeID)))
        .navigationTitle("Home")
        .navigationBarTitleDisplayMode(.inline)
    }
}

private extension DateFormatter {
    static let dailyBreathDay: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

private struct DailyBreathChatDestination: View {
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id

    private var tradition: FaithTradition {
        FaithTradition(rawValue: traditionID) ?? .bible
    }

    var body: some View {
        JaguarScriptureChatView(guide: .init(tradition: tradition))
            .id(tradition.id)
    }
}

enum DailyBreathCaptureRoute {
    static var isActive: Bool {
        CommandLine.arguments.contains("-dailyBreathCaptureRoute")
    }

    static var url: URL? {
        guard let flagIndex = CommandLine.arguments.firstIndex(of: "-dailyBreathCaptureRoute"),
              CommandLine.arguments.indices.contains(flagIndex + 1) else {
            return nil
        }

        return URL(string: "dailybreath://\(CommandLine.arguments[flagIndex + 1])")
    }
}

struct BrandHeader: View {
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        HStack(spacing: 12) {
            Image("DailyBreathIcon")
                .resizable()
                .scaledToFit()
                .frame(width: 54, height: 54)
                .clipShape(RoundedRectangle(cornerRadius: 14))
            VStack(alignment: .leading, spacing: 2) {
                Text("DAILYBREATH")
                    .font(.headline.weight(.black))
                Text("Faith-centered wellness")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            Spacer()
        }
    }
}

extension Color {
    static let dailyGreen = Color(red: 0.09, green: 0.25, blue: 0.17)
    static let dailyGold = Color(red: 0.82, green: 0.64, blue: 0.30)
    static let dailyCream = Color(red: 0.96, green: 0.92, blue: 0.84)
}
