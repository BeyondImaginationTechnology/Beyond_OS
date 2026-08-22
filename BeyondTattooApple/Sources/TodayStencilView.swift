import SwiftUI

struct TodayStencilView: View {
    @EnvironmentObject private var store: TattooStore

    private var packFileCount: Int {
        2 + [store.dailyDrop.transferURL, store.dailyDrop.transferPDFURL, store.dailyDrop.referenceURL, store.dailyDrop.placementImageURL, store.dailyDrop.packURL, store.dailyDrop.loreURL, store.dailyDrop.styleCardURL].compactMap { $0 }.count
    }

    var body: some View {
        TattooScreen(title: "Today") {
            HeroStencilCard(stencil: store.dailyDrop, isSaved: store.savedStencilIDs.contains(store.dailyDrop.id)) {
                store.toggleSaved(store.dailyDrop)
            }

            LazyVGrid(columns: [GridItem(.adaptive(minimum: 150), spacing: 12)], spacing: 12) {
                MetricTile(value: "\(store.dailyDrop.rewardBits)", label: "Reward bits", icon: "bitcoinsign.circle.fill")
                MetricTile(value: "\(packFileCount)", label: "Actual files", icon: "tray.full.fill")
                MetricTile(value: store.dailyDrop.transferPDFURL == nil ? "PNG" : "PDF", label: "Transfer ready", icon: "doc.richtext.fill")
            }

            VStack(alignment: .leading, spacing: 12) {
                SectionTitle(text: "Studio pack")
                Link(destination: store.dailyDrop.packageURL) {
                    Label("Download complete pack", systemImage: "square.and.arrow.down.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .controlSize(.large)

                HStack(spacing: 10) {
                    Link(destination: store.dailyDrop.stencilURL) {
                        Label("Print master", systemImage: "photo.fill")
                    }
                    .buttonStyle(.bordered)
                    if let transferURL = store.dailyDrop.transferURL {
                        Link(destination: transferURL) {
                            Label("Transfer", systemImage: "square.and.arrow.down")
                        }
                        .buttonStyle(.bordered)
                    }
                    if let transferPDFURL = store.dailyDrop.transferPDFURL {
                        Link(destination: transferPDFURL) {
                            Label("PDF", systemImage: "doc.fill")
                        }
                        .buttonStyle(.bordered)
                    }
                }
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
        }
    }
}

private struct HeroStencilCard: View {
    let stencil: StencilDrop
    let isSaved: Bool
    let onToggleSave: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            AsyncImage(url: stencil.previewURL) { phase in
                switch phase {
                case .empty:
                    ProgressView().frame(maxWidth: .infinity, minHeight: 280)
                case .success(let image):
                    image.resizable().scaledToFill()
                case .failure:
                    Image(systemName: "sparkles.rectangle.stack")
                        .font(.system(size: 72))
                        .frame(maxWidth: .infinity, minHeight: 280)
                @unknown default:
                    EmptyView()
                }
            }
            .frame(maxWidth: .infinity, minHeight: 280, maxHeight: 360)
            .clipped()
            .clipShape(RoundedRectangle(cornerRadius: 8))

            VStack(alignment: .leading, spacing: 8) {
                SectionTitle(text: stencil.collection)
                Text(stencil.title)
                    .font(.system(size: 38, weight: .black, design: .rounded))
                    .foregroundStyle(Color.tattooInk)
                Text(stencil.summary)
                    .foregroundStyle(.secondary)
                Label(stencil.placement, systemImage: "ruler.fill")
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(Color.tattooGold)
            }

            Button(action: onToggleSave) {
                Label(isSaved ? "Saved to my stencils" : "Save stencil", systemImage: isSaved ? "bookmark.fill" : "bookmark")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.bordered)
            .controlSize(.large)
        }
        .padding()
        .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}

private struct MetricTile: View {
    let value: String
    let label: String
    let icon: String

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Image(systemName: icon)
                .foregroundStyle(Color.tattooViolet)
            Text(value)
                .font(.title.bold())
                .foregroundStyle(Color.tattooInk)
            Text(label)
                .font(.caption.weight(.semibold))
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}
