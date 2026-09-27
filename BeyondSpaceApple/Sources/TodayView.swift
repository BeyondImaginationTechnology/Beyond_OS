import SwiftUI

struct TodayView: View {
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @AppStorage("savedFactIDs") private var savedFactIDs = ""
    @State private var fact = SampleContent.facts[0]
    @State private var academyURL = URL(string: "https://beyondimagination.co.technology/beyond-space/academy.php")!
    @State private var spaceTVURL = URL(string: "https://beyondimagination.co.technology/beyond-tv/channel.php?slug=space-tv")!
    @State private var youtubeURL = URL(string: "https://www.youtube.com/playlist?list=PLXBcsPKqNstB10447aKbDnkPEJdTV9sj-")!
    @State private var factDate = ""

    private var isSaved: Bool { savedIDs.contains(fact.id) }
    private var savedIDs: Set<Int> {
        Set(savedFactIDs.split(separator: ",").compactMap { Int($0) })
    }

    var body: some View {
        ZStack {
            SpaceBackground()
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    Text("Daily Space")
                        .font(.largeTitle.bold())
                        .accessibilityAddTraits(.isHeader)
                    Text("One verified wonder, every day.")
                        .font(.headline)
                        .foregroundStyle(SpaceTheme.secondaryText)

                    SpaceCard {
                        VStack(alignment: .leading, spacing: 18) {
                            Label("DAILY FACT · \(fact.id)", systemImage: fact.symbol)
                                .font(.caption.bold())
                                .foregroundStyle(SpaceTheme.cyan)
                            Text(fact.title)
                                .font(.system(.title, design: .rounded, weight: .bold))
                                .accessibilityAddTraits(.isHeader)
                            Text(fact.summary)
                                .font(.title3.weight(.semibold))
                            Divider().overlay(Color.white.opacity(0.28))
                            Text(fact.detail)
                                .font(.body)
                                .foregroundStyle(SpaceTheme.secondaryText)
                            Link(destination: fact.sourceURL) {
                                Label("Source: \(fact.sourceName)", systemImage: "arrow.up.right.square")
                                    .frame(minHeight: 44)
                            }
                            .accessibilityHint("Opens the source in your browser")
                        }
                    }
                    .accessibilityElement(children: .contain)

                    if !factDate.isEmpty {
                        Text("Daily Space · \(factDate)")
                            .font(.footnote)
                            .foregroundStyle(SpaceTheme.secondaryText)
                    }

                    HStack(spacing: 12) {
                        Button { toggleSaved() } label: {
                            Label(isSaved ? "Saved" : "Save", systemImage: isSaved ? "bookmark.fill" : "bookmark")
                                .frame(maxWidth: .infinity, minHeight: 48)
                        }
                        .buttonStyle(.borderedProminent)
                        Link(destination: academyURL) {
                            Label("Continue learning", systemImage: "book.closed.fill")
                                .frame(maxWidth: .infinity, minHeight: 48)
                        }
                        .buttonStyle(.bordered)
                    }
                    HStack(spacing: 12) {
                        Link(destination: spaceTVURL) {
                            Label("Space TV", systemImage: "tv")
                                .frame(maxWidth: .infinity, minHeight: 44)
                        }
                        .buttonStyle(.bordered)
                        Link(destination: youtubeURL) {
                            Label("YouTube", systemImage: "play.rectangle")
                                .frame(maxWidth: .infinity, minHeight: 44)
                        }
                        .buttonStyle(.bordered)
                    }
                }
                .padding()
            }
            .refreshable { await loadDailyFact() }
        }
        .navigationTitle("Beyond Space")
        .navigationBarTitleDisplayMode(.inline)
        .task { await loadDailyFact() }
    }

    private func loadDailyFact() async {
        guard let result = try? await DailySpaceService.today(),
              let sourceURL = result.fact.sourceURL,
              let remoteAcademyURL = result.fact.academyURL else { return }
        let remote = result.fact
        let updatedFact = SpaceFact(
            id: remote.number,
            title: remote.title,
            summary: remote.fact,
            detail: remote.lesson,
            sourceName: remote.sourceName ?? "NASA Science",
            sourceURL: sourceURL,
            symbol: "sparkles"
        )
        if reduceMotion {
            fact = updatedFact
        } else {
            withAnimation(.easeInOut(duration: 0.25)) { fact = updatedFact }
        }
        academyURL = remoteAcademyURL
        spaceTVURL = result.fact.distribution?.spaceTVURL ?? spaceTVURL
        youtubeURL = result.fact.distribution?.youtubeURL ?? youtubeURL
        factDate = result.date
    }
    private func toggleSaved() {
        var ids = savedIDs
        if ids.contains(fact.id) { ids.remove(fact.id) } else { ids.insert(fact.id) }
        savedFactIDs = ids.sorted().map(String.init).joined(separator: ",")
    }
}
