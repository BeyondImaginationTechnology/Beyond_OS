import SwiftUI

struct PracticeView: View {
    @EnvironmentObject private var store: AppStore
    @State private var answer = ""
    @State private var result: PracticeResult?
    @State private var promptMode = PromptMode.daily
    @State private var dictionaryIndex = 0
    @FocusState private var answerFocused: Bool

    private var activePrompt: PracticePrompt {
        switch promptMode {
        case .daily:
            return PracticePrompt(question: store.lesson.challenge, expected: store.lesson.answer, listenText: store.lesson.french, hint: store.lesson.frenchPronunciation)
        case .dictionary:
            let word = store.dictionary.isEmpty ? nil : store.dictionary[dictionaryIndex % store.dictionary.count]
            return PracticePrompt(
                question: "Translate: \(word?.english ?? "hello")",
                expected: word?.french ?? "bonjour",
                listenText: word?.french ?? "bonjour",
                hint: word?.pronunciation ?? "bohn-zhoor"
            )
        }
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                Picker("Practice mode", selection: $promptMode) {
                    ForEach(PromptMode.allCases) { mode in
                        Text(mode.title).tag(mode)
                    }
                }
                .pickerStyle(.segmented)

                VStack(alignment: .leading, spacing: 16) {
                    Text(activePrompt.question)
                        .font(.title2.weight(.black))
                    TextField("Type the French answer", text: $answer)
                        .textInputAutocapitalization(.sentences)
                        .submitLabel(.done)
                        .focused($answerFocused)
                        .padding(14)
                        .background(Color.white.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
                        .overlay(RoundedRectangle(cornerRadius: 14).stroke(Color.white.opacity(0.12), lineWidth: 1))
                        .onSubmit(check)

                    HStack {
                        Button(action: check) {
                            Label("Check", systemImage: "checkmark.circle.fill").frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.borderedProminent)

                        Button { store.speak(activePrompt.listenText) } label: {
                            Image(systemName: "speaker.wave.2.fill").frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.bordered)
                    }
                    .controlSize(.large)

                    if let result {
                        ResultPanel(result: result, expected: activePrompt.expected)
                            .transition(.move(edge: .top).combined(with: .opacity))
                    }
                }
                .animation(.snappy(duration: 0.24), value: result)
                .padding(18)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))
                .overlay(RoundedRectangle(cornerRadius: 22).stroke(store.appTheme.accent.opacity(0.18), lineWidth: 1))

                HStack {
                    Button {
                        answer = ""
                        result = .revealed
                    } label: {
                        Label("Reveal", systemImage: "eye.fill")
                    }
                    .buttonStyle(.bordered)

                    Button {
                        dictionaryIndex += 1
                        promptMode = .dictionary
                        answer = ""
                        result = nil
                    } label: {
                        Label("Next Word", systemImage: "arrow.forward.circle.fill")
                    }
                    .buttonStyle(.bordered)
                }

                LessonInfoBlock(title: "Hint", text: activePrompt.hint, systemImage: "textformat.abc", color: .teal)
            }
            .padding()
        }
        .background(store.appTheme.appBackground)
        .navigationTitle("Practice")
    }

    private func check() {
        if store.checkAnswer(answer, expected: activePrompt.expected) {
            answerFocused = false
            withAnimation(.snappy(duration: 0.24)) {
                answer = ""
                result = .correct
            }
            store.recordCorrectPractice()
            advanceDictionaryPromptIfNeeded()
        } else {
            withAnimation(.snappy(duration: 0.24)) {
                result = .incorrect
            }
        }
    }

    private func advanceDictionaryPromptIfNeeded() {
        guard promptMode == .dictionary else { return }
        Task {
            try? await Task.sleep(for: .milliseconds(700))
            await MainActor.run {
                guard result == .correct, promptMode == .dictionary else { return }
                withAnimation(.snappy(duration: 0.28)) {
                    dictionaryIndex += 1
                    result = nil
                }
            }
        }
    }
}

private enum PromptMode: String, CaseIterable, Identifiable {
    case daily
    case dictionary

    var id: String { rawValue }

    var title: String {
        switch self {
        case .daily: "Daily"
        case .dictionary: "Dictionary"
        }
    }
}

private struct PracticePrompt {
    let question: String
    let expected: String
    let listenText: String
    let hint: String
}

private enum PracticeResult: Equatable {
    case correct
    case incorrect
    case revealed
}

private struct ResultPanel: View {
    let result: PracticeResult
    let expected: String

    var body: some View {
        let message: String = switch result {
        case .correct: "Correct. Tres bien!"
        case .incorrect: "Try again, then listen once more."
        case .revealed: "Answer: \(expected)"
        }

        let color: Color = switch result {
        case .correct: .green
        case .incorrect: .orange
        case .revealed: .indigo
        }

        Text(message)
            .font(.headline)
            .foregroundStyle(color)
            .frame(maxWidth: .infinity, alignment: .leading)
            .padding(14)
            .background(color.opacity(0.10), in: RoundedRectangle(cornerRadius: 14))
    }
}
