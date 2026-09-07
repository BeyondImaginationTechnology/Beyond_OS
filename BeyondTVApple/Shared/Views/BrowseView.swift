import AVFoundation
import SwiftUI

struct BrowseView: View {
    @EnvironmentObject private var model: AppModel
    @State private var selectedFilter = "All"

    private var filters: [String] {
        let types = Set(model.catalogItems.compactMap { $0.type?.capitalized })
        return ["All"] + Array(types).sorted()
    }

    private var filteredItems: [CatalogItem] {
        let items = model.catalogItems
        let typeFiltered = selectedFilter == "All"
            ? items
            : items.filter { $0.type?.capitalized == selectedFilter }
        #if os(tvOS)
        return typeFiltered.filter(\.isNativelyPlayable)
        #else
        return typeFiltered
        #endif
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 22) {
                    hero
                    filterPicker
                    catalogGrid
                }
                .padding()
            }
            .background(BeyondTVBackground().ignoresSafeArea())
            .navigationTitle("Browse")
            .toolbar {
                ToolbarItem(placement: .primaryAction) {
                    ThemeToggleButton()
                }
            }
            .overlay {
                if model.isCatalogLoading && model.catalogItems.isEmpty {
                    ProgressView("Loading stream catalog…")
                }
            }
            .task {
                await model.refreshCatalog()
            }
        }
    }

    private var hero: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("STREAM CATALOG")
                .font(.caption.bold())
                .tracking(2)
                .foregroundStyle(.orange)
            Text("Movies, seasons, specials, and every available source in one catalog.")
                .font(.title.bold())
                .lineLimit(3)
            Text(catalogSummary)
                .font(.subheadline)
                .foregroundStyle(.secondary)
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 18))
        .overlay {
            RoundedRectangle(cornerRadius: 18)
                .stroke(.white.opacity(0.12), lineWidth: 1)
        }
    }

    private var catalogSummary: String {
        #if os(tvOS)
        "\(filteredItems.count) direct-playback titles · web-player titles are omitted"
        #else
        "\(model.catalogItems.count) unlocked catalog titles · channels stay in the Watch tab"
        #endif
    }

    private var filterPicker: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 10) {
                ForEach(filters, id: \.self) { filter in
                    Button {
                        selectedFilter = filter
                    } label: {
                        Text(filter.uppercased())
                            .font(.caption.bold())
                            .tracking(0.8)
                            .padding(.horizontal, 14)
                            .padding(.vertical, 10)
                            .background(
                                selectedFilter == filter
                                    ? AnyShapeStyle(LinearGradient(colors: [.orange, .pink, .purple], startPoint: .topLeading, endPoint: .bottomTrailing))
                                    : AnyShapeStyle(.white.opacity(0.10)),
                                in: Capsule()
                            )
                    }
                    .buttonStyle(.plain)
                }
            }
        }
    }

    private var catalogGrid: some View {
        LazyVGrid(columns: [GridItem(.adaptive(minimum: 168), spacing: 14)], spacing: 14) {
            ForEach(filteredItems) { item in
                Button {
                    Task { await model.watch(catalog: item) }
                } label: {
                    CatalogCard(item: item)
                }
                .buttonStyle(.plain)
            }
        }
    }
}

private struct CatalogCard: View {
    let item: CatalogItem

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            ZStack(alignment: .topLeading) {
                RoundedRectangle(cornerRadius: 14)
                    .fill(.black.opacity(0.32))

                if item.prefersVideoFramePreview, let videoURL = item.videoURL {
                    VideoFramePreview(url: videoURL) { fallbackArt }
                } else if let artworkURL = item.preferredArtworkURL {
                    AsyncImage(url: artworkURL) { phase in
                        switch phase {
                        case .success(let image):
                            image
                                .resizable()
                                .scaledToFill()
                        case .failure:
                            fallbackArt
                        case .empty:
                            ProgressView()
                        @unknown default:
                            fallbackArt
                        }
                    }
                    .frame(maxWidth: .infinity)
                    .aspectRatio(16 / 10, contentMode: .fill)
                    .clipShape(RoundedRectangle(cornerRadius: 14))
                } else {
                    fallbackArt
                }

                Text(item.categoryLabel.uppercased())
                    .font(.caption2.bold())
                    .tracking(0.9)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 6)
                    .background(.black.opacity(0.66), in: Capsule())
                    .padding(8)
            }
            .aspectRatio(16 / 10, contentMode: .fit)

            VStack(alignment: .leading, spacing: 6) {
                Text([item.icon, item.title].compactMap { $0 }.joined(separator: " "))
                    .font(.headline)
                    .lineLimit(2)
                if !item.detailLine.isEmpty {
                    Text(item.detailLine)
                        .font(.caption.bold())
                        .foregroundStyle(.orange)
                        .lineLimit(1)
                }
                Text(item.description ?? item.subtitle ?? item.sourceLabel ?? "Playable Beyond TV catalog item.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    .lineLimit(3)
            }

            Spacer(minLength: 0)

            HStack {
                Label(
                    item.playbackActionLabel,
                    systemImage: item.isNativelyPlayable ? "play.fill" : "safari.fill"
                )
                    .font(.caption2.bold())
                    .lineLimit(1)
                Spacer()
                Image(systemName: "chevron.right")
                    .font(.caption.bold())
            }
            .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, minHeight: 298, alignment: .leading)
        .padding(12)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 18))
        .overlay {
            RoundedRectangle(cornerRadius: 18)
                .stroke(.white.opacity(0.12), lineWidth: 1)
        }
    }

    private var fallbackArt: some View {
        ZStack {
            LinearGradient(colors: [.purple.opacity(0.8), .orange.opacity(0.45)], startPoint: .topLeading, endPoint: .bottomTrailing)
            Text(item.icon ?? "▶")
                .font(.system(size: 42))
        }
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }
}

private struct VideoFramePreview<Fallback: View>: View {
    let url: URL
    let fallback: Fallback
    @StateObject private var loader = VideoFrameLoader()

    init(url: URL, @ViewBuilder fallback: () -> Fallback) {
        self.url = url
        self.fallback = fallback()
    }

    var body: some View {
        ZStack {
            if let frame = loader.frame {
                Image(decorative: frame, scale: 1)
                    .resizable()
                    .scaledToFill()
            } else if loader.didFail {
                fallback
            } else {
                fallback
                    .overlay { ProgressView().tint(.white) }
            }
        }
        .frame(maxWidth: .infinity)
        .aspectRatio(16 / 10, contentMode: .fill)
        .clipShape(RoundedRectangle(cornerRadius: 14))
        .task(id: url) {
            loader.load(url: url)
        }
    }
}

@MainActor
private final class VideoFrameLoader: ObservableObject {
    @Published var frame: CGImage?
    @Published var didFail = false
    private static var cache: [URL: CGImage] = [:]

    func load(url: URL) {
        if let cachedFrame = Self.cache[url] {
            frame = cachedFrame
            didFail = false
            return
        }
        frame = nil
        didFail = false

        DispatchQueue.global(qos: .utility).async { [weak self] in
            let asset = AVURLAsset(url: url)
            let generator = AVAssetImageGenerator(asset: asset)
            generator.appliesPreferredTrackTransform = true
            generator.maximumSize = CGSize(width: 640, height: 400)
            let time = CMTime(seconds: 8, preferredTimescale: 600)
            let image = try? generator.copyCGImage(at: time, actualTime: nil)

            DispatchQueue.main.async {
                guard let self else { return }
                self.frame = image
                self.didFail = image == nil
                if let image {
                    Self.cache[url] = image
                }
            }
        }
    }
}
