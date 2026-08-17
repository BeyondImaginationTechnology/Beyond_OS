import SwiftUI

struct DictionaryView: View {
    @EnvironmentObject private var store: AppStore
    @State private var query = ""
    @State private var selectedType = "all"

    private var types: [String] {
        ["all"] + Array(Set(store.dictionary.map(\.type))).sorted()
    }

    private var results: [DictionaryWord] {
        store.dictionary.filter { word in
            let matchesType = selectedType == "all" || word.type == selectedType
            let matchesQuery = query.isEmpty || [word.english, word.french, word.spanish, word.kreyol, word.patois, word.pronunciation]
                .contains { $0.localizedCaseInsensitiveContains(query) }
            return matchesType && matchesQuery
        }
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 14) {
                ScrollView(.horizontal, showsIndicators: false) {
                    HStack(spacing: 8) {
                        ForEach(types, id: \.self) { type in
                            Button {
                                selectedType = type
                            } label: {
                                Text(type == "all" ? "All" : type.capitalized)
                                    .font(.caption.weight(.bold))
                                    .padding(.horizontal, 12)
                                    .padding(.vertical, 8)
                                    .background(selectedType == type ? store.appTheme.accent : Color.secondary.opacity(0.12), in: Capsule())
                                    .foregroundStyle(selectedType == type ? .white : .primary)
                            }
                            .buttonStyle(.plain)
                        }
                    }
                    .padding(.horizontal)
                }

                LazyVStack(spacing: 12) {
                    ForEach(results) { word in
                        DictionaryCard(word: word)
                    }
                }
                .padding(.horizontal)
            }
            .padding(.vertical)
        }
        .background(store.appTheme.appBackground)
        .navigationTitle("Dictionary")
        .searchable(text: $query, prompt: "Search every language")
        .overlay {
            if results.isEmpty {
                ContentUnavailableView.search(text: query)
            }
        }
    }
}

private struct DictionaryCard: View {
    @EnvironmentObject private var store: AppStore
    let word: DictionaryWord

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack(alignment: .top) {
                VStack(alignment: .leading, spacing: 4) {
                    Text(word.english)
                        .font(.headline)
                    Text(word.type.uppercased())
                        .font(.caption2.weight(.bold))
                        .foregroundStyle(.secondary)
                }
                Spacer()
                Button { store.speakDictionaryWord(word, language: .french) } label: {
                    Image(systemName: "speaker.wave.2.fill")
                        .frame(width: 36, height: 36)
                        .background(store.appTheme.accent.opacity(0.12), in: Circle())
                }
                .buttonStyle(.plain)
            }

            VStack(alignment: .leading, spacing: 4) {
                Text(word.french)
                    .font(.title2.weight(.black))
                    .foregroundStyle(store.appTheme.accent)
                Text(word.pronunciation)
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }

            LazyVGrid(columns: [.init(.flexible()), .init(.flexible()), .init(.flexible())], spacing: 8) {
                TranslationPill(label: "Kreyol", value: word.kreyol, color: .red) {
                    store.speakDictionaryWord(word, language: .kreyol)
                }
                TranslationPill(label: "Patois", value: word.patois, color: .green) {
                    store.speakDictionaryWord(word, language: .patois)
                }
                TranslationPill(label: "Spanish", value: word.spanish, color: .orange) {
                    store.speakDictionaryWord(word, language: .spanish)
                }
            }
        }
        .padding(16)
        .foregroundStyle(.white)
        .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
        .overlay(RoundedRectangle(cornerRadius: 18).stroke(store.appTheme.accent.opacity(0.16), lineWidth: 1))
    }
}

private struct TranslationPill: View {
    let label: String
    let value: String
    let color: Color
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            VStack(alignment: .leading, spacing: 3) {
                HStack(spacing: 4) {
                    Text(label.uppercased())
                        .font(.caption2.weight(.bold))
                    Image(systemName: "speaker.wave.2.fill")
                        .font(.caption2.weight(.bold))
                }
                .foregroundStyle(color)
                Text(value)
                    .font(.caption.weight(.semibold))
                    .lineLimit(2)
                    .minimumScaleFactor(0.8)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            .padding(9)
            .background(color.opacity(0.17), in: RoundedRectangle(cornerRadius: 12))
            .overlay(RoundedRectangle(cornerRadius: 12).stroke(color.opacity(0.22), lineWidth: 1))
        }
        .buttonStyle(.plain)
    }
}
