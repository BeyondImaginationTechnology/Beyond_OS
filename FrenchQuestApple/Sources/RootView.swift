import SwiftUI

enum QuestRoute: Hashable {
    case map
    case spellbook
    case training
    case hero
}

struct RootView: View {
    @EnvironmentObject private var store: QuestStore
    @Environment(\.scenePhase) private var scenePhase

    var body: some View {
        NavigationStack {
            TodayView()
                .navigationDestination(for: QuestRoute.self) { route in
                    switch route {
                    case .map:
                        AcademyView()
                    case .spellbook:
                        DictionaryView()
                    case .training:
                        PracticeView()
                    case .hero:
                        LearningProgressView()
                    }
                }
        }
        .tint(store.theme.accent)
        .preferredColorScheme(.dark)
        .onAppear {
            store.startBackgroundMusicIfNeeded()
        }
        .onChange(of: scenePhase) { _, phase in
            if phase == .active {
                store.startBackgroundMusicIfNeeded()
            } else {
                store.pauseBackgroundMusic()
            }
        }
    }
}

struct BrandHeader: View {
    @EnvironmentObject private var store: QuestStore

    var body: some View {
        HStack(spacing: 12) {
            Image("BeyondFrenchLogo")
                .resizable()
                .scaledToFill()
                .frame(width: 58, height: 58)
                .clipShape(RoundedRectangle(cornerRadius: 17))
                .overlay(RoundedRectangle(cornerRadius: 17).stroke(.white.opacity(0.28), lineWidth: 1))
                .shadow(color: store.theme.accent.opacity(0.38), radius: 12)
            VStack(alignment: .leading, spacing: 2) {
                Text("FRENCH QUEST").font(.headline.weight(.black))
                Text("THE LOST REALMS OF FRANCE").font(.caption2.weight(.black)).foregroundStyle(store.theme.accent)
            }
            Spacer()
            Button {
                store.toggleBackgroundMusic()
            } label: {
                Image(systemName: store.musicEnabled ? "music.note" : "speaker.slash.fill")
                    .font(.headline.weight(.black))
                    .foregroundStyle(store.musicEnabled ? store.theme.accent : .secondary)
                    .frame(width: 42, height: 42)
                    .background(Color.white.opacity(0.08), in: Circle())
                    .overlay(Circle().stroke(Color.white.opacity(0.12), lineWidth: 1))
            }
            .buttonStyle(.plain)
            .accessibilityLabel(store.musicEnabled ? "Turn music off" : "Turn music on")
            NavigationLink(value: QuestRoute.hero) {
                ZStack {
                    Circle().fill(store.theme.accent.opacity(0.20))
                    Image(systemName: "person.crop.circle.fill.badge.checkmark")
                        .font(.title2)
                        .foregroundStyle(store.theme.accent)
                }
                .frame(width: 42, height: 42)
                .overlay(Circle().stroke(store.theme.accent.opacity(0.35), lineWidth: 1))
            }
            .accessibilityLabel("Open hero profile")
        }
        .foregroundStyle(.white)
    }
}

struct AdventurePortalGrid: View {
    @EnvironmentObject private var store: QuestStore

    private let portals: [(QuestRoute, String, String, String, Color)] = [
        (.map, "Quest Map", "Unlock the next realm", "map.fill", .cyan),
        (.spellbook, "Spellbook", "158 words and phrases", "book.closed.fill", .purple),
        (.training, "Training Grounds", "Sharpen your recall", "bolt.fill", .orange),
        (.hero, "Hero Lodge", "Gear, stats, and themes", "shield.lefthalf.filled", .green)
    ]

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Text("EXPLORE")
                    .font(.caption.weight(.black))
                    .tracking(1.8)
                    .foregroundStyle(store.theme.accent)
                Rectangle().fill(store.theme.accent.opacity(0.35)).frame(height: 1)
            }

            LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
                ForEach(Array(portals.enumerated()), id: \.offset) { _, portal in
                    NavigationLink(value: portal.0) {
                        VStack(alignment: .leading, spacing: 10) {
                            HStack {
                                Image(systemName: portal.3)
                                    .font(.title2.weight(.black))
                                    .foregroundStyle(portal.4)
                                Spacer()
                                Image(systemName: "chevron.right")
                                    .font(.caption.weight(.black))
                                    .foregroundStyle(.white.opacity(0.55))
                            }
                            Text(portal.1)
                                .font(.headline.weight(.black))
                                .foregroundStyle(.white)
                            Text(portal.2)
                                .font(.caption)
                                .foregroundStyle(.white.opacity(0.66))
                                .lineLimit(2)
                        }
                        .frame(maxWidth: .infinity, minHeight: 104, alignment: .leading)
                        .padding(15)
                        .background(
                            LinearGradient(
                                colors: [portal.4.opacity(0.25), Color.white.opacity(0.05)],
                                startPoint: .topLeading,
                                endPoint: .bottomTrailing
                            ),
                            in: RoundedRectangle(cornerRadius: 20)
                        )
                        .overlay(RoundedRectangle(cornerRadius: 20).stroke(portal.4.opacity(0.32), lineWidth: 1))
                    }
                    .buttonStyle(.plain)
                }
            }
        }
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
