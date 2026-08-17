import SwiftUI

@main
struct DailyBreathApp: App {
    @StateObject private var store = DailyBreathStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .task { await store.load() }
        }
    }
}
