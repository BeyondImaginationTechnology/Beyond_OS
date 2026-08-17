import SwiftUI

struct RootView: View {
    @EnvironmentObject private var store: QuestStore

    var body: some View {
        TabView {
            NavigationStack { TodayView() }
                .tabItem { Label("Quest", systemImage: "map.fill") }
            NavigationStack { AcademyView() }
                .tabItem { Label("Map", systemImage: "flag.checkered") }
            NavigationStack { DictionaryView() }
                .tabItem { Label("Phrases", systemImage: "character.book.closed.fill") }
            NavigationStack { PracticeView() }
                .tabItem { Label("Train", systemImage: "bolt.fill") }
            NavigationStack { LearningProgressView() }
                .tabItem { Label("Hero", systemImage: "person.crop.circle.fill.badge.checkmark") }
        }
        .tint(store.theme.accent)
        .preferredColorScheme(.dark)
    }
}

struct BrandHeader: View {
    @EnvironmentObject private var store: QuestStore

    var body: some View {
        HStack(spacing: 12) {
            Image(systemName: "sparkles")
                .font(.title2.weight(.black))
                .foregroundStyle(.white)
                .frame(width: 54, height: 54)
                .background(store.theme.accent, in: RoundedRectangle(cornerRadius: 16))
            VStack(alignment: .leading, spacing: 2) {
                Text("FRENCH QUEST").font(.headline.weight(.black))
                Text("Play your way into French").font(.caption).foregroundStyle(.secondary)
            }
            Spacer()
            Image(systemName: store.theme.symbol)
                .font(.headline)
                .foregroundStyle(store.theme.accent)
                .frame(width: 34, height: 34)
                .background(store.theme.accent.opacity(0.20), in: Circle())
        }
        .foregroundStyle(.white)
    }
}

struct QuestStatBar: View {
    @EnvironmentObject private var store: QuestStore

    var body: some View {
        HStack(spacing: 10) {
            StatPill(value: "\(store.xp)", label: "XP", systemImage: "sparkles", color: store.theme.accent)
            StatPill(value: "\(store.hearts)", label: "Hearts", systemImage: "heart.fill", color: .red)
            StatPill(value: "\(store.streak)", label: "Streak", systemImage: "flame.fill", color: .orange)
        }
    }
}

struct StatPill: View {
    let value: String
    let label: String
    let systemImage: String
    let color: Color

    var body: some View {
        HStack(spacing: 7) {
            Image(systemName: systemImage).foregroundStyle(color)
            VStack(alignment: .leading, spacing: 0) {
                Text(value).font(.headline.weight(.black))
                Text(label.uppercased()).font(.caption2.weight(.bold)).foregroundStyle(.secondary)
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(10)
        .background(Color.white.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
        .overlay(RoundedRectangle(cornerRadius: 14).stroke(color.opacity(0.18), lineWidth: 1))
    }
}

struct QuestCard<Content: View>: View {
    @EnvironmentObject private var store: QuestStore
    let content: Content

    init(@ViewBuilder content: () -> Content) {
        self.content = content()
    }

    var body: some View {
        content
            .padding(18)
            .background(store.theme.card, in: RoundedRectangle(cornerRadius: 22))
            .overlay(RoundedRectangle(cornerRadius: 22).stroke(store.theme.accent.opacity(0.18), lineWidth: 1))
    }
}

struct ThemePicker: View {
    @EnvironmentObject private var store: QuestStore

    var body: some View {
        QuestCard {
            VStack(alignment: .leading, spacing: 12) {
                Text("Theme").font(.title3.weight(.black))
                Picker("Theme", selection: $store.theme) {
                    ForEach(QuestTheme.allCases) { theme in
                        Label(theme.title, systemImage: theme.symbol).tag(theme)
                    }
                }
                .pickerStyle(.segmented)
            }
        }
    }
}
