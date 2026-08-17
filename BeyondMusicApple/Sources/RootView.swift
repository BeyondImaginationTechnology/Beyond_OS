import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { ListenView() }
                .tabItem { Label("Listen", systemImage: "play.circle.fill") }
            NavigationStack { LibraryView() }
                .tabItem { Label("Library", systemImage: "music.note.list") }
            NavigationStack { DiscoverView() }
                .tabItem { Label("Discover", systemImage: "sparkles") }
            NavigationStack { ProfileView() }
                .tabItem { Label("Profile", systemImage: "person.crop.circle.fill") }
        }
    }
}

struct MusicScreen<Content: View>: View {
    let title: String
    @ViewBuilder var content: Content

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                MusicHeader()
                content
                MiniPlayer()
            }
            .padding()
        }
        .background(Color.musicBackground.ignoresSafeArea())
        .navigationTitle(title)
    }
}

struct MusicHeader: View {
    var body: some View {
        HStack(spacing: 12) {
            ZStack {
                RoundedRectangle(cornerRadius: 8)
                    .fill(.linearGradient(colors: [.musicRose, .musicAqua], startPoint: .topLeading, endPoint: .bottomTrailing))
                Image(systemName: "waveform")
                    .font(.title2.weight(.black))
                    .foregroundStyle(.white)
            }
            .frame(width: 54, height: 54)

            VStack(alignment: .leading, spacing: 2) {
                Text("BEYOND MUSIC")
                    .font(.headline.weight(.black))
                Text("Search, save, import, and private listening")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Spacer()

            Text("1.1.1")
                .font(.caption.bold())
                .padding(.horizontal, 10)
                .padding(.vertical, 6)
                .background(Color.musicAqua.opacity(0.16), in: Capsule())
        }
    }
}

struct MusicPanel<Content: View>: View {
    @ViewBuilder var content: Content

    var body: some View {
        VStack(alignment: .leading, spacing: 14) {
            content
        }
        .padding(18)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.musicPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}

struct MusicEyebrow: View {
    let text: String

    var body: some View {
        Text(text.uppercased())
            .font(.caption.weight(.black))
            .tracking(1.2)
            .foregroundStyle(Color.musicAqua)
    }
}

struct MiniPlayer: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicPanel {
            HStack(spacing: 12) {
                Image(systemName: store.isPlaying ? "waveform.circle.fill" : "music.note")
                    .font(.title)
                    .foregroundStyle(Color.musicAqua)
                    .frame(width: 42)
                VStack(alignment: .leading, spacing: 3) {
                    Text(store.currentTrack?.title ?? "No song selected")
                        .font(.headline)
                    Text(store.currentTrack?.displayArtist ?? "Import or download music")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Spacer()
                Button {
                    store.playPrevious()
                } label: {
                    Image(systemName: "backward.fill")
                        .font(.subheadline)
                        .frame(width: 34, height: 34)
                }
                .buttonStyle(.plain)
                .foregroundStyle(store.hasPreviousTrack ? Color.musicAqua : .secondary)
                .disabled(!store.hasPreviousTrack)
                .accessibilityLabel("Previous song")

                Button {
                    store.togglePlayback()
                } label: {
                    Image(systemName: store.isPlaying ? "pause.fill" : "play.fill")
                        .font(.headline)
                        .frame(width: 42, height: 42)
                        .background(Color.musicAqua, in: Circle())
                        .foregroundStyle(Color.musicBackground)
                }
                .buttonStyle(.plain)
                .disabled(store.currentTrack == nil)
                .accessibilityLabel(store.isPlaying ? "Pause" : "Play")

                Button {
                    store.playNext()
                } label: {
                    Image(systemName: "forward.fill")
                        .font(.subheadline)
                        .frame(width: 34, height: 34)
                }
                .buttonStyle(.plain)
                .foregroundStyle(store.hasNextTrack ? Color.musicAqua : .secondary)
                .disabled(!store.hasNextTrack)
                .accessibilityLabel("Next song")
            }
        }
    }
}

extension Color {
    static let musicBackground = Color(red: 0.055, green: 0.030, blue: 0.050)
    static let musicPanel = Color(red: 0.130, green: 0.070, blue: 0.115)
    static let musicPanelSoft = Color(red: 0.185, green: 0.095, blue: 0.160)
    static let musicAqua = Color(red: 1.000, green: 0.255, blue: 0.610)
    static let musicRose = Color(red: 0.970, green: 0.120, blue: 0.455)
    static let musicGold = Color(red: 1.000, green: 0.690, blue: 0.360)
}
