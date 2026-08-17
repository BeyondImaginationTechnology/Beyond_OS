import SwiftUI
import UniformTypeIdentifiers

struct LibraryView: View {
    @EnvironmentObject private var store: MusicStore
    @State private var showingImporter = false

    var body: some View {
        MusicScreen(title: "Library") {
            MusicPanel {
                HStack(alignment: .top, spacing: 14) {
                    ZStack {
                        RoundedRectangle(cornerRadius: 8)
                            .fill(.linearGradient(colors: [.musicAqua.opacity(0.88), .musicRose.opacity(0.82)], startPoint: .topLeading, endPoint: .bottomTrailing))
                        Image(systemName: "externaldrive.fill.badge.checkmark")
                            .font(.title2.weight(.black))
                            .foregroundStyle(.white)
                    }
                    .frame(width: 58, height: 58)
                    VStack(alignment: .leading, spacing: 8) {
                        MusicEyebrow(text: "Offline Vault")
                        Text("\(store.localTracks.count) saved songs")
                            .font(.largeTitle.bold())
                        Text(store.offlineReadinessText)
                            .foregroundStyle(.secondary)
                    }
                    Spacer()
                }

                HStack(spacing: 10) {
                    OfflineMetric(title: "Storage", value: store.offlineStorageText, systemImage: "internaldrive.fill")
                    OfflineMetric(title: "Downloaded", value: "\(store.downloadedTracks.count)", systemImage: "arrow.down.circle.fill")
                    OfflineMetric(title: "Imported", value: "\(store.importedTracks.count)", systemImage: "folder.fill")
                }

                Button {
                    showingImporter = true
                } label: {
                    Label("Import MP3 or audio files", systemImage: "square.and.arrow.down.fill")
                        .font(.headline)
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .disabled(store.isImporting)
            }

            TextField("Search songs, artists, albums, filenames", text: $store.searchText)
                .textFieldStyle(.roundedBorder)
                .textInputAutocapitalization(.never)

            MusicPanel {
                MusicEyebrow(text: "Saved view")
                Picker("Library filter", selection: $store.libraryFilter) {
                    ForEach(LibraryFilter.allCases) { filter in
                        Text(filter.rawValue).tag(filter)
                    }
                }
                .pickerStyle(.segmented)

                HStack {
                    Label("Sort", systemImage: "arrow.up.arrow.down")
                        .font(.caption.bold())
                        .foregroundStyle(.secondary)
                    Spacer()
                    Picker("Sort", selection: $store.librarySort) {
                        ForEach(LibrarySort.allCases) { sort in
                            Text(sort.rawValue).tag(sort)
                        }
                    }
                    .pickerStyle(.menu)
                }
            }

            MusicPanel {
                MusicEyebrow(text: "Offline shortcuts")
                HStack(spacing: 10) {
                    ShortcutButton(title: "Downloaded", systemImage: "arrow.down.circle.fill", filter: .downloaded)
                    ShortcutButton(title: "Imported", systemImage: "folder.fill", filter: .imported)
                    ShortcutButton(title: "Favorites", systemImage: "heart.fill", filter: .favorites)
                }
            }

            ScrollView(.horizontal, showsIndicators: false) {
                HStack {
                    MoodButton(title: "All", mood: nil)
                    ForEach(MusicMood.allCases) { mood in
                        MoodButton(title: mood.rawValue, mood: mood)
                    }
                }
            }

            TrackListView(
                title: "Local Songs",
                tracks: store.filteredTracks,
                emptyTitle: "No local songs yet",
                emptyMessage: "Import MP3, M4A, WAV, or AAC files from Files, or download search results under their source terms."
            )
        }
        .fileImporter(
            isPresented: $showingImporter,
            allowedContentTypes: [.mp3, .mpeg4Audio, .wav, .audio],
            allowsMultipleSelection: true
        ) { result in
            if case .success(let urls) = result {
                Task { await store.importAudioFiles(from: urls) }
            }
        }
    }
}

private struct OfflineMetric: View {
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

private struct ShortcutButton: View {
    @EnvironmentObject private var store: MusicStore
    let title: String
    let systemImage: String
    let filter: LibraryFilter

