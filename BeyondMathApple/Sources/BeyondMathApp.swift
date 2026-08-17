import SwiftUI

@main
struct BeyondMathApp: App {
    @StateObject private var progress = LearningProgress()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(progress)
                .tint(.mathGreen)
        }
    }
}

extension Color {
    static let mathNavy = Color(red: 0.027, green: 0.067, blue: 0.122)
    static let mathPanel = Color(red: 0.051, green: 0.102, blue: 0.169)
    static let mathBlue = Color(red: 0.055, green: 0.647, blue: 1)
    static let mathGreen = Color(red: 0.357, green: 0.859, blue: 0.271)
    static let mathGold = Color(red: 1, green: 0.831, blue: 0.231)
    static let mathPurple = Color(red: 0.729, green: 0.267, blue: 1)
}
