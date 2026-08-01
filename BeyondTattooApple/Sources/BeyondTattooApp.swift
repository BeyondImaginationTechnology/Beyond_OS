import SwiftUI

@main
struct BeyondTattooApp: App {
    @StateObject private var store = TattooStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
        }
    }
}
