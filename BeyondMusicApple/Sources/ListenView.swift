import SwiftUI

struct ListenView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Listen") {
            MusicPanel {
                MusicEyebrow(text: store.currentTrack == nil ? "Ready" : "Now playing")
                Text(store.currentTrack?.title ?? "Import your first song")
                    .font(.system(size: 34, weight: .black))
                Text(store.currentTrack?.displayArtist ?? "Use Library to add MP3, M4A, WAV, or AAC files.")
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
                    .disabled(store.currentTrack == nil)

                    if let track = store.currentTrack {
                        Button {
                            Task { await store.download(track) }
                        } label: {
                            Image(systemName: "arrow.down.circle.fill")
                                .font(.title2)
                                .frame(width: 48, height: 48)
                        }
                        .buttonStyle(.bordered)
                        .disabled(track.downloadURL == nil || store.isAvailableOffline(track))
                        .accessibilityLabel("Download current track")
                    }
                }
            }

            VStack(alignment: .leading, spacing: 12) {
                MusicEyebrow(text: "Local playlists")
                ForEach(store.playlists) { playlist in
                    PlaylistRow(playlist: playlist)
                }
            }
        }
    }
}

private struct PlaylistRow: View {
    @EnvironmentObject private var store: MusicStore
    let playlist: MusicPlaylist

    var body: some View {
        NavigationLink {
            TrackListView(
                title: playlist.title,
                tracks: store.tracks(for: playlist),
                emptyTitle: "Nothing here yet",
                emptyMessage: "Imported and downloaded files will appear here automatically."
            )
        } label: {
            MusicPanel {
                HStack(spacing: 12) {
                    Image(systemName: playlist.systemImage)
                        .font(.title2)
                        .foregroundStyle(Color.musicAqua)
                        .frame(width: 40)
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
