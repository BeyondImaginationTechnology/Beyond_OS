import SwiftUI

enum JaguarTheme {
    static let ink = Color(red: 0.025, green: 0.012, blue: 0.055)
    static let panel = Color(red: 0.075, green: 0.035, blue: 0.12)
    static let panelRaised = Color(red: 0.12, green: 0.06, blue: 0.18)
    static let violet = Color(red: 0.56, green: 0.22, blue: 0.96)
    static let magenta = Color(red: 0.91, green: 0.23, blue: 0.78)
    static let mint = Color(red: 0.39, green: 0.95, blue: 0.66)
    static let secondaryText = Color.white.opacity(0.66)
    static let gradient = LinearGradient(colors: [violet, magenta], startPoint: .topLeading, endPoint: .bottomTrailing)
}
