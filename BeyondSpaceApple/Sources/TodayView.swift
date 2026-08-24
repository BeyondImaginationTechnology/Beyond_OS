import SwiftUI

struct TodayView: View {
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @AppStorage("savedFactIDs") private var savedFactIDs = ""
    @State private var selectedIndex = 0

    private var fact: SpaceFact { SampleContent.facts[selectedIndex] }
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
                            Label("FACT \(fact.id) OF 55", systemImage: fact.symbol)
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

                    HStack(spacing: 12) {
                        Button { previousFact() } label: {
                            Label("Previous", systemImage: "chevron.left")
                                .frame(maxWidth: .infinity, minHeight: 44)
                        }
                        .buttonStyle(.bordered)
                        Button { toggleSaved() } label: {
                            Label(isSaved ? "Saved" : "Save", systemImage: isSaved ? "bookmark.fill" : "bookmark")
                                .frame(maxWidth: .infinity, minHeight: 44)
                        }
                        .buttonStyle(.borderedProminent)
                        Button { nextFact() } label: {
                            Label("Next", systemImage: "chevron.right")
                                .labelStyle(.iconOnly)
                                .frame(minWidth: 44, minHeight: 44)
                        }
                        .buttonStyle(.bordered)
                        .accessibilityLabel("Next fact")
                    }
                }
                .padding()
            }
        }
        .navigationTitle("Beyond Space")
        .navigationBarTitleDisplayMode(.inline)
    }

    private func previousFact() { changeIndex((selectedIndex - 1 + SampleContent.facts.count) % SampleContent.facts.count) }
    private func nextFact() { changeIndex((selectedIndex + 1) % SampleContent.facts.count) }
    private func changeIndex(_ index: Int) {
        if reduceMotion { selectedIndex = index } else { withAnimation(.easeInOut(duration: 0.25)) { selectedIndex = index } }
    }
    private func toggleSaved() {
        var ids = savedIDs
        if ids.contains(fact.id) { ids.remove(fact.id) } else { ids.insert(fact.id) }
        savedFactIDs = ids.sorted().map(String.init).joined(separator: ",")
    }
}
