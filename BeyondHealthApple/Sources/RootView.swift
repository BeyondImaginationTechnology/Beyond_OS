import SwiftUI

struct RootView: View {
    @EnvironmentObject private var store: HealthStore

    var body: some View {
        TabView {
            NavigationStack { TodayView() }
                .tabItem { Label("Today", systemImage: "sun.max.fill") }
            NavigationStack { JournalView() }
                .tabItem { Label("Journal", systemImage: "square.and.pencil") }
            NavigationStack { PracticesView() }
                .tabItem { Label("Practices", systemImage: "circle.dotted") }
            NavigationStack { InsightsView() }
                .tabItem { Label("Insights", systemImage: "chart.bar") }
            NavigationStack { SettingsView() }
                .tabItem { Label("Settings", systemImage: "gearshape") }
        }
        .tint(.healthGreen)
        .alert("Could not save", isPresented: Binding(
            get: { store.errorMessage != nil },
            set: { if !$0 { store.errorMessage = nil } }
        )) {
            Button("OK") { store.errorMessage = nil }
        } message: {
            Text(store.errorMessage ?? "Please try again.")
        }
    }
}

struct HealthScreen<Content: View>: View {
    let title: String
    let content: Content

    init(title: String, @ViewBuilder content: () -> Content) {
        self.title = title
        self.content = content()
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                HStack(spacing: 10) {
                    Image(systemName: "heart.fill")
                        .foregroundStyle(.white)
                        .frame(width: 38, height: 38)
                        .background(Color.healthGreen, in: RoundedRectangle(cornerRadius: 12))
                    VStack(alignment: .leading, spacing: 1) {
                        Text("Beyond Health").font(.headline.weight(.bold))
                        Text("Daily wellbeing companion").font(.caption).foregroundStyle(.secondary)
                    }
                    Spacer()
                }
                content
            }
            .padding()
        }
        .background(Color.healthBackground.ignoresSafeArea())
        .navigationTitle(title)
        .navigationBarTitleDisplayMode(.inline)
    }
}

struct HealthCard<Content: View>: View {
    let content: Content

    init(@ViewBuilder content: () -> Content) {
        self.content = content()
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 14) { content }
            .padding(18)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(.white, in: RoundedRectangle(cornerRadius: 22))
    }
}

extension Color {
    static let healthBackground = Color(red: 0.96, green: 0.98, blue: 0.95)
    static let healthGreen = Color(red: 0.09, green: 0.47, blue: 0.35)
    static let healthInk = Color(red: 0.09, green: 0.21, blue: 0.17)
    static let healthMint = Color(red: 0.87, green: 0.95, blue: 0.89)
}
