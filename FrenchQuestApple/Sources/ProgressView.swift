import SwiftUI

struct LearningProgressView: View {
    @EnvironmentObject private var store: QuestStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()

                QuestCard {
                    VStack(alignment: .leading, spacing: 14) {
                        Text("QUEST HERO")
                            .font(.caption.weight(.black))
                            .foregroundStyle(store.theme.accent)
                        Text(heroTitle)
                            .font(.largeTitle.weight(.black))
                        Text("\(store.completedChallengeIDs.count) of \(store.totalChallenges) launch challenges cleared")
                            .foregroundStyle(.secondary)
                        SwiftUI.ProgressView(value: store.progress)
                            .tint(store.theme.accent)
                    }
                }

                QuestStatBar()
                ThemePicker()

                QuestCard {
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Recovery")
                            .font(.title3.weight(.black))
                        Text("Hearts refill for this local beta build so testers can keep playing.")
                            .foregroundStyle(.secondary)
                        HStack {
                            Button {
                                store.refillHearts()
                            } label: {
                                Label("Refill Hearts", systemImage: "heart.fill")
                                    .frame(maxWidth: .infinity)
                            }
                            .buttonStyle(.borderedProminent)

                            Button(role: .destructive) {
                                store.resetProgress()
                            } label: {
                                Label("Reset", systemImage: "arrow.counterclockwise")
                                    .frame(maxWidth: .infinity)
                            }
                            .buttonStyle(.bordered)
                        }
                    }
                }
            }
            .padding()
        }
        .background(store.theme.background)
        .navigationTitle("Hero")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var heroTitle: String {
        switch store.progress {
        case 0:
            return "New Explorer"
        case 0..<0.34:
            return "Bonjour Scout"
        case 0.34..<0.70:
            return "Cafe Navigator"
        case 0.70..<1:
            return "Metro Adventurer"
        default:
            return "French Quest Champion"
        }
    }
}
