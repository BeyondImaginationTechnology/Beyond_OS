import SwiftUI

struct AcademyView: View {
    @EnvironmentObject private var store: QuestStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                Text("Quest Map")
                    .font(.largeTitle.weight(.black))
                Text("Clear each region to unlock the next route.")
                    .foregroundStyle(.secondary)

                ForEach(store.regions) { region in
                    RegionCard(region: region)
                }
            }
            .padding()
        }
        .background(store.theme.background)
        .navigationTitle("Map")
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct RegionCard: View {
    @EnvironmentObject private var store: QuestStore
    let region: QuestRegion

    private var unlocked: Bool { store.isRegionUnlocked(region) }
    private var completed: Int { store.completedCount(in: region) }

    var body: some View {
        QuestCard {
            VStack(alignment: .leading, spacing: 14) {
                HStack(spacing: 14) {
                    Image(systemName: unlocked ? region.icon : "lock.fill")
                        .font(.title2.weight(.black))
                        .foregroundStyle(region.color)
                        .frame(width: 48, height: 48)
                        .background(region.color.opacity(0.16), in: RoundedRectangle(cornerRadius: 14))
                    VStack(alignment: .leading, spacing: 3) {
                        Text(region.title).font(.title3.weight(.black))
                        Text(region.subtitle).font(.subheadline).foregroundStyle(.secondary)
                    }
                    Spacer()
                    Text("\(completed)/\(region.lessonCount)")
                        .font(.headline.weight(.black))
                        .foregroundStyle(unlocked ? region.color : .secondary)
                }

                SwiftUI.ProgressView(value: Double(completed), total: Double(max(region.lessonCount, 1)))
                    .tint(region.color)

                VStack(spacing: 8) {
                    ForEach(region.challenges) { challenge in
                        HStack(spacing: 10) {
                            Image(systemName: icon(for: challenge))
                                .foregroundStyle(store.completedChallengeIDs.contains(challenge.id) ? .green : region.color)
                                .frame(width: 28)
                            VStack(alignment: .leading, spacing: 2) {
                                Text(challenge.prompt).font(.subheadline.weight(.bold))
                                Text(challenge.phrase).font(.caption).foregroundStyle(.secondary)
                            }
                            Spacer()
                        }
                        .opacity(store.isChallengeUnlocked(challenge, in: region) ? 1 : 0.45)
                    }
                }
            }
            .opacity(unlocked ? 1 : 0.55)
        }
    }

    private func icon(for challenge: QuestChallenge) -> String {
        if store.completedChallengeIDs.contains(challenge.id) { return "checkmark.circle.fill" }
        switch challenge.kind {
        case .translate:
            return "text.bubble.fill"
        case .listen:
            return "speaker.wave.2.fill"
        case .culture:
            return "globe.europe.africa.fill"
        }
    }
}
