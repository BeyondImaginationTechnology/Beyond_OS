import SwiftUI

struct RootView: View {
    @EnvironmentObject private var store: AppStore

    var body: some View {
        TabView {
            NavigationStack { TodayView() }
                .tabItem { Label("Today", systemImage: "sun.max.fill") }
            NavigationStack { AcademyView() }
                .tabItem { Label("Academy", systemImage: "graduationcap.fill") }
            NavigationStack { DictionaryView() }
                .tabItem { Label("Dictionary", systemImage: "character.book.closed.fill") }
            NavigationStack { PracticeView() }
                .tabItem { Label("Practice", systemImage: "waveform.and.mic") }
            NavigationStack { LearningProgressView() }
                .tabItem { Label("Progress", systemImage: "chart.bar.fill") }
        }
        .tint(store.appTheme.accent)
        .preferredColorScheme(.dark)
    }
}

struct BrandHeader: View {
    @EnvironmentObject private var store: AppStore

    var body: some View {
        HStack(spacing: 12) {
            Image("BeyondFrenchLogo").resizable().scaledToFit().frame(width: 54, height: 54).clipShape(Circle())
            VStack(alignment: .leading, spacing: 2) {
                Text("BEYOND FRENCH").font(.headline.weight(.black))
                Text("Speak, listen, remember").font(.caption).foregroundStyle(.secondary)
            }
            Spacer()
            Image(systemName: store.appTheme.symbol)
                .font(.headline)
                .foregroundStyle(store.appTheme.accent)
                .frame(width: 34, height: 34)
                .background(store.appTheme.accent.opacity(0.20), in: Circle())
        }
        .foregroundStyle(.white)
    }
}

struct AccessPill: View {
    let text: String
    var body: some View {
        Label(text, systemImage: "checkmark.seal.fill")
            .font(.caption.bold()).foregroundStyle(.green)
            .padding(.horizontal, 10).padding(.vertical, 7)
            .background(.green.opacity(0.18), in: Capsule())
    }
}

struct MetricTile: View {
    let title: String
    let value: String
    let systemImage: String
    let color: Color

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            Image(systemName: systemImage)
                .font(.title3.weight(.semibold))
                .foregroundStyle(color)
            Text(value)
                .font(.title2.weight(.black))
            Text(title.uppercased())
                .font(.caption2.weight(.bold))
                .foregroundStyle(.white.opacity(0.66))
        }
        .foregroundStyle(.white)
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(14)
        .background(
            LinearGradient(colors: [color.opacity(0.32), Color.white.opacity(0.055)], startPoint: .topLeading, endPoint: .bottomTrailing),
            in: RoundedRectangle(cornerRadius: 16)
        )
        .overlay(RoundedRectangle(cornerRadius: 16).stroke(color.opacity(0.22), lineWidth: 1))
    }
}

struct ThemePicker: View {
    @EnvironmentObject private var store: AppStore

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Theme")
                .font(.title3.weight(.black))
            Picker("Theme", selection: $store.appTheme) {
                ForEach(AppTheme.allCases) { theme in
                    Label(theme.title, systemImage: theme.symbol).tag(theme)
                }
            }
            .pickerStyle(.segmented)
        }
        .padding(18)
        .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))
        .overlay(RoundedRectangle(cornerRadius: 22).stroke(store.appTheme.accent.opacity(0.18), lineWidth: 1))
    }
}

extension AppTheme {
    var appBackground: LinearGradient {
        LinearGradient(colors: backgroundColors, startPoint: .topLeading, endPoint: .bottomTrailing)
    }

    var cardFill: LinearGradient {
        LinearGradient(colors: cardColors, startPoint: .topLeading, endPoint: .bottomTrailing)
    }

    private var backgroundColors: [Color] {
        switch self {
        case .classic:
            [
                Color(red: 0.02, green: 0.05, blue: 0.13),
                Color(red: 0.04, green: 0.14, blue: 0.29),
                Color(red: 0.02, green: 0.03, blue: 0.08)
            ]
        case .ocean:
            [
                Color(red: 0.00, green: 0.12, blue: 0.17),
                Color(red: 0.00, green: 0.28, blue: 0.33),
                Color(red: 0.01, green: 0.06, blue: 0.12)
            ]
        case .sunrise:
            [
                Color(red: 0.20, green: 0.08, blue: 0.18),
                Color(red: 0.45, green: 0.19, blue: 0.18),
                Color(red: 0.10, green: 0.05, blue: 0.12)
            ]
        case .garden:
            [
                Color(red: 0.02, green: 0.13, blue: 0.08),
                Color(red: 0.06, green: 0.25, blue: 0.14),
                Color(red: 0.01, green: 0.06, blue: 0.05)
            ]
        }
    }

    private var cardColors: [Color] {
        switch self {
        case .classic:
            [Color(red: 0.13, green: 0.24, blue: 0.45).opacity(0.52), Color.white.opacity(0.055)]
        case .ocean:
            [Color(red: 0.00, green: 0.46, blue: 0.53).opacity(0.34), Color.white.opacity(0.06)]
        case .sunrise:
            [Color(red: 0.76, green: 0.35, blue: 0.21).opacity(0.36), Color.white.opacity(0.07)]
        case .garden:
            [Color(red: 0.10, green: 0.46, blue: 0.22).opacity(0.34), Color.white.opacity(0.06)]
        }
    }

    var accent: Color {
        switch self {
        case .classic: Color(red: 0.12, green: 0.45, blue: 1.0)
        case .ocean: Color(red: 0.00, green: 0.78, blue: 0.86)
        case .sunrise: Color(red: 1.0, green: 0.72, blue: 0.12)
        case .garden: Color(red: 0.06, green: 0.78, blue: 0.30)
        }
    }

    var symbol: String {
        switch self {
        case .classic: "sparkles"
        case .ocean: "water.waves"
        case .sunrise: "sun.max.fill"
        case .garden: "leaf.fill"
        }
    }
}
