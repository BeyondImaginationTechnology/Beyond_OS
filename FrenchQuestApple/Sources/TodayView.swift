import SwiftUI

struct TodayView: View {
    let onMenu: () -> Void

    var body: some View {
        WorldTourMapView(onMenu: onMenu)
    }
}

struct ChallengePlayer: View {
    @EnvironmentObject private var store: QuestStore
    let region: QuestRegion
    let challenge: QuestChallenge
    @State private var selected: String?

    private var isOutOfHearts: Bool { store.hearts == 0 }

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


                HStack(spacing: 7) {
                    ForEach(0..<QuestStore.maxHearts, id: \.self) { index in
                        Image(systemName: index < store.hearts ? "heart.fill" : "heart")
                            .foregroundStyle(index < store.hearts ? .red : .white.opacity(0.25))
                    }
                    Text("\(store.hearts) attempts left")
                        .font(.caption.weight(.bold))
                        .foregroundStyle(.white.opacity(0.65))
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

                VStack(spacing: 10) {
                    ForEach(Array(challenge.options.enumerated()), id: \.element) { index, option in
                        Button {
                            selected = option
                            let isCorrect = withAnimation(.snappy(duration: 0.22)) {
                                store.submit(option, for: challenge, in: region)
                            }
                            store.speakFeedback(correct: isCorrect)
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
                        .disabled(isOutOfHearts)
                    }
                }

                if isOutOfHearts {
                    VStack(alignment: .leading, spacing: 10) {
                        Text("OUT OF HEARTS")
                            .font(.headline.weight(.black))
                            .foregroundStyle(.red)
                        Text("Restart with three attempts and try this mission again.")
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                        Button {
                            selected = nil
                            store.resetResult()
                            store.refillHearts()
                        } label: {
                            Label("Try Again", systemImage: "arrow.clockwise")
                                .font(.headline.weight(.black))
                                .frame(maxWidth: .infinity)
                                .padding(.vertical, 13)
                                .background(region.color, in: Capsule())
                        }
                        .buttonStyle(.plain)
                    }
                    .padding(14)
                    .background(Color.red.opacity(0.10), in: RoundedRectangle(cornerRadius: 14))
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

            }
        }
        .animation(.snappy(duration: 0.22), value: store.lastResult)
        .onAppear {
            store.resetResult()
        }
    }

    private func optionBackground(_ option: String) -> Color {
        guard selected == option, let result = store.lastResult else {
            return Color.white.opacity(0.08)
        }
        return result.correct ? Color.green.opacity(0.20) : Color.orange.opacity(0.18)
    }
}
