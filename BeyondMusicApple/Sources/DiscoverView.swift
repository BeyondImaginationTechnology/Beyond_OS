import SwiftUI

struct DiscoverView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Discover") {
            MusicPanel {
                MusicEyebrow(text: "Search")
                Text("Find authorized music")
                    .font(.largeTitle.bold())
                Text("Search open audio catalogs and optional app-key providers. Downloaded results are copied into your local library after you review the source license.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
                HStack {
                    TextField("Try jazz, piano, lo-fi", text: $store.searchText)
                        .textFieldStyle(.roundedBorder)
                        .textInputAutocapitalization(.never)
                    Button {
                        Task { await store.searchOpenMusic() }
                    } label: {
                        Image(systemName: "magnifyingglass")
                            .frame(width: 42, height: 42)
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(store.isSearching)
                    .accessibilityLabel("Search music")
                }
                HStack {
                    Button {
                        Task { await store.loadNextSearchPage() }
                    } label: {
                        Label("Next page", systemImage: "arrow.right.circle")
                    }
                    .buttonStyle(.bordered)
                    .disabled(store.searchResults.isEmpty || store.isSearching)

                    Button {
                        Task { await store.surpriseMe() }
                    } label: {
                        Label("Random audio", systemImage: "shuffle.circle")
                    }
                    .buttonStyle(.bordered)
                    .disabled(store.isSearching)
                }
                Text(store.statusMessage)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            MusicPanel {
                HStack(spacing: 12) {
                    Image(systemName: store.hasBeyondID ? "person.crop.circle.badge.checkmark" : "person.crop.circle.badge.exclamationmark")
                        .font(.title2)
                        .foregroundStyle(Color.musicAqua)
                    VStack(alignment: .leading, spacing: 4) {
                        MusicEyebrow(text: "Beyond ID")
                        Text(store.beyondIDSession.label)
                            .font(.headline)
                        Text(store.hasBeyondID ? "Searches and local choices can stay associated with your Beyond ID beta profile." : "Connect on Profile when you are ready to pair this device.")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                }
            }

            if store.isSearching {
                MusicPanel {
                    ProgressView()
                    Text("Searching open music")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }

            TrackListView(
                title: "Results",
                tracks: store.searchResults,
                emptyTitle: "No search results loaded",
                emptyMessage: "Search a song, artist, or genre. Downloaded results are saved into your local library."
            )
        }
    }
}
