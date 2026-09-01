import SwiftUI

enum DailyBreathTab: String, Hashable {
    case today, scripture, academy, breathe, journal
}

struct RootView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @State private var selectedTab: DailyBreathTab? = .today

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
                    navigationRow(.today, title: "Today", symbol: "sun.max.fill", subtitle: "A steady beginning")
                    navigationRow(.scripture, title: "Scripture", symbol: "book.closed.fill", subtitle: "Read and reflect")
                    navigationRow(.academy, title: "Academy", symbol: "graduationcap.fill", subtitle: "Learn at your pace")
                    navigationRow(.breathe, title: "Breathe", symbol: "wind", subtitle: "Find your next breath")
                    navigationRow(.journal, title: "Journal", symbol: "square.and.pencil", subtitle: "Keep what matters")
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
        case "academy": selectedTab = .academy
        default: selectedTab = .today
        }
    }

    @ViewBuilder
    private func navigationRow(_ tab: DailyBreathTab, title: String, symbol: String, subtitle: String) -> some View {
        Label {
            VStack(alignment: .leading, spacing: 2) {
                Text(title)
                Text(subtitle)
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
        case .today:
            NavigationStack { TodayView() }
        case .scripture:
            NavigationStack { ScriptureLibraryView() }
        case .academy:
            NavigationStack { AcademyView() }
        case .breathe:
            NavigationStack { BreatheView() }
        case .journal:
            NavigationStack { JournalView() }
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
