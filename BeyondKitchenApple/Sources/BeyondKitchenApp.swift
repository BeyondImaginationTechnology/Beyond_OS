import SwiftUI

@main
struct BeyondKitchenApp: App {
    var body: some Scene {
        WindowGroup {
            KitchenRootView()
                .preferredColorScheme(.light)
        }
    }
}
