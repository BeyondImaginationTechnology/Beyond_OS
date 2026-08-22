import SwiftUI

struct LibraryView: View {
    @EnvironmentObject private var store: TattooStore
    @State private var selectedCollectionID = "divine-realism"
    @State private var searchText = ""

    private var selectedCollection: TattooCollection {
        store.collections.first { $0.id == selectedCollectionID } ?? store.collections[0]
    }

    private var filteredStencils: [ScheduledStencil] {
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !query.isEmpty else { return selectedCollection.stencils }
        return selectedCollection.stencils.filter {
            $0.name.localizedCaseInsensitiveContains(query)
                || $0.style.localizedCaseInsensitiveContains(query)
                || $0.placement.localizedCaseInsensitiveContains(query)
        }
    }

    var body: some View {
        TattooScreen(title: "Library") {
            HStack(spacing: 12) {
                MetricPill(value: "\(store.totalStencilCount)", label: "Total")
                MetricPill(value: "\(store.availableStencilCount)", label: "Unlocked")
                MetricPill(value: "\(store.collections.count)", label: "Series")
            }

            Picker("Collection", selection: $selectedCollectionID) {
                ForEach(store.collections) { collection in
                    Text(collection.name).tag(collection.id)
                }
            }
            .pickerStyle(.segmented)

            HStack {
                Image(systemName: "magnifyingglass")
                    .foregroundStyle(.secondary)
                TextField("Search stencils, styles, placement", text: $searchText)
                    .textInputAutocapitalization(.never)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            VStack(alignment: .leading, spacing: 12) {
                SectionTitle(text: selectedCollection.dates)
                Text(selectedCollection.name)
                    .font(.largeTitle.weight(.black))
                    .foregroundStyle(Color.tattooInk)
                Text(selectedCollection.description)
                    .foregroundStyle(.secondary)
                HStack(spacing: 12) {
                    Label("\(selectedCollection.dropCount) drops", systemImage: "calendar.badge.clock")
                    Label("\(selectedCollection.availableCount) live", systemImage: "lock.open.fill")
                }
                .font(.caption.weight(.semibold))
                .foregroundStyle(Color.tattooGold)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            LazyVGrid(columns: [GridItem(.adaptive(minimum: 230), spacing: 12)], spacing: 12) {
                ForEach(filteredStencils) { stencil in
                    ScheduleCard(stencil: stencil)
                }
            }
        }
    }
}

private struct MetricPill: View {
    let value: String
    let label: String

    var body: some View {
        VStack(spacing: 3) {
            Text(value)
                .font(.title3.weight(.black))
                .foregroundStyle(Color.tattooInk)
            Text(label)
                .font(.caption.weight(.semibold))
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 12)
        .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}

private struct ScheduleCard: View {
    let stencil: ScheduledStencil

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            AsyncImage(url: stencil.previewURL) { phase in
                if case .success(let image) = phase {
                    image.resizable().scaledToFill()
                } else {
                    ZStack {
                        Color.tattooBackground
                        Image(systemName: "photo.artframe").font(.largeTitle).foregroundStyle(Color.tattooGold)
                    }
                }
            }
            .frame(height: 210)
            .clipped()
            .clipShape(RoundedRectangle(cornerRadius: 7))

            HStack(alignment: .top) {
                VStack(alignment: .leading, spacing: 4) {
                    Text(stencil.isoDate)
                        .font(.caption.weight(.black))
                        .foregroundStyle(Color.tattooGold)
                    Text(stencil.name)
                        .font(.headline.weight(.bold))
                        .foregroundStyle(Color.tattooInk)
                }
                Spacer()
                Image(systemName: stencil.isAvailable ? "checkmark.circle.fill" : "clock.fill")
                    .foregroundStyle(stencil.isAvailable ? .green : Color.tattooGold)
            }

            Text(stencil.style)
                .font(.subheadline.weight(.semibold))
                .foregroundStyle(.secondary)

            Label(stencil.placement, systemImage: "ruler.fill")
                .font(.caption)
                .foregroundStyle(.secondary)

            HStack(spacing: 8) {
                Tag(text: stencil.difficulty)
                if stencil.hasTransferAsset {
                    Tag(text: "Transfer")
                }
                if stencil.hasEditableAsset {
                    Tag(text: "Editable")
                }
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}

private struct Tag: View {
    let text: String

    var body: some View {
        Text(text)
            .font(.caption2.weight(.black))
            .lineLimit(1)
            .minimumScaleFactor(0.8)
            .padding(.horizontal, 8)
            .padding(.vertical, 5)
            .background(Color.tattooViolet.opacity(0.16), in: Capsule())
            .foregroundStyle(Color.tattooInk)
    }
}
