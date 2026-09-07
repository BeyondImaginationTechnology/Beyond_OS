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
                    await store.load()
                    await DailyBreathNotificationService.refreshScheduledReminderIfEnabled()
                }
        }
    }
}
