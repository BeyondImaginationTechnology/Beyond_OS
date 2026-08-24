import SwiftUI

enum SpaceTheme {
    static let ink = Color(red: 0.01, green: 0.015, blue: 0.025)
    static let panel = Color(red: 0.035, green: 0.055, blue: 0.09)
    static let violet = Color(red: 0.34, green: 0.23, blue: 0.08)
    static let cyan = Color(red: 0.88, green: 0.66, blue: 0.25)
    static let text = Color.white
    static let secondaryText = Color.white.opacity(0.78)
}

struct SpaceBackground: View {
    @Environment(\.accessibilityReduceTransparency) private var reduceTransparency

    var body: some View {
        ZStack {
            SpaceTheme.ink
            if !reduceTransparency {
                RadialGradient(colors: [SpaceTheme.violet.opacity(0.28), .clear], center: .topTrailing, startRadius: 20, endRadius: 430)
                RadialGradient(colors: [SpaceTheme.cyan.opacity(0.15), .clear], center: .bottomLeading, startRadius: 10, endRadius: 380)
            }
        }
        .ignoresSafeArea()
    }
}

struct SpaceCard<Content: View>: View {
    let content: Content

    init(@ViewBuilder content: () -> Content) {
        self.content = content()
    }

    var body: some View {
        content
            .padding(20)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(SpaceTheme.panel, in: RoundedRectangle(cornerRadius: 24, style: .continuous))
            .overlay(RoundedRectangle(cornerRadius: 24, style: .continuous).stroke(Color.white.opacity(0.14)))
    }
}
