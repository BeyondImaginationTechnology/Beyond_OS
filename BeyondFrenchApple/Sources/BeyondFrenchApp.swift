import SwiftUI

@main
struct BeyondFrenchApp: App {
    @StateObject private var store = AppStore()
    @StateObject private var auth = BeyondFrenchAuth()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .environmentObject(auth)
                .task {
                    store.configureAuth(auth)
                    await auth.restoreSession()
                    await store.load()
                    await store.syncProgress()
                }
        }
    }
}
