import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: QuestStore

    private var nextChallenge: (region: QuestRegion, challenge: QuestChallenge)? {
        for region in store.regions where store.isRegionUnlocked(region) {
            if let challenge = region.challenges.first(where: { !store.completedChallengeIDs.contains($0.id) && store.isChallengeUnlocked($0, in: region) }) {
                return (region, challenge)
            }
        }
        return nil
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                QuestStatBar()

                QuestCard {
                    VStack(alignment: .leading, spacing: 14) {
                        HStack {
                            Text("ACTIVE QUEST")
                            Spacer()
                            Label("REALM 1", systemImage: "crown.fill")
                        }
                            .font(.caption.weight(.black))
                            .foregroundStyle(store.theme.accent)
                        Text(nextChallenge?.region.title ?? "All quests cleared")
                            .font(.system(size: 34, weight: .black, design: .rounded))
                        Text(nextChallenge?.region.subtitle ?? "Review phrases in training to keep your streak warm.")
                            .foregroundStyle(.secondary)
                        SwiftUI.ProgressView(value: store.progress)
                            .tint(store.theme.accent)
                    }
                }

                AdventurePortalGrid()

                if let nextChallenge {
                    ChallengePlayer(region: nextChallenge.region, challenge: nextChallenge.challenge)
                } else {
                    QuestCard {
                        VStack(alignment: .leading, spacing: 10) {
                            Label("Quest complete", systemImage: "checkmark.seal.fill")
                                .font(.title3.weight(.black))
                                .foregroundStyle(.green)
                            Text("You cleared every launch realm in French Quest 1.1.1.")
                                .foregroundStyle(.secondary)
                        }
                    }
                }
            }
            .padding()
        }
        .background(store.theme.background)
        .toolbar(.hidden, for: .navigationBar)
    }
}

struct ChallengePlayer: View {
    @EnvironmentObject private var store: QuestStore
    let region: QuestRegion
    let challenge: QuestChallenge
    @State private var selected: String?

    var body: some View {
        QuestCard {
            VStack(alignment: .leading, spacing: 16) {
                HStack {
                    Label(region.title, systemImage: region.icon)
                        .font(.caption.weight(.bold))
                        .foregroundStyle(region.color)
                    Spacer()
                    Button {
                        store.speak(challenge.phrase)
                    } label: {
                        Image(systemName: "speaker.wave.2.fill")
                            .frame(width: 36, height: 36)
                            .background(region.color.opacity(0.20), in: Circle())
                    }
                    .buttonStyle(.plain)
                    .accessibilityLabel("Listen")
                }

                HStack(alignment: .top) {
                    Text(challenge.prompt)
                        .font(.title2.weight(.black))
                    Spacer()
                    Text("+\(region.reward / max(region.lessonCount, 1)) XP")
                        .font(.caption.weight(.black))
                        .foregroundStyle(.yellow)
                        .padding(.horizontal, 9)
                        .padding(.vertical, 6)
                        .background(Color.yellow.opacity(0.14), in: Capsule())
                }

                VStack(alignment: .leading, spacing: 5) {
                    Text(challenge.phrase)
                        .font(.system(size: 32, weight: .black, design: .rounded))
                        .foregroundStyle(region.color)
                    Text(challenge.pronunciation)
                        .font(.headline)
                        .foregroundStyle(.secondary)
                }

                VStack(spacing: 10) {
                    ForEach(Array(challenge.options.enumerated()), id: \.element) { index, option in
                        Button {
                            selected = option
                            withAnimation(.snappy(duration: 0.22)) {
                                store.submit(option, for: challenge, in: region)
                            }
                        } label: {
                            HStack {
                                Text(["A", "B", "C", "D"][min(index, 3)])
                                    .font(.caption.weight(.black))
                                    .frame(width: 30, height: 30)
                                    .background(region.color.opacity(0.18), in: Circle())
                                Text(option).font(.headline)
                                Spacer()
                                if selected == option {
                                    Image(systemName: store.lastResult?.correct == true ? "checkmark.circle.fill" : "xmark.circle.fill")
                                }
                            }
                            .padding(14)
                            .background(optionBackground(option), in: RoundedRectangle(cornerRadius: 14))
                        }
                        .buttonStyle(.plain)
                        .disabled(store.hearts == 0)
                    }
                }

                if let result = store.lastResult {
                    Text(result.message)
                        .font(.headline)
                        .foregroundStyle(result.correct ? .green : .orange)
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .padding(12)
                        .background((result.correct ? Color.green : Color.orange).opacity(0.12), in: RoundedRectangle(cornerRadius: 14))
                        .transition(.move(edge: .top).combined(with: .opacity))
                }

                Text(challenge.tip)
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }
        }
        .animation(.snappy(duration: 0.22), value: store.lastResult)
    }

    private func optionBackground(_ option: String) -> Color {
        guard selected == option, let result = store.lastResult else {
            return Color.white.opacity(0.08)
        }
        return result.correct ? Color.green.opacity(0.20) : Color.orange.opacity(0.18)
    }
}
