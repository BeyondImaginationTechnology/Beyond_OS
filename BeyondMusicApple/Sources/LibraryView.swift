import SwiftUI

struct LibraryView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Library") {
            MusicPanel {
                MusicEyebrow(text: "Personal library")
                Text("\(store.allTracks.count) tracks")
                    .font(.largeTitle.bold())
                Text("\(store.downloadedTracks.count) downloaded · \(store.totalLibraryMinutes) minutes indexed")
                    .foregroundStyle(.secondary)
            }

            TextField("Search tracks, artists, albums", text: $store.searchText)
                .textFieldStyle(.roundedBorder)
                .textInputAutocapitalization(.never)

            ScrollView(.horizontal, showsIndicators: false) {
                HStack {
                    MoodButton(title: "All", mood: nil)
                    ForEach(MusicMood.allCases) { mood in
                        MoodButton(title: mood.rawValue, mood: mood)
                    }
                }
            }

            TrackListView(title: "Tracks", tracks: store.filteredTracks)
        }
    }
}

private struct MoodButton: View {
    @EnvironmentObject private var store: MusicStore
    let title: String
    let mood: MusicMood?

    var body: some View {
        Button {
            store.selectedMood = mood
        } label: {
            Text(title)
                .font(.caption.bold())
                .padding(.horizontal, 12)
                .padding(.vertical, 8)
                .background(store.selectedMood == mood ? Color.musicAqua : Color.musicPanelSoft, in: Capsule())
                .foregroundStyle(store.selectedMood == mood ? Color.musicBackground : .primary)
        }
        .buttonStyle(.plain)
    }
}

struct TrackListView: View {
    let title: String
    let tracks: [MusicTrack]

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            MusicEyebrow(text: title)
            if tracks.isEmpty {
                MusicPanel {
                    Text("No tracks yet")
                        .font(.headline)
                    Text("Search open music to add playable tracks.")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            } else {
                ForEach(tracks) { track in
                    TrackRow(track: track)
                }
            }
        }
    }
}

struct TrackRow: View {
    @EnvironmentObject private var store: MusicStore
    let track: MusicTrack

    var body: some View {
        MusicPanel {
            HStack(spacing: 12) {
                Button {
                    store.play(track)
                } label: {
                    Image(systemName: store.currentTrack.id == track.id && store.isPlaying ? "pause.circle.fill" : "play.circle.fill")
                        .font(.title)
                        .foregroundStyle(Color.musicAqua)
                }
                .buttonStyle(.plain)
                .accessibilityLabel("Play \(track.title)")

                VStack(alignment: .leading, spacing: 4) {
                    Text(track.title)
                        .font(.headline)
                    Text("\(track.artist) · \(track.album)")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Text("\(track.providerName) · \(track.licenseNote)")
                        .font(.caption2)
                        .foregroundStyle(Color.musicGold)
                        .lineLimit(1)
                }

                Spacer()

                VStack(alignment: .trailing, spacing: 8) {
                    Text(track.durationText)
                        .font(.caption.monospacedDigit())
                        .foregroundStyle(.secondary)
                    DownloadButton(track: track)
                }
            }
        }
    }
}

struct DownloadButton: View {
    @EnvironmentObject private var store: MusicStore
    let track: MusicTrack

    var body: some View {
        Button {
            Task { await store.download(track) }
        } label: {
            image
                .font(.title3)
        }
        .buttonStyle(.plain)
        .disabled(isDisabled)
        .accessibilityLabel("Download \(track.title)")
    }

    private var image: Image {
        switch store.downloadState(for: track) {
        case .idle: Image(systemName: "arrow.down.circle")
        case .downloading: Image(systemName: "hourglass.circle")
        case .downloaded: Image(systemName: "checkmark.circle.fill")
        case .failed: Image(systemName: "exclamationmark.circle")
        }
    }

    private var isDisabled: Bool {
        track.downloadURL == nil || store.downloadState(for: track) == .downloading || store.isAvailableOffline(track)
    }
}
