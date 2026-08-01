import SwiftUI

struct ListenView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Listen") {
            MusicPanel {
                MusicEyebrow(text: "Now playing")
                Text(store.currentTrack.title)
                    .font(.system(size: 34, weight: .black))
                Text(store.currentTrack.artist)
                    .foregroundStyle(.secondary)
                HStack {
                    Button {
                        store.togglePlayback()
                    } label: {
                        Label(store.isPlaying ? "Pause" : "Play", systemImage: store.isPlaying ? "pause.fill" : "play.fill")
                            .font(.headline)
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)

                    Button {
                        Task { await store.download(store.currentTrack) }
                    } label: {
                        Image(systemName: "arrow.down.circle.fill")
                            .font(.title2)
                            .frame(width: 48, height: 48)
                    }
                    .buttonStyle(.bordered)
                    .disabled(store.currentTrack.downloadURL == nil || store.isAvailableOffline(store.currentTrack))
                    .accessibilityLabel("Download current track")
                }
            }

            VStack(alignment: .leading, spacing: 12) {
                MusicEyebrow(text: "Stations")
                ForEach(store.stations) { station in
                    StationRow(station: station)
                }
            }

            VStack(alignment: .leading, spacing: 12) {
                MusicEyebrow(text: "Playlists")
                ForEach(store.playlists) { playlist in
                    PlaylistRow(playlist: playlist)
                }
            }
        }
    }
}

private struct StationRow: View {
    let station: MusicStation

    var body: some View {
        MusicPanel {
            HStack(spacing: 12) {
                Image(systemName: station.mood.systemImage)
                    .font(.title2)
                    .foregroundStyle(Color.musicAqua)
                    .frame(width: 40)
                VStack(alignment: .leading, spacing: 4) {
                    Text(station.name)
                        .font(.headline)
                    Text(station.tagline)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Spacer()
                Text(station.listenerCount.compactCountText)
                    .font(.caption.bold())
                    .foregroundStyle(Color.musicGold)
            }
        }
    }
}

private struct PlaylistRow: View {
    @EnvironmentObject private var store: MusicStore
    let playlist: MusicPlaylist

    var body: some View {
        NavigationLink {
            TrackListView(title: playlist.title, tracks: store.tracks(for: playlist))
        } label: {
            MusicPanel {
                HStack {
                    VStack(alignment: .leading, spacing: 4) {
                        Text(playlist.title)
                            .font(.headline)
                        Text(playlist.subtitle)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                    Spacer()
                    Image(systemName: "chevron.right")
                        .foregroundStyle(.secondary)
                }
            }
        }
        .buttonStyle(.plain)
    }
}
