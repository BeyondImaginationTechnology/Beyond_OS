import SwiftUI

struct DictionaryView: View {
    @EnvironmentObject private var store: AppStore
    @State private var query = ""

    private var results: [DictionaryWord] {
        guard !query.isEmpty else { return store.dictionary }
        return store.dictionary.filter {
            [$0.english, $0.french, $0.spanish, $0.kreyol, $0.patois]
                .contains { $0.localizedCaseInsensitiveContains(query) }
        }
    }

    var body: some View {
        List(results) { word in
            VStack(alignment: .leading, spacing: 8) {
                HStack { Text(word.english).font(.headline); Spacer(); AccessPill(text: word.type.uppercased()) }
                Button { store.speak(word.french) } label: {
                    HStack { Text("🇫🇷 \(word.french)").font(.title3.bold()); Spacer(); Image(systemName: "speaker.wave.2.fill") }
                }.buttonStyle(.plain).foregroundStyle(.blue)
                Text(word.pronunciation).font(.caption).foregroundStyle(.secondary)
                Text("🇭🇹 \(word.kreyol)   🇯🇲 \(word.patois)   🇪🇸 \(word.spanish)").font(.subheadline)
            }.padding(.vertical, 6)
        }
        .navigationTitle("Free Dictionary")
        .searchable(text: $query, prompt: "Search every language")
        .overlay { if results.isEmpty { ContentUnavailableView.search(text: query) } }
    }
}
