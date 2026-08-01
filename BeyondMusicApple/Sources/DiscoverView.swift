import SwiftUI

struct DiscoverView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Discover") {
            MusicPanel {
                MusicEyebrow(text: "Search")
                Text("Find authorized music")
                    .font(.largeTitle.bold())
                Text("MVP search mixes public audio catalogs and optional app-key providers. Review licenses before publishing or commercial reuse.")
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

            if store.isSearching {
                MusicPanel {
                    ProgressView()
                    Text("Searching open music")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }

            TrackListView(title: "Results", tracks: store.searchResults)

            VStack(alignment: .leading, spacing: 12) {
                MusicEyebrow(text: "Artists")
                ForEach(store.artists) { artist in
                    MusicPanel {
                        HStack {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(artist.name)
                                    .font(.headline)
                                Text(artist.note)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            Spacer()
                            Text(artist.monthlyListeners.compactCountText)
                                .font(.caption.bold())
                                .foregroundStyle(Color.musicGold)
                        }
                    }
                }
            }
        }
    }
}
