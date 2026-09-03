import SwiftUI

@main
struct DailyBreathApp: App {
    @UIApplicationDelegateAdaptor(DailyBreathAppDelegate.self) private var appDelegate
    @StateObject private var store = DailyBreathStore()
    @StateObject private var beyondIDSession = DailyBreathBeyondIDSession()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .environmentObject(beyondIDSession)
                .task {
                    await store.load()
                    await DailyBreathNotificationService.refreshScheduledReminderIfEnabled()
                }
        }
    }
}
