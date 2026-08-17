import SwiftUI

struct DictionaryView: View {
    @EnvironmentObject private var store: QuestStore
    @State private var query = ""
    @State private var mode = DictionaryMode.words
    @State private var selectedType = "all"

    private var types: [String] {
        ["all"] + Array(Set(store.dictionary.map(\.type))).sorted()
    }

    private var words: [DictionaryWord] {
        store.dictionary.filter { word in
            let matchesType = selectedType == "all" || word.type == selectedType
            let matchesQuery = query.isEmpty || [word.english, word.french, word.spanish, word.kreyol, word.patois, word.pronunciation]
                .contains { $0.localizedCaseInsensitiveContains(query) }
            return matchesType && matchesQuery
        }
    }

    private var phrases: [(region: QuestRegion, challenge: QuestChallenge)] {
        store.regions.flatMap { region in
            region.challenges.map { (region, $0) }
        }
        .filter { item in
            query.isEmpty ||
            item.challenge.phrase.localizedCaseInsensitiveContains(query) ||
            item.challenge.answer.localizedCaseInsensitiveContains(query) ||
            item.region.title.localizedCaseInsensitiveContains(query)
        }
    }

    var body: some View {
        ScrollView {
            LazyVStack(alignment: .leading, spacing: 12) {
                BrandHeader()
                    .padding(.bottom, 6)
                Picker("Dictionary Mode", selection: $mode) {
                    ForEach(DictionaryMode.allCases) { mode in
                        Text(mode.title).tag(mode)
                    }
                }
                .pickerStyle(.segmented)

                if mode == .words {
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
                                        .background(selectedType == type ? store.theme.accent : Color.white.opacity(0.10), in: Capsule())
                                        .foregroundStyle(selectedType == type ? .black : .white)
                                }
                                .buttonStyle(.plain)
                            }
                        }
                    }

                    ForEach(words) { word in
                        WordCard(word: word)
                    }
                } else {
                    ForEach(phrases, id: \.challenge.id) { item in
                        PhraseCard(region: item.region, challenge: item.challenge)
                    }
                }
            }
            .padding()
        }
        .background(store.theme.background)
        .navigationTitle(mode.title)
        .navigationBarTitleDisplayMode(.inline)
        .searchable(text: $query, prompt: mode.searchPrompt)
        .overlay {
            if (mode == .words && words.isEmpty) || (mode == .phrases && phrases.isEmpty) {
                ContentUnavailableView.search(text: query)
            }
        }
    }
}

private enum DictionaryMode: String, CaseIterable, Identifiable {
    case words
    case phrases

    var id: String { rawValue }

    var title: String {
        switch self {
        case .words: "Words"
        case .phrases: "Phrases"
        }
    }

    var searchPrompt: String {
        switch self {
        case .words: "Search every language"
        case .phrases: "Search quests and phrases"
        }
    }
}

private struct WordCard: View {
    @EnvironmentObject private var store: QuestStore
    let word: DictionaryWord

    var body: some View {
        QuestCard {
            VStack(alignment: .leading, spacing: 12) {
                HStack(alignment: .top) {
                    VStack(alignment: .leading, spacing: 4) {
                        Text(word.english)
                            .font(.headline)
                        Text(word.type.uppercased())
                            .font(.caption2.weight(.black))
                            .foregroundStyle(.secondary)
                    }
                    Spacer()
                    Button { store.speakDictionaryWord(word, language: .french) } label: {
                        Image(systemName: "speaker.wave.2.fill")
                            .frame(width: 34, height: 34)
                            .background(store.theme.accent.opacity(0.18), in: Circle())
                    }
                    .buttonStyle(.plain)
                }

                Text(word.french)
                    .font(.title2.weight(.black))
                    .foregroundStyle(store.theme.accent)
                Text(word.pronunciation)
                    .font(.headline)
                    .foregroundStyle(.secondary)

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
        }
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
                        .font(.caption2.weight(.black))
                    Image(systemName: "speaker.wave.2.fill")
                        .font(.caption2.weight(.bold))
                }
                .foregroundStyle(color)
                Text(value)
                    .font(.caption.weight(.semibold))
                    .lineLimit(2)
                    .minimumScaleFactor(0.8)
                    .foregroundStyle(.white)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            .padding(9)
            .background(color.opacity(0.17), in: RoundedRectangle(cornerRadius: 12))
            .overlay(RoundedRectangle(cornerRadius: 12).stroke(color.opacity(0.22), lineWidth: 1))
        }
        .buttonStyle(.plain)
    }
}

private struct PhraseCard: View {
    @EnvironmentObject private var store: QuestStore
    let region: QuestRegion
    let challenge: QuestChallenge

    var body: some View {
        QuestCard {
            VStack(alignment: .leading, spacing: 12) {
                HStack {
                    Text(region.title.uppercased())
                        .font(.caption2.weight(.black))
                        .foregroundStyle(region.color)
                    Spacer()
                    Button {
                        store.speak(challenge.phrase)
                    } label: {
                        Image(systemName: "speaker.wave.2.fill")
                            .frame(width: 34, height: 34)
                            .background(region.color.opacity(0.18), in: Circle())
                    }
                    .buttonStyle(.plain)
                }

                Text(challenge.phrase)
                    .font(.title2.weight(.black))
                    .foregroundStyle(region.color)
                Text(challenge.pronunciation)
                    .font(.headline)
                    .foregroundStyle(.secondary)
                Text(challenge.tip)
                    .font(.subheadline)
                    .foregroundStyle(.white.opacity(0.78))
            }
        }
    }
}
