import SwiftUI

@main
struct BeyondTVApp: App {
    @StateObject private var model = AppModel()
    @AppStorage("beyondTVTheme") private var storedTheme = BeyondTVTheme.sunset.rawValue

    private var preferredColorScheme: ColorScheme {
        (BeyondTVTheme(rawValue: storedTheme) ?? .sunset).preferredColorScheme
    }

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(model)
                .preferredColorScheme(preferredColorScheme)
                .task {
                    await model.start()
                }
        }
    }
}
