import SwiftUI

@main
struct BeyondBabyNamesApp: App {
    @StateObject private var store = BabyNameStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .preferredColorScheme(.dark)
        }
    }
}
