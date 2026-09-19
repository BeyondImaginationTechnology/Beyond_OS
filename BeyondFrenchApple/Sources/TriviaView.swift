import SwiftUI

private struct TriviaQuestion: Identifiable {
    let id: String
    let category: String
    let prompt: String
    let options: [String]
    let answer: String

    static let all: [TriviaQuestion] = [
        .init(id: "math-1", category: "Math", prompt: "What is 8 × 7?", options: ["54", "56", "64", "49"], answer: "56"),
        .init(id: "science-1", category: "Science", prompt: "Which planet is known as the Red Planet?", options: ["Venus", "Mars", "Jupiter", "Mercury"], answer: "Mars"),
        .init(id: "history-1", category: "History", prompt: "Who was the first president of the United States?", options: ["Abraham Lincoln", "George Washington", "Thomas Jefferson", "John Adams"], answer: "George Washington"),
        .init(id: "language-1", category: "Language", prompt: "Which word is a verb?", options: ["Happy", "Run", "Purple", "Table"], answer: "Run"),
        .init(id: "geography-1", category: "Geography", prompt: "What is the largest ocean on Earth?", options: ["Atlantic", "Indian", "Arctic", "Pacific"], answer: "Pacific"),
        .init(id: "math-2", category: "Math", prompt: "What is 3/4 written as a decimal?", options: ["0.25", "0.5", "0.75", "0.8"], answer: "0.75"),
        .init(id: "science-2", category: "Science", prompt: "Plants make food using sunlight in a process called…", options: ["Respiration", "Photosynthesis", "Evaporation", "Digestion"], answer: "Photosynthesis"),
        .init(id: "language-2", category: "Language", prompt: "Which sentence uses correct punctuation?", options: ["Where are you going", "Where are you going?", "where are you going?", "Where are you going!"], answer: "Where are you going?")
    ]
}

struct TriviaView: View {
    @EnvironmentObject private var store: AppStore
    @State private var playerOne = "Player 1"
    @State private var playerTwo = "Player 2"
    @State private var game: TriviaGame?
    @State private var matchType: TriviaMatchType = .cpu

    var body: some View {
        Group {
            if let game {
                TriviaMatchView(game: game) { self.game = nil }
            } else {
                setup
            }
        }
        .navigationTitle("Trivia")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var setup: some View {
        ZStack {
            store.appTheme.appBackground.ignoresSafeArea()
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    BrandHeader()
                    Text("HEAD-TO-HEAD TRIVIA")
                        .font(.caption.weight(.black)).tracking(1.8)
                        .foregroundStyle(store.appTheme.accent)
                    Text("Are you smarter than a 5th grader?")
                        .font(.system(size: 32, weight: .black, design: .rounded))
                        .foregroundStyle(.white)
                    Text(matchType == .cpu ? "Challenge the referee’s CPU opponent on this device." : "Pass the device, take turns, and let the referee settle the score.")
                        .foregroundStyle(.white.opacity(0.72))

                    Picker("Match type", selection: $matchType) {
                        ForEach(TriviaMatchType.allCases) { type in
                            Label(type.title, systemImage: type.symbol).tag(type)
                        }
                    }
                    .pickerStyle(.segmented)

                    VStack(spacing: 12) {
                        TriviaNameField(label: "PLAYER ONE", icon: "1.circle.fill", name: $playerOne)
                        if matchType == .local {
                            TriviaNameField(label: "PLAYER TWO", icon: "2.circle.fill", name: $playerTwo)
                        } else {
                            TriviaNameField(label: "OPPONENT", icon: "cpu", name: .constant("Trivia CPU"))
                        }
                    }

                    VStack(alignment: .leading, spacing: 10) {
                        Label("How it works", systemImage: "flag.checkered")
                            .font(.headline.weight(.black))
                        Text(matchType == .cpu ? "Eight mixed-subject questions · the CPU takes alternate turns · one point per correct answer" : "Eight mixed-subject questions · alternating turns · one point per correct answer")
                            .font(.subheadline)
                            .foregroundStyle(.white.opacity(0.7))
                    }
                    .padding(18)
                    .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))

                    Button {
                        game = TriviaGame(
                            players: [cleanName(playerOne, fallback: "Player 1"), matchType == .cpu ? "Trivia CPU" : cleanName(playerTwo, fallback: "Player 2")],
                            type: matchType
                        )
                    } label: {
                        Label(matchType == .cpu ? "Play CPU" : "Start Local Match", systemImage: "play.fill")
                            .font(.headline.weight(.black))
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 16)
                            .background(store.appTheme.accent, in: Capsule())
                    }
                    .buttonStyle(.plain)
                    .foregroundStyle(.black)
                }
                .padding(20)
            }
        }
    }

    private func cleanName(_ name: String, fallback: String) -> String {
        let value = name.trimmingCharacters(in: .whitespacesAndNewlines)
        return value.isEmpty ? fallback : value
    }
}

private struct TriviaNameField: View {
    let label: String
    let icon: String
    @Binding var name: String

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Label(label, systemImage: icon).font(.caption.weight(.black)).foregroundStyle(.white.opacity(0.65))
            TextField(label, text: $name)
                .textInputAutocapitalization(.words)
                .padding(14)
                .background(.white.opacity(0.1), in: RoundedRectangle(cornerRadius: 14))
                .foregroundStyle(.white)
        }
    }
}

private enum TriviaMatchType: String, CaseIterable, Identifiable {
    case cpu
    case local

    var id: String { rawValue }
    var title: String { self == .cpu ? "Vs CPU" : "Local" }
    var symbol: String { self == .cpu ? "cpu" : "person.2.fill" }
}

private struct TriviaGame {
    let players: [String]
    let type: TriviaMatchType
}

