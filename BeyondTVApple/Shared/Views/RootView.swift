import SwiftUI

struct RootView: View {
    @EnvironmentObject private var model: AppModel

    var body: some View {
        TabView {
            WatchView()
                .tabItem { Label("Watch", systemImage: "play.tv.fill") }

            ChannelGuideView()
                .tabItem { Label("Guide", systemImage: "rectangle.grid.2x2.fill") }

            AboutView()
                .tabItem { Label("About", systemImage: "info.circle.fill") }
        }
        .tint(.purple)
    }
}
