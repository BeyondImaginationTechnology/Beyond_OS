import SwiftUI

@main
struct BeyondHealthMobileApp: App {
    @StateObject private var store = HealthStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
        }
    }
}
