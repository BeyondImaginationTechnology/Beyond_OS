import SwiftUI

enum QuestRoute: Hashable {
    case destination(String)
}

private enum FrenchQuestScreen: Equatable {
    case mainMenu
    case game
    case settings
}

struct RootView: View {
    @EnvironmentObject private var store: QuestStore
    @Environment(\.scenePhase) private var scenePhase
    @State private var screen = FrenchQuestScreen.mainMenu

    var body: some View {
        Group {
            switch screen {
            case .mainMenu:
                MainMenuView(
                    onNewGame: {
                        store.resetProgress()
                        screen = .game
                    },
                    onLoadGame: { screen = .game },
                    onSettings: { screen = .settings }
                )
            case .game:
                NavigationStack {
                    WorldTourMapView(onMenu: { screen = .mainMenu })
                        .navigationDestination(for: QuestRoute.self) { route in
                            switch route {
                            case .destination(let regionID):
                                if let region = store.regions.first(where: { $0.id == regionID }) {
                                    DestinationAdventureView(region: region)
                                }
                            }
                        }
                }
            case .settings:
                GameSettingsView(onBack: { screen = .mainMenu })
            }
        }
        .animation(.easeInOut(duration: 0.25), value: screen)
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

private struct MainMenuView: View {
    @EnvironmentObject private var store: QuestStore
    let onNewGame: () -> Void
    let onLoadGame: () -> Void
    let onSettings: () -> Void
    @State private var confirmingNewGame = false

    var body: some View {
        ZStack {
            store.theme.background.ignoresSafeArea()

            Circle()
                .fill(store.theme.accent.opacity(0.16))
                .frame(width: 420, height: 420)
                .blur(radius: 70)
                .offset(x: -170, y: -310)

            Circle()
                .fill(Color.orange.opacity(0.10))
                .frame(width: 360, height: 360)
                .blur(radius: 80)
                .offset(x: 190, y: 330)

            ScrollView {
                VStack(spacing: 0) {
                    Spacer(minLength: 54)

                    Image("BeyondFrenchLogo")
                        .resizable()
                        .scaledToFill()
                        .frame(width: 154, height: 154)
                        .clipShape(Circle())
                        .overlay(Circle().stroke(.white.opacity(0.24), lineWidth: 2))
                        .shadow(color: store.theme.accent.opacity(0.52), radius: 32)

                    VStack(spacing: 8) {
                        Text("FRENCH QUEST")
                            .font(.system(size: 46, weight: .black, design: .rounded))
                            .minimumScaleFactor(0.75)
                            .lineLimit(1)
                        Text("THE WORLD TOUR")
                            .font(.caption.weight(.black))
                            .tracking(2.2)
                            .foregroundStyle(store.theme.accent)
                    }
                    .padding(.top, 22)

                    VStack(spacing: 12) {
                        MenuActionButton(title: "New Game", systemImage: "play.fill", color: store.theme.accent, isPrimary: true) {
                            confirmingNewGame = true
                        }

                        MenuActionButton(
                            title: "Load Game",
                            subtitle: store.hasSavedGame ? "Continue with \(store.xp) XP" : "No saved game found",
                            systemImage: "folder.fill",
                            color: .orange,
                            isEnabled: store.hasSavedGame,
                            action: onLoadGame
                        )

                        MenuActionButton(title: "Settings", systemImage: "gearshape.fill", color: .purple, action: onSettings)
                    }
                    .frame(maxWidth: 390)
                    .padding(.top, 48)

                    AppVersionLabel()
                        .padding(.top, 34)

                    Spacer(minLength: 40)
                }
                .frame(maxWidth: .infinity)
                .padding(.horizontal, 24)
            }
        }
        .foregroundStyle(.white)
        .alert("Start a new game?", isPresented: $confirmingNewGame) {
            Button("Cancel", role: .cancel) {}
            Button("New Game", role: .destructive, action: onNewGame)
        } message: {
            Text(store.hasSavedGame ? "This will erase the current local quest progress and begin again." : "Your adventure will begin from the first quest.")
        }
    }
}

private struct MenuActionButton: View {
    let title: String
    var subtitle: String? = nil
    let systemImage: String
    let color: Color
    var isEnabled = true
    var isPrimary = false
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            HStack(spacing: 14) {
                Image(systemName: systemImage)
                    .font(.title3.weight(.black))
                    .foregroundStyle(isPrimary ? .white : color)
                    .frame(width: 34)
                VStack(spacing: 2) {
                    Text(title.uppercased())
                        .font(.headline.weight(.black))
                        .tracking(0.8)
                    if let subtitle {
                        Text(subtitle)
                            .font(.caption2.weight(.semibold))
                            .foregroundStyle(.white.opacity(0.58))
                    }
                }
                Spacer()
                Color.clear.frame(width: 34, height: 1)
            }
            .padding(.horizontal, 20)
            .frame(maxWidth: .infinity, minHeight: isPrimary ? 72 : 62)
            .background(
                LinearGradient(
                    colors: isPrimary ? [storePrimaryColor, Color(red: 0.92, green: 0.22, blue: 0.17)] : [Color.white.opacity(0.12), Color.white.opacity(0.06)],
                    startPoint: .topLeading,
                    endPoint: .bottomTrailing
                ),
                in: Capsule()
            )
            .overlay(Capsule().stroke(isPrimary ? Color.white.opacity(0.30) : Color.white.opacity(0.14), lineWidth: 1))
            .shadow(color: isPrimary ? Color.orange.opacity(0.28) : .clear, radius: 18, y: 8)
        }
        .buttonStyle(.plain)
        .disabled(!isEnabled)
        .opacity(isEnabled ? 1 : 0.46)
    }

