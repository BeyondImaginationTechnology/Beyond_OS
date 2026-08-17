import SwiftUI

struct PracticeView: View {
    @EnvironmentObject private var store: QuestStore
    @State private var index = 0
    @State private var typedAnswer = ""
    @State private var feedback: QuestResult?
    @FocusState private var answerFocused: Bool

    private var deck: [(region: QuestRegion, challenge: QuestChallenge)] {
        store.regions.flatMap { region in
            region.challenges.map { (region, $0) }
        }
    }

    private var active: (region: QuestRegion, challenge: QuestChallenge) {
        deck[index % max(deck.count, 1)]
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                QuestStatBar()

                QuestCard {
                    VStack(alignment: .leading, spacing: 16) {
                        Text("TRAINING ROOM")
                            .font(.caption.weight(.black))
                            .foregroundStyle(active.region.color)
                        Text("Type the French phrase for:")
                            .foregroundStyle(.secondary)
                        Text(active.challenge.answer)
                            .font(.largeTitle.weight(.black))

                        TextField("French answer", text: $typedAnswer)
                            .focused($answerFocused)
                            .textInputAutocapitalization(.sentences)
                            .submitLabel(.done)
                            .padding(14)
                            .background(Color.white.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
                            .overlay(RoundedRectangle(cornerRadius: 14).stroke(Color.white.opacity(0.12), lineWidth: 1))
                            .onSubmit(check)

                        HStack {
                            Button(action: check) {
                                Label("Check", systemImage: "checkmark.circle.fill")
                                    .frame(maxWidth: .infinity)
                            }
                            .buttonStyle(.borderedProminent)

                            Button {
                                store.speak(active.challenge.phrase)
                            } label: {
                                Image(systemName: "speaker.wave.2.fill")
                                    .frame(maxWidth: .infinity)
                            }
                            .buttonStyle(.bordered)

                            Button(action: next) {
                                Image(systemName: "arrow.forward.circle.fill")
                                    .frame(maxWidth: .infinity)
                            }
                            .buttonStyle(.bordered)
                        }
                        .controlSize(.large)

                        if let feedback {
                            Text(feedback.message)
                                .font(.headline)
                                .foregroundStyle(feedback.correct ? .green : .orange)
                                .padding(12)
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .background((feedback.correct ? Color.green : Color.orange).opacity(0.12), in: RoundedRectangle(cornerRadius: 14))
                                .transition(.move(edge: .top).combined(with: .opacity))
                        }

                        Text(active.challenge.phrase)
                            .font(.headline)
                            .foregroundStyle(active.region.color)
                    }
                }
                .animation(.snappy(duration: 0.24), value: feedback)
            }
            .padding()
        }
        .background(store.theme.background)
        .navigationTitle("Train")
        .navigationBarTitleDisplayMode(.inline)
    }

    private func check() {
        let isCorrect = normalized(typedAnswer) == normalized(active.challenge.phrase)
        answerFocused = false
        withAnimation(.snappy(duration: 0.24)) {
            typedAnswer = isCorrect ? "" : typedAnswer
            feedback = QuestResult(correct: isCorrect, message: isCorrect ? "Clean recall. Moving on." : "Close the loop by listening once.")
        }
        if isCorrect {
            Task {
                try? await Task.sleep(for: .milliseconds(650))
                await MainActor.run { next() }
            }
        }
    }

    private func next() {
        withAnimation(.snappy(duration: 0.28)) {
            index += 1
            typedAnswer = ""
            feedback = nil
        }
    }

    private func normalized(_ value: String) -> String {
        value
            .folding(options: [.caseInsensitive, .diacriticInsensitive], locale: .current)
            .replacingOccurrences(of: "[^a-z0-9]+", with: "", options: .regularExpression)
    }
}
