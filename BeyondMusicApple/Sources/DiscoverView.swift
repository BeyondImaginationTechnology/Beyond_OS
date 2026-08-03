import SwiftUI

struct DiscoverView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Discover") {
            MusicPanel {
                MusicEyebrow(text: "Search")
                Text("Find music")
                    .font(.largeTitle.bold())
                Text("Search Internet Archive and YouTube from one bar. Internet Archive files download directly; YouTube results use the configured converter API and remain subject to YouTube terms, rights-holder permissions, and applicable law.")
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
                    if store.hasBeyondID {
                        Text("BETA")
                            .font(.caption2.bold())
                            .padding(.horizontal, 8)
                            .padding(.vertical, 5)
                            .background(Color.musicAqua.opacity(0.16), in: Capsule())
                            .foregroundStyle(Color.musicAqua)
                    }
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

            TrackListView(
                title: "Results",
                tracks: store.filteredSearchResults,
                emptyTitle: "No search results loaded",
                emptyMessage: "Search a song, artist, genre, or YouTube URL. Downloads are saved into your local library.",
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