    var body: some View {
        Button {
            store.libraryFilter = filter
        } label: {
            Label(title, systemImage: systemImage)
                .font(.caption.bold())
                .frame(maxWidth: .infinity)
                .lineLimit(1)
                .minimumScaleFactor(0.72)
        }
        .buttonStyle(.bordered)
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

struct TrackListView<HeaderAccessory: View>: View {
    let title: String
    let tracks: [MusicTrack]
    var emptyTitle = "No tracks yet"
    var emptyMessage = "Search open music to find playable tracks."
    var showsSource = false
    @ViewBuilder var headerAccessory: () -> HeaderAccessory

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack(alignment: .center) {
                MusicEyebrow(text: title)
                Spacer()
            }
            headerAccessory()
            if tracks.isEmpty {
                MusicPanel {
                    Text(emptyTitle)
                        .font(.headline)
                    Text(emptyMessage)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            } else {
                ForEach(tracks) { track in
                    TrackRow(track: track, queue: tracks, showsSource: showsSource)
                }
            }
        }
    }
}

extension TrackListView where HeaderAccessory == EmptyView {
    init(
        title: String,
        tracks: [MusicTrack],
        emptyTitle: String = "No tracks yet",
        emptyMessage: String = "Search open music to find playable tracks.",
        showsSource: Bool = false
    ) {
        self.title = title
        self.tracks = tracks
        self.emptyTitle = emptyTitle
        self.emptyMessage = emptyMessage
        self.showsSource = showsSource
        self.headerAccessory = { EmptyView() }
    }
}

struct TrackRow: View {
    @EnvironmentObject private var store: MusicStore
    let track: MusicTrack
    let queue: [MusicTrack]
    var showsSource = false
    @State private var showingRemoveConfirmation = false

    var body: some View {
        MusicPanel {
            HStack(spacing: 12) {
                Button {
                    if store.currentTrack?.id == track.id {
                        store.togglePlayback()
                    } else {
                        store.play(track, in: queue)
                    }
                } label: {
                    Image(systemName: store.currentTrack?.id == track.id && store.isPlaying ? "pause.circle.fill" : "play.circle.fill")
                        .font(.title)
                        .foregroundStyle(Color.musicAqua)
                }
                .buttonStyle(.plain)
                .accessibilityLabel("Play \(track.title)")

                VStack(alignment: .leading, spacing: 4) {
                    Text(track.title)
                        .font(.headline)
                        .lineLimit(1)
                    Text("\(track.displayArtist) · \(track.displayAlbum)")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                        .lineLimit(1)
                    if store.isAvailableOffline(track) {
                        Text(store.localDetailText(for: track))
                            .font(.caption2.bold())
                            .foregroundStyle(Color.musicAqua)
                            .lineLimit(1)
                    }
                    if showsSource {
                        Text(track.provenanceText)
                            .font(.caption2)
                            .foregroundStyle(Color.musicGold)
                            .lineLimit(1)
                    }
                }

                Spacer()

                VStack(alignment: .trailing, spacing: 8) {
                    Text(track.durationText)
                        .font(.caption.monospacedDigit())
                        .foregroundStyle(.secondary)
                    HStack(spacing: 12) {
                        Button {
                            store.toggleFavorite(track)
                        } label: {
                            Image(systemName: track.isFavorite ? "heart.fill" : "heart")
                                .font(.title3)
                                .foregroundStyle(track.isFavorite ? Color.musicRose : .secondary)
                        }
                        .buttonStyle(.plain)
                        .accessibilityLabel(track.isFavorite ? "Remove favorite" : "Save favorite")

                        if store.isAvailableOffline(track) {
                            Button(role: .destructive) {
                                showingRemoveConfirmation = true
                            } label: {
                                Image(systemName: "trash")
                                    .font(.title3)
                            }
                            .buttonStyle(.plain)
                            .foregroundStyle(.red)
                            .accessibilityLabel("Remove \(track.title) from device")
                        }

                        DownloadButton(track: track)
                    }
                }
            }
        }
        .confirmationDialog(
            "Remove this song from your device?",
            isPresented: $showingRemoveConfirmation,
            titleVisibility: .visible
        ) {
            Button("Remove Download", role: .destructive) {
                store.remove(track)
            }
            Button("Cancel", role: .cancel) {}
        } message: {
            Text("This deletes the saved audio file from Beyond Music. You can download or import it again later if the source is still available.")
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
        (track.downloadURL == nil && track.providerName != "YouTube") || store.downloadState(for: track) == .downloading || store.isAvailableOffline(track)
    }
}
