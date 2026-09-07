import Foundation
import SwiftUI

@main
struct DailyBreathApp: App {
    @UIApplicationDelegateAdaptor(DailyBreathAppDelegate.self) private var appDelegate
    @StateObject private var store = DailyBreathStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .task {
                    DailyBreathLaunchDefaults.seedIfNeeded()
                    await store.load()
                    await DailyBreathNotificationService.refreshScheduledReminderIfEnabled()
                }
        }
    }
}

private enum DailyBreathLaunchDefaults {
    static func seedIfNeeded() {
        let defaults = UserDefaults.standard
        guard defaults.object(forKey: "selectedFaithTradition") == nil else { return }

        defaults.set(FaithTradition.bible.id, forKey: "selectedFaithTradition")
        defaults.set(DailyBreathTheme.forest.id, forKey: "dailyBreathTheme")
    }
}
