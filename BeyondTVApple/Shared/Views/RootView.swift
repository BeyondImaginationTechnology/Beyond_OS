import SwiftUI

enum BeyondTVTab: Hashable {
    case watch
    case guide
    case browse
    case account
    case about
}

struct RootView: View {
    @EnvironmentObject private var model: AppModel
    @State private var selectedTab = BeyondTVTab.watch

    var body: some View {
        TabView(selection: $selectedTab) {
            WatchView()
                .tabItem { Label("Watch", systemImage: "play.tv.fill") }
                .tag(BeyondTVTab.watch)

            ChannelGuideView()
                .tabItem { Label("Guide", systemImage: "rectangle.grid.2x2.fill") }
                .tag(BeyondTVTab.guide)

            BrowseView(selectedTab: $selectedTab)
                .tabItem { Label("Browse", systemImage: "square.grid.2x2.fill") }
                .tag(BeyondTVTab.browse)

            AccountView()
                .tabItem { Label("Account", systemImage: "person.crop.circle.fill") }
                .tag(BeyondTVTab.account)

            AboutView()
                .tabItem { Label("About", systemImage: "info.circle.fill") }
                .tag(BeyondTVTab.about)
        }
        .tint(.orange)
    }
}
