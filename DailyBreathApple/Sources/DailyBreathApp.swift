import Foundation
import SwiftUI

@main
struct DailyBreathApp: App {
    @UIApplicationDelegateAdaptor(DailyBreathAppDelegate.self) private var appDelegate
    @StateObject private var store = DailyBreathStore()
    @AppStorage("dailyBreathLanguage") private var languageID = ""

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .environment(\.locale, Locale(identifier: languageID.isEmpty ? "en" : languageID))
                .fullScreenCover(isPresented: Binding(
                    get: { languageID.isEmpty && !DailyBreathCaptureRoute.isActive },
                    set: { _ in }
                )) {
                    LanguageSetupView(languageID: $languageID)
                }
                .task {
                    if DailyBreathCaptureRoute.isActive && languageID.isEmpty {
                        languageID = DailyBreathLanguage.english.rawValue
                    }
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
