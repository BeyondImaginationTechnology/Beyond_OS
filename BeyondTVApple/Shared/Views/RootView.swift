import SwiftUI

enum BeyondTVTab: Hashable {
    case watch
    case guide
    case browse
    case safety
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

            SourceSafetyView()
                .tabItem { Label("Safety", systemImage: "checkmark.shield.fill") }
                .tag(BeyondTVTab.safety)

            AboutView()
                .tabItem { Label("About", systemImage: "info.circle.fill") }
                .tag(BeyondTVTab.about)
        }
        .tint(.orange)
    }
}

private struct SourceSafetyView: View {
    @EnvironmentObject private var model: AppModel

    var body: some View {
        NavigationStack {
            List {
                Section {
                    Text("Report a questionable video source without interrupting live TV.")
                    Text("A working stream shows availability. It does not establish permission to use the video.")
                        .foregroundStyle(.secondary)
                } header: {
                    Text("Source Safety")
                }

                Section("Review process") {
                    Label("Source verification: identify the provider and exact link", systemImage: "link")
                    Label("Evidence: record the title, page, and observation", systemImage: "doc.text.magnifyingglass")
                    Label("Abuse handling: staff review reports before taking action", systemImage: "checkmark.shield")
                }

                Section("Report") {
                    #if os(iOS)
                    Link("Report current source", destination: reportURL)
                    #else
                    Text("Report at beyondimagination.co.technology/beyond-tv/source-safety.php")
                    #endif
                    Text("Formal rights notices: support@beyondimagination.co.technology")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }
            .scrollContentBackground(.hidden)
            .background(BeyondTVBackground().ignoresSafeArea())
            .navigationTitle("Source Safety")
        }
    }

    private var reportURL: URL {
        var parts = URLComponents(string: "https://beyondimagination.co.technology/beyond-tv/source-safety.php")!
        var items = [URLQueryItem(name: "title", value: model.status.now)]
        if let channel = model.selectedChannel {
            items.append(URLQueryItem(name: "channel", value: channel.slug))
            items.append(URLQueryItem(name: "page", value: "https://beyondimagination.co.technology/beyond-tv/channel.php?slug=\(channel.slug)"))
        }
        if let source = model.currentSource?.url ?? model.webPlaybackURL {
            items.append(URLQueryItem(name: "source", value: source.absoluteString))
        }
        parts.queryItems = items
        return parts.url!
    }
}