    private var storePrimaryColor: Color {
        Color(red: 1.0, green: 0.62, blue: 0.10)
    }
}

private struct GameSettingsView: View {
    @EnvironmentObject private var store: QuestStore
    let onBack: () -> Void

    var body: some View {
        ZStack {
            store.theme.background.ignoresSafeArea()
            ScrollView {
                VStack(alignment: .leading, spacing: 18) {
                    Button(action: onBack) {
                        Label("Main Menu", systemImage: "chevron.left")
                            .font(.headline.weight(.black))
                    }
                    .buttonStyle(.plain)

                    Text("SETTINGS")
                        .font(.system(size: 40, weight: .black, design: .rounded))

                    QuestCard {
                        HStack {
                            VStack(alignment: .leading, spacing: 4) {
                                Text("Music").font(.title3.weight(.black))
                                Text("French accordion soundtrack")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            Spacer()
                            Button {
                                store.toggleBackgroundMusic()
                            } label: {
                                Label(store.musicEnabled ? "On" : "Off", systemImage: store.musicEnabled ? "speaker.wave.2.fill" : "speaker.slash.fill")
                                    .font(.headline.weight(.black))
                                    .padding(.horizontal, 14)
                                    .padding(.vertical, 10)
                                    .background(store.theme.accent.opacity(0.2), in: Capsule())
                            }
                            .buttonStyle(.plain)
                        }
                    }

                    ThemePicker()

                    QuestCard {
                        VStack(alignment: .leading, spacing: 6) {
                            Text("French Quest").font(.headline.weight(.black))
                            AppVersionLabel()
                        }
                        .frame(maxWidth: .infinity, alignment: .leading)
                    }
                }
                .frame(maxWidth: 620)
                .padding(24)
            }
        }
        .foregroundStyle(.white)
    }
}

private struct AppVersionLabel: View {
    private var version: String {
        Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "Unknown"
    }

    private var build: String {
        Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String ?? "Unknown"
    }

    var body: some View {
        Text("Version \(version) · Build \(build)")
            .font(.caption.weight(.bold))
            .foregroundStyle(.white.opacity(0.55))
            .accessibilityLabel("French Quest version \(version), build \(build)")
    }
}

struct BrandHeader: View {
    @EnvironmentObject private var store: QuestStore
    var onMenu: (() -> Void)?

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
                Text("THE WORLD TOUR").font(.caption2.weight(.black)).foregroundStyle(store.theme.accent)
            }
            Spacer()
            if let onMenu {
                Button(action: onMenu) {
                    Image(systemName: "house.fill")
                        .font(.headline.weight(.black))
                        .foregroundStyle(store.theme.accent)
                        .frame(width: 42, height: 42)
                        .background(Color.white.opacity(0.08), in: Circle())
                        .overlay(Circle().stroke(Color.white.opacity(0.12), lineWidth: 1))
                }
                .buttonStyle(.plain)
                .accessibilityLabel("Return to main menu")
            }
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
