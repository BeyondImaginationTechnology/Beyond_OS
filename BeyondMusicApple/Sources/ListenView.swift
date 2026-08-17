import SwiftUI

struct ListenView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Listen") {
            MusicPanel {
                HStack(alignment: .center, spacing: 16) {
                    NowPlayingArtwork(track: store.currentTrack)
                    VStack(alignment: .leading, spacing: 7) {
                        MusicEyebrow(text: store.currentTrack == nil ? "Offline player" : "Now playing")
                        Text(store.currentTrack?.title ?? "Import your first song")
                            .font(.system(size: 30, weight: .black))
                            .lineLimit(2)
                            .minimumScaleFactor(0.74)
                        Text(store.currentTrack?.displayArtist ?? "Use Library to add MP3, M4A, WAV, or AAC files.")
                            .foregroundStyle(.secondary)
                            .lineLimit(2)
                        if let track = store.currentTrack, store.isAvailableOffline(track) {
                            Label(store.localDetailText(for: track), systemImage: "checkmark.circle.fill")
                                .font(.caption.bold())
                                .foregroundStyle(Color.musicAqua)
                        }
                    }
                }
                HStack(spacing: 12) {
                    Button {
                        store.playPrevious()
                    } label: {
                        Image(systemName: "backward.fill")
                            .font(.headline)
                            .frame(width: 48, height: 48)
                    }
                    .buttonStyle(.bordered)
                    .disabled(!store.hasPreviousTrack)
                    .accessibilityLabel("Previous song")

                    Button {
                        store.togglePlayback()
                    } label: {
                        Label(store.isPlaying ? "Pause" : "Play", systemImage: store.isPlaying ? "pause.fill" : "play.fill")
                            .font(.headline)
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(store.currentTrack == nil)

                    Button {
                        store.playNext()
                    } label: {
                        Image(systemName: "forward.fill")
                            .font(.headline)
                            .frame(width: 48, height: 48)
                    }
                    .buttonStyle(.bordered)
                    .disabled(!store.hasNextTrack)
                    .accessibilityLabel("Next song")

                    if let track = store.currentTrack {
                        Button {
                            Task { await store.download(track) }
                        } label: {
                            Image(systemName: "arrow.down.circle.fill")
                                .font(.title2)
                                .frame(width: 48, height: 48)
                        }
                        .buttonStyle(.bordered)
                        .disabled((track.downloadURL == nil && track.providerName != "YouTube") || store.isAvailableOffline(track))
                        .accessibilityLabel("Download current track")
                    }
                }
            }

            MusicPanel {
                MusicEyebrow(text: "Offline listening")
                HStack(spacing: 10) {
                    ListenMetric(title: "Ready", value: "\(store.localTracks.count)", systemImage: "checkmark.circle.fill")
                    ListenMetric(title: "Minutes", value: "\(store.totalLibraryMinutes)", systemImage: "clock.fill")
                    ListenMetric(title: "Storage", value: store.offlineStorageText, systemImage: "internaldrive.fill")
                }
            }

            TrackListView(
                title: "Offline Queue",
                tracks: Array(store.localTracks.prefix(10)),
                emptyTitle: "No offline queue yet",
                emptyMessage: "Import MP3s or download tracks, then your saved songs will be ready here."
            )

            TrackListView(
                title: "Recently Played",
                tracks: Array(store.recentlyPlayedTracks.prefix(6)),
                emptyTitle: "Nothing played yet",
                emptyMessage: "Start a local or downloaded track to build your listening history."
            )

            VStack(alignment: .leading, spacing: 12) {
                MusicEyebrow(text: "Playlists")
                ForEach(store.playlists) { playlist in
                    PlaylistRow(playlist: playlist)
                }
            }
        }
    }
}

private struct NowPlayingArtwork: View {
    let track: MusicTrack?

    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 8)
                .fill(.linearGradient(colors: [.musicPanelSoft, .musicAqua.opacity(0.28)], startPoint: .topLeading, endPoint: .bottomTrailing))
            if let artworkURL = track?.artworkURL {
                AsyncImage(url: artworkURL) { image in
                    image
                        .resizable()
                        .scaledToFill()
                } placeholder: {
                    Image(systemName: "music.note")
                        .font(.largeTitle.bold())
                        .foregroundStyle(Color.musicAqua)
                }
            } else {
                Image(systemName: "music.note")
                    .font(.largeTitle.bold())
                    .foregroundStyle(Color.musicAqua)
            }
        }
        .frame(width: 112, height: 112)
        .clipShape(RoundedRectangle(cornerRadius: 8))
    }
}

private struct ListenMetric: View {
    let title: String
    let value: String
    let systemImage: String

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Image(systemName: systemImage)
                .foregroundStyle(Color.musicAqua)
            Text(value)
                .font(.headline.bold())
                .lineLimit(1)
                .minimumScaleFactor(0.7)
            Text(title)
                .font(.caption2.bold())
                .foregroundStyle(.secondary)
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.musicPanelSoft, in: RoundedRectangle(cornerRadius: 8))
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
