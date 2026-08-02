import SwiftUI

enum BeyondTVTheme: String, CaseIterable {
    case sunset
    case dark
    case light

    var label: String {
        switch self {
        case .sunset: "Sunset"
        case .dark: "Dark"
        case .light: "Light"
        }
    }

    var symbolName: String {
        switch self {
        case .sunset: "sunset.fill"
        case .dark: "moon.stars.fill"
        case .light: "sun.max.fill"
        }
    }

    var next: BeyondTVTheme {
        let themes = Self.allCases
        let index = themes.firstIndex(of: self) ?? 0
        return themes[(index + 1) % themes.count]
    }
}

struct BeyondTVBackground: View {
    @AppStorage("beyondTVTheme") private var storedTheme = BeyondTVTheme.sunset.rawValue

    private var theme: BeyondTVTheme {
        BeyondTVTheme(rawValue: storedTheme) ?? .sunset
    }

    var body: some View {
        ZStack {
            if theme != .light {
                Image("BeyondTVPromo")
                    .resizable()
                    .scaledToFill()
                    .opacity(theme == .sunset ? 0.24 : 0.18)
                    .blur(radius: 1.5)
            }

            LinearGradient(
                colors: backgroundColors,
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )

            RadialGradient(
                colors: accentGlow,
                center: .topTrailing,
                startRadius: 80,
                endRadius: 560
            )
            .blendMode(theme == .light ? .normal : .screen)
        }
    }

    private var backgroundColors: [Color] {
        switch theme {
        case .sunset:
            [
                Color(red: 0.23, green: 0.08, blue: 0.22).opacity(0.82),
                Color(red: 0.10, green: 0.07, blue: 0.15).opacity(0.96),
                Color(red: 0.06, green: 0.05, blue: 0.10)
            ]
        case .dark:
            [
                Color(red: 0.03, green: 0.04, blue: 0.09).opacity(0.94),
                Color(red: 0.06, green: 0.05, blue: 0.12),
                Color.black
            ]
        case .light:
            [
                Color(red: 0.97, green: 0.94, blue: 0.96),
                Color(red: 0.92, green: 0.95, blue: 0.99)
            ]
        }
    }

    private var accentGlow: [Color] {
        switch theme {
        case .sunset:
            [.orange.opacity(0.28), .pink.opacity(0.16), .clear]
        case .dark:
            [.purple.opacity(0.22), .blue.opacity(0.12), .clear]
        case .light:
            [.pink.opacity(0.16), .orange.opacity(0.08), .clear]
        }
    }
}

struct ThemeToggleButton: View {
    @AppStorage("beyondTVTheme") private var storedTheme = BeyondTVTheme.sunset.rawValue

    private var theme: BeyondTVTheme {
        BeyondTVTheme(rawValue: storedTheme) ?? .sunset
    }

    var body: some View {
        Button {
            storedTheme = theme.next.rawValue
        } label: {
            Label(theme.label, systemImage: theme.symbolName)
                .labelStyle(.iconOnly)
        }
        .accessibilityLabel("Theme: \(theme.label). Switch to \(theme.next.label).")
    }
}
