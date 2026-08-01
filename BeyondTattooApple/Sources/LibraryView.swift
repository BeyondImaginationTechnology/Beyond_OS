import SwiftUI

struct LibraryView: View {
    @EnvironmentObject private var store: TattooStore
    @State private var selectedCollectionID = "beyond-ancient"

    private var selectedCollection: TattooCollection {
        store.collections.first { $0.id == selectedCollectionID } ?? store.collections[0]
    }

    var body: some View {
        TattooScreen(title: "Library") {
            Picker("Collection", selection: $selectedCollectionID) {
                ForEach(store.collections) { collection in
                    Text(collection.name).tag(collection.id)
                }
            }
            .pickerStyle(.segmented)

            VStack(alignment: .leading, spacing: 12) {
                SectionTitle(text: selectedCollection.dates)
                Text(selectedCollection.name)
                    .font(.largeTitle.weight(.black))
                    .foregroundStyle(Color.tattooInk)
                Text(selectedCollection.description)
                    .foregroundStyle(.secondary)
                Label("\(selectedCollection.dropCount) stencil drops", systemImage: "calendar.badge.clock")
                    .foregroundStyle(Color.tattooGold)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            LazyVGrid(columns: [GridItem(.adaptive(minimum: 230), spacing: 12)], spacing: 12) {
                ForEach(selectedCollection.stencils) { stencil in
                    ScheduleCard(stencil: stencil)
                }
            }
        }
    }
}

private struct ScheduleCard: View {
    let stencil: ScheduledStencil

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text(stencil.isoDate)
                .font(.caption.weight(.black))
                .foregroundStyle(Color.tattooGold)
            Text(stencil.name)
                .font(.headline.weight(.bold))
                .foregroundStyle(Color.tattooInk)
            Label("Unlocks in library", systemImage: "lock.open.fill")
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}
