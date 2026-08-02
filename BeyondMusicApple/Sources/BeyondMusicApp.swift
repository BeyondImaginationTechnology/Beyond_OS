import SwiftUI

@main
struct BeyondMusicApp: App {
    @StateObject private var store = MusicStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .tint(.musicAqua)
                .preferredColorScheme(.dark)
                .task {
                    await store.refreshBeyondIDSession()
                }
        }
    }
}
