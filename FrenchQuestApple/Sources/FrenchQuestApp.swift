import SwiftUI

@main
struct FrenchQuestApp: App {
    @StateObject private var store = QuestStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
        }
    }
}
