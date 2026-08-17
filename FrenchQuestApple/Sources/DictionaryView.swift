import SwiftUI

struct DictionaryView: View {
    @EnvironmentObject private var store: QuestStore
    @State private var query = ""

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
                ForEach(phrases, id: \.challenge.id) { item in
                    PhraseCard(region: item.region, challenge: item.challenge)
                }
            }
            .padding()
        }
        .background(store.theme.background)
        .navigationTitle("Phrases")
        .navigationBarTitleDisplayMode(.inline)
        .searchable(text: $query, prompt: "Search quests and phrases")
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
