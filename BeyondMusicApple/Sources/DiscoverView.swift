import SwiftUI

struct DiscoverView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Discover") {
            MusicPanel {
                MusicEyebrow(text: "Search")
                Text("Find music")
                    .font(.largeTitle.bold())
                Text("Search downloadable archives, mixtapes, Audius, and ccMixter from one bar. Save playable files directly into your offline library when a provider exposes a download.")
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
                }
                Text(store.statusMessage)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            MusicPanel {
                HStack(spacing: 12) {
                    Image(systemName: store.hasBeyondID ? "checkmark.seal.fill" : "person.crop.circle.badge.exclamationmark")
                        .font(.title2)
                        .foregroundStyle(Color.musicAqua)
                    VStack(alignment: .leading, spacing: 4) {
                        MusicEyebrow(text: store.hasBeyondID ? "Signed In" : "Beyond ID")
                        Text(store.beyondIDSession.label)
                            .font(.headline)
                        Text(store.beyondIDDetailText)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                    Spacer()
                }
            }

            if store.isSearching {
                MusicPanel {
                    ProgressView()
                    Text("Searching music")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }

            MusicPanel {
                MusicEyebrow(text: "Mixtape Sources")
                Text("More places to dig")
                    .font(.title2.bold())
                Text("Use these sources for discovery and manual file collecting. In-app downloads stay limited to providers with direct playable files the app can verify.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
                VStack(alignment: .leading, spacing: 10) {
                    Link(destination: URL(string: "https://mixtapemonkey.com")!) {
                        Label("MixtapeMonkey", systemImage: "safari")
                    }
                    Link(destination: URL(string: "https://datpiff.com/?xs=1")!) {
                        Label("DatPiff Library", systemImage: "archivebox")
                    }
                    Link(destination: URL(string: "https://archive.org/details/hiphopmixtapes")!) {
                        Label("Internet Archive Hip-Hop Mixtapes", systemImage: "tray.and.arrow.down")
                    }
                }
                .font(.subheadline.weight(.semibold))
            }

            TrackListView(
                title: "Results",
                tracks: store.filteredSearchResults,
                emptyTitle: "No search results loaded",
                emptyMessage: "Search a song, artist, mixtape, genre, or lo-fi mood. Downloadable results save into your local library.",
                showsSource: true,
                headerAccessory: {
                    Picker("Provider", selection: $store.searchProviderFilter) {
                        ForEach(MusicProviderFilter.allCases) { filter in
                            Text("\(filter.rawValue) \(store.searchProviderCounts[filter, default: 0])").tag(filter)
                        }
                    }
                    .pickerStyle(.segmented)
                }
            )
        }
    }
}
