import Foundation
import SwiftUI

@main
struct DailyBreathApp: App {
    @UIApplicationDelegateAdaptor(DailyBreathAppDelegate.self) private var appDelegate
    @StateObject private var store = DailyBreathStore()
    @StateObject private var auth = BeyondIDAuthManager()
    @AppStorage("dailyBreathLanguage") private var languageID = ""
    @AppStorage("dailyBreathOnboardingComplete") private var onboardingComplete = false

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .environmentObject(auth)
                .environment(\.locale, Locale(identifier: languageID.isEmpty ? "en" : languageID))
                .fullScreenCover(isPresented: Binding(
                    get: { languageID.isEmpty && !DailyBreathCaptureRoute.isActive },
                    set: { _ in }
                )) {
                    LanguageSetupView(languageID: $languageID)
                }
                .fullScreenCover(isPresented: Binding(
                    get: { !languageID.isEmpty && !onboardingComplete && !DailyBreathCaptureRoute.isActive },
                    set: { _ in }
                )) {
                    AccountChoiceView(onboardingComplete: $onboardingComplete)
                        .environmentObject(auth)
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
        if defaults.object(forKey: "selectedFaithTradition") == nil {
            defaults.set(FaithTradition.bible.id, forKey: "selectedFaithTradition")
        }
        if defaults.object(forKey: "dailyBreathTheme") == nil {
            defaults.set(DailyBreathTheme.seasonal.id, forKey: "dailyBreathTheme")
        }
    }
}
