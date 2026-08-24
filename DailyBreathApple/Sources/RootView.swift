import SwiftUI

enum DailyBreathTab: String, Hashable {
    case today, scripture, academy, breathe, journal
}

struct RootView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @State private var selectedTab = DailyBreathTab.today

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
        TabView(selection: $selectedTab) {
            NavigationStack { TodayView() }
                .tabItem { Label("Today", systemImage: "sun.max.fill") }
                .tag(DailyBreathTab.today)
            NavigationStack { ScriptureLibraryView() }
                .tabItem { Label("Scripture", systemImage: "book.closed.fill") }
                .tag(DailyBreathTab.scripture)
            NavigationStack { AcademyView() }
                .tabItem { Label("Academy", systemImage: "graduationcap.fill") }
                .tag(DailyBreathTab.academy)
            NavigationStack { BreatheView() }
                .tabItem { Label("Breathe", systemImage: "wind") }
                .tag(DailyBreathTab.breathe)
            NavigationStack { JournalView() }
                .tabItem { Label("Journal", systemImage: "square.and.pencil") }
                .tag(DailyBreathTab.journal)
        }
        .tint(selectedTheme.accent)
        .preferredColorScheme(preferredScheme)
        .onOpenURL(perform: openDeepLink)
        .onAppear {
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
        let route = (url.host ?? url.pathComponents.last ?? "today").lowercased()
        switch route {
        case "breathe": selectedTab = .breathe
        case "journal": selectedTab = .journal
        case "bible":
            UserDefaults.standard.set(FaithTradition.bible.id, forKey: "selectedFaithTradition")
            selectedTab = .scripture
        case "torah":
            UserDefaults.standard.set(FaithTradition.torah.id, forKey: "selectedFaithTradition")
            selectedTab = .scripture
        case "quran":
            UserDefaults.standard.set(FaithTradition.quran.id, forKey: "selectedFaithTradition")
            selectedTab = .scripture
        case "academy": selectedTab = .academy
        default: selectedTab = .today
        }
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
            Text(Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "")
                .font(.caption.bold())
                .padding(.horizontal, 10)
                .padding(.vertical, 6)
                .foregroundStyle(selectedTheme.primary)
                .background(selectedTheme.primary.opacity(0.12), in: Capsule())
        }
    }
}

extension Color {
    static let dailyGreen = Color(red: 0.09, green: 0.25, blue: 0.17)
    static let dailyGold = Color(red: 0.82, green: 0.64, blue: 0.30)
    static let dailyCream = Color(red: 0.96, green: 0.92, blue: 0.84)
}