private struct TriviaMatchView: View {
    @EnvironmentObject private var store: AppStore
    let game: TriviaGame
    let onExit: () -> Void
    @State private var questionIndex = 0
    @State private var scores = [0, 0]
    @State private var selectedAnswer: String?
    @State private var showResult = false
    @State private var cpuThinking = false

    private var question: TriviaQuestion { TriviaQuestion.all[questionIndex] }
    private var playerIndex: Int { questionIndex % game.players.count }
    private var isFinished: Bool { questionIndex >= TriviaQuestion.all.count }
    private var isCpuTurn: Bool { game.type == .cpu && playerIndex == 1 }

    var body: some View {
        ZStack {
            store.appTheme.appBackground.ignoresSafeArea()
            if isFinished { results } else { round }
        }
        .foregroundStyle(.white)
        .toolbar {
            ToolbarItem(placement: .topBarTrailing) {
                Button("End Match", role: .destructive, action: onExit)
            }
        }
        .onChange(of: questionIndex) { _, _ in playCpuTurnIfNeeded() }
        .onAppear { playCpuTurnIfNeeded() }
    }

    private var round: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                HStack {
                    Text("QUESTION \(questionIndex + 1) OF \(TriviaQuestion.all.count)")
                        .font(.caption.weight(.black)).tracking(1.4).foregroundStyle(store.appTheme.accent)
                    Spacer()
                    Text(question.category.uppercased()).font(.caption.weight(.black)).padding(.horizontal, 10).padding(.vertical, 7).background(.white.opacity(0.1), in: Capsule())
                }
                HStack(spacing: 10) {
                    ForEach(game.players.indices, id: \.self) { index in
                        VStack(alignment: .leading, spacing: 2) {
                            Text(game.players[index]).font(.caption.weight(.bold)).lineLimit(1)
                            Text("\(scores[index]) pts").font(.title3.weight(.black))
                        }
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .padding(12)
                        .background(index == playerIndex ? store.appTheme.accent.opacity(0.25) : .white.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
                    }
                }
                Text(isCpuTurn ? "REFEREE: Trivia CPU is answering" : "REFEREE: Pass to \(game.players[playerIndex])")
                    .font(.caption.weight(.black)).tracking(1.1).foregroundStyle(.yellow)
                Text(question.prompt).font(.system(size: 29, weight: .black, design: .rounded))
                VStack(spacing: 10) {
                    ForEach(question.options, id: \.self) { option in
                        Button { choose(option) } label: {
                            HStack {
                                Text(option).font(.headline)
                                Spacer()
                                if selectedAnswer == option { Image(systemName: resultIcon(for: option)) }
                            }
                            .padding(16)
                            .background(optionColor(option), in: RoundedRectangle(cornerRadius: 16))
                        }
                        .buttonStyle(.plain)
                        .disabled(showResult || isCpuTurn)
                    }
                }
                if showResult {
                    Button(action: nextQuestion) {
                        Label(questionIndex == TriviaQuestion.all.count - 1 ? "See Winner" : "Next Turn", systemImage: "arrow.right.circle.fill")
                            .font(.headline.weight(.black)).frame(maxWidth: .infinity).padding(.vertical, 15).background(store.appTheme.accent, in: Capsule())
                    }
                    .buttonStyle(.plain).foregroundStyle(.black)
                }
                if cpuThinking {
                    HStack(spacing: 10) {
                        ProgressView().tint(store.appTheme.accent)
                        Text("Trivia CPU is thinking…").font(.subheadline.weight(.bold))
                    }
                    .foregroundStyle(.white.opacity(0.72))
                }
            }
            .padding(20)
        }
    }

    private var results: some View {
        let winner = scores[0] == scores[1] ? "It’s a tie!" : "\(game.players[scores[0] > scores[1] ? 0 : 1]) wins!"
        return VStack(spacing: 20) {
            Image(systemName: "trophy.fill").font(.system(size: 68)).foregroundStyle(.yellow)
            Text("MATCH COMPLETE").font(.caption.weight(.black)).tracking(2).foregroundStyle(store.appTheme.accent)
            Text(winner).font(.system(size: 34, weight: .black, design: .rounded)).multilineTextAlignment(.center)
            HStack { ForEach(game.players.indices, id: \.self) { index in Text("\(game.players[index]): \(scores[index])").font(.headline.weight(.bold)) } }.padding()
            Button("Play Again", action: onExit).buttonStyle(.borderedProminent)
        }
        .padding(24)
    }

    private func choose(_ option: String) {
        selectedAnswer = option
        showResult = true
        if option == question.answer { scores[playerIndex] += 1 }
    }

    private func nextQuestion() { questionIndex += 1; selectedAnswer = nil; showResult = false }
    private func playCpuTurnIfNeeded() {
        guard isCpuTurn, !isFinished else { return }
        cpuThinking = true
        Task { @MainActor in
            try? await Task.sleep(for: .milliseconds(850))
            guard !Task.isCancelled, isCpuTurn, !showResult else { return }
            let isCorrect = Int.random(in: 0..<100) < 62
            choose(isCorrect ? question.answer : question.options.first(where: { $0 != question.answer }) ?? question.answer)
            cpuThinking = false
            try? await Task.sleep(for: .milliseconds(900))
            guard !Task.isCancelled, showResult else { return }
            nextQuestion()
        }
    }
    private func resultIcon(for option: String) -> String { option == question.answer ? "checkmark.circle.fill" : "xmark.circle.fill" }
    private func optionColor(_ option: String) -> Color {
        guard showResult else { return .white.opacity(0.1) }
        if option == question.answer { return .green.opacity(0.35) }
        return selectedAnswer == option ? .red.opacity(0.35) : .white.opacity(0.08)
    }
}
