import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { TodayView() }
                .tabItem { Label("Today", systemImage: "sparkles") }
            NavigationStack { ExploreView() }
                .tabItem { Label("Explore", systemImage: "safari.fill") }
            NavigationStack { HoroscopeView() }
                .tabItem { Label("Horoscope", systemImage: "moon.stars.fill") }
            NavigationStack { SettingsView() }
                .tabItem { Label("Settings", systemImage: "accessibility") }
        }
        .tint(SpaceTheme.cyan)
        .preferredColorScheme(.dark)
    }
}
