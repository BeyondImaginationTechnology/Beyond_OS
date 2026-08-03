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
        guard selectedFilter != "All" else { return items }
        return items.filter { $0.type?.capitalized == selectedFilter }
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
            Text("Movies, seasons, specials, and direct streams ready to play.")
                .font(.title.bold())
                .lineLimit(3)
            Text("\(model.catalogItems.count) catalog titles · channels stay in the Watch tab")
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
                    Task { await model.play(catalog: item) }
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

                if let thumbnail = item.thumbnail {
                    AsyncImage(url: thumbnail) { phase in
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
                Label(item.sourceLabel ?? "Play", systemImage: item.videoURL == nil ? "safari.fill" : "play.fill")
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
