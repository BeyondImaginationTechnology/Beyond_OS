import SwiftUI

enum DailyBreathTheme: String, CaseIterable, Identifiable {
    case forest
    case botanical
    case dawn

    var id: String { rawValue }

    init(id: String) {
        self = DailyBreathTheme(rawValue: id) ?? .forest
    }

    var name: String {
        switch self {
        case .forest: return "Forest"
        case .botanical: return "Botanical"
        case .dawn: return "Dawn"
        }
    }

    var symbolName: String {
        switch self {
        case .forest: return "leaf.fill"
        case .botanical: return "camera.macro"
        case .dawn: return "sunrise.fill"
        }
    }

    var primary: Color {
        switch self {
        case .forest: return Color.dailyGreen
        case .botanical: return Color(red: 0.20, green: 0.31, blue: 0.23)
        case .dawn: return Color(red: 0.49, green: 0.25, blue: 0.18)
        }
    }

    var secondary: Color {
        switch self {
        case .forest: return Color(red: 0.17, green: 0.41, blue: 0.29)
        case .botanical: return Color(red: 0.56, green: 0.68, blue: 0.50)
        case .dawn: return Color(red: 0.85, green: 0.63, blue: 0.36)
        }
    }

    var accent: Color {
        switch self {
        case .forest, .botanical: return Color.dailyGold
        case .dawn: return Color(red: 0.96, green: 0.80, blue: 0.48)
        }
    }

    var pageBase: Color {
        switch self {
        case .forest: return Color(red: 0.93, green: 0.96, blue: 0.91)
        case .botanical: return Color(red: 0.96, green: 0.93, blue: 0.86)
        case .dawn: return Color(red: 0.98, green: 0.91, blue: 0.82)
        }
    }
}

struct DailyBreathThemeBackground: View {
    let theme: DailyBreathTheme

    var body: some View {
        ZStack {
            theme.pageBase.ignoresSafeArea()
            LinearGradient(
                colors: [
                    theme.pageBase,
                    theme.secondary.opacity(0.20),
                    theme.primary.opacity(0.12)
                ],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
            .ignoresSafeArea()
            LinearGradient(
                colors: [
                    theme.accent.opacity(0.18),
                    .clear,
                    theme.primary.opacity(0.10)
                ],
                startPoint: .top,
                endPoint: .bottom
            )
            .ignoresSafeArea()
        }
    }
}
