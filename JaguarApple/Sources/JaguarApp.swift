import SwiftUI

@main
struct JaguarApp: App {
    @StateObject private var auth = JaguarAuthManager()
    @StateObject private var store = JaguarChatStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(auth)
                .environmentObject(store)
                .preferredColorScheme(.dark)
        }
    }
}
