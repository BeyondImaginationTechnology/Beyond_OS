import SwiftUI

enum DailyBreathTheme: String, CaseIterable, Identifiable {
    case seasonal
    case fall
    case forest
    case botanical
    case dawn
    case rose
    case torahLight
    case quranMoon
    case bibleForest
    case tanakhNavy
    case quranEmerald

    var id: String { rawValue }

    init(id: String) {
        self = DailyBreathTheme(rawValue: id) ?? .seasonal
    }

    static var currentSeason: DailyBreathTheme {
        switch Calendar.current.component(.month, from: Date()) {
        case 9...11: .fall
        case 12, 1, 2: .forest
        default: .botanical
        }
    }

    var artworkName: String? {
        switch self {
        case .bibleForest: "BibleForestPortrait"
        case .tanakhNavy: "TanakhNavyPortrait"
        case .quranEmerald: "QuranEmeraldPortrait"
        default: nil
        }
    }

    var shareArtworkName: String? {
        switch self {
        case .bibleForest: "BibleForestLandscape"
        case .tanakhNavy: "TanakhNavyLandscape"
        case .quranEmerald: "QuranEmeraldLandscape"
        default: nil
        }
    }

    static func recommended(for tradition: FaithTradition) -> DailyBreathTheme {
        switch tradition {
        case .bible: .forest
        case .torah: .torahLight
        case .quran: .quranMoon
        }
    }

    var name: String {
        switch self {
        case .seasonal: return "Seasonal"
        case .fall: return "Fall"
        case .forest: return "Forest"
        case .botanical: return "Botanical"
        case .dawn: return "Dawn"
        case .rose: return "Rose"
        case .torahLight: return "Torah Light"
        case .quranMoon: return "Quran Moon"
        case .bibleForest: return "Bible Forest"
        case .tanakhNavy: return "Tanakh Navy & Gold"
        case .quranEmerald: return "Quran Emerald & Gold"
        }
    }

    var symbolName: String {
        switch self {
        case .seasonal: return "calendar"
        case .fall: return "leaf.fill"
        case .forest: return "leaf.fill"
        case .botanical: return "camera.macro"
        case .dawn: return "sunrise.fill"
        case .rose: return "heart.fill"
        case .torahLight: return "star.circle.fill"
        case .quranMoon: return "moon.stars.fill"
        case .bibleForest: return "cross.fill"
        case .tanakhNavy: return "star.circle.fill"
        case .quranEmerald: return "moon.stars.fill"
        }
    }

    var primary: Color {
        switch self {
        case .seasonal: return Self.currentSeason.primary
        case .fall: return Color(red: 0.34, green: 0.21, blue: 0.13)
        case .forest: return Color.dailyGreen
        case .botanical: return Color(red: 0.20, green: 0.31, blue: 0.23)
        case .dawn: return Color(red: 0.49, green: 0.25, blue: 0.18)
        case .rose: return Color(red: 0.62, green: 0.16, blue: 0.35)
        case .torahLight: return Color(red: 0.18, green: 0.36, blue: 0.62)
        // Quran Moon's page is intentionally midnight navy. Use a sky-blue
        // ink for titles, controls, and labels so it remains legible on that
        // surface everywhere the shared theme primary color is used.
        case .quranMoon: return Color(red: 0.52, green: 0.78, blue: 1.0)
        case .bibleForest: return Color(red: 0.03, green: 0.16, blue: 0.12)
        case .tanakhNavy: return Color(red: 0.03, green: 0.08, blue: 0.19)
        case .quranEmerald: return Color(red: 0.02, green: 0.18, blue: 0.15)
        }
    }

    var secondary: Color {
        switch self {
        case .seasonal: return Self.currentSeason.secondary
        case .fall: return Color(red: 0.68, green: 0.40, blue: 0.18)
        case .forest: return Color(red: 0.17, green: 0.41, blue: 0.29)
        case .botanical: return Color(red: 0.56, green: 0.68, blue: 0.50)
        case .dawn: return Color(red: 0.85, green: 0.63, blue: 0.36)
        case .rose: return Color(red: 0.91, green: 0.45, blue: 0.62)
        case .torahLight: return Color(red: 0.68, green: 0.82, blue: 0.96)
        case .quranMoon: return Color(red: 0.08, green: 0.31, blue: 0.34)
        case .bibleForest: return Color(red: 0.12, green: 0.34, blue: 0.25)
        case .tanakhNavy: return Color(red: 0.10, green: 0.20, blue: 0.36)
        case .quranEmerald: return Color(red: 0.11, green: 0.38, blue: 0.29)
        }
    }

    var accent: Color {
        switch self {
        case .seasonal: return Self.currentSeason.accent
        case .fall: return Color(red: 0.96, green: 0.76, blue: 0.42)
        case .forest, .botanical: return Color.dailyGold
        case .dawn: return Color(red: 0.96, green: 0.80, blue: 0.48)
        case .rose: return Color(red: 0.98, green: 0.72, blue: 0.82)
        case .torahLight: return Color(red: 0.86, green: 0.69, blue: 0.24)
        case .quranMoon: return Color(red: 0.93, green: 0.72, blue: 0.25)
        case .bibleForest, .tanakhNavy, .quranEmerald: return Color(red: 0.96, green: 0.82, blue: 0.54)
        }
    }

    var pageBase: Color {
        switch self {
        case .seasonal: return Self.currentSeason.pageBase
        case .fall: return Color(red: 0.97, green: 0.93, blue: 0.86)
        case .forest: return Color(red: 0.93, green: 0.96, blue: 0.91)
        case .botanical: return Color(red: 0.96, green: 0.93, blue: 0.86)
        case .dawn: return Color(red: 0.98, green: 0.91, blue: 0.82)
        case .rose: return Color(red: 0.99, green: 0.92, blue: 0.95)
        case .torahLight: return Color(red: 0.965, green: 0.98, blue: 1.0)
        case .quranMoon: return Color(red: 0.025, green: 0.045, blue: 0.10)
        case .bibleForest: return Color(red: 0.91, green: 0.94, blue: 0.88)
        case .tanakhNavy: return Color(red: 0.91, green: 0.93, blue: 0.97)
        case .quranEmerald: return Color(red: 0.90, green: 0.95, blue: 0.91)
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
