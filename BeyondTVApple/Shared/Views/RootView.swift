import SwiftUI

enum BeyondTVTab: Hashable {
    case watch
    case guide
    case browse
    case about
}

struct RootView: View {
    @EnvironmentObject private var model: AppModel

    var body: some View {
        TabView(selection: $model.selectedTab) {
            WatchView()
                .tabItem { Label("Watch", systemImage: "play.tv.fill") }
                .tag(BeyondTVTab.watch)

            ChannelGuideView()
                .tabItem { Label("Guide", systemImage: "rectangle.grid.2x2.fill") }
                .tag(BeyondTVTab.guide)

            BrowseView()
                .tabItem { Label("Browse", systemImage: "square.grid.2x2.fill") }
                .tag(BeyondTVTab.browse)

            AboutView()
                .tabItem { Label("About", systemImage: "info.circle.fill") }
                .tag(BeyondTVTab.about)
        }
        .tint(.orange)
    }
}
