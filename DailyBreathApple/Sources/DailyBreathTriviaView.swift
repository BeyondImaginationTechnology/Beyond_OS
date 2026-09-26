import SwiftUI

private struct DailyBreathTriviaQuestion: Identifiable {
    let id: String
    let prompt: String
    let options: [String]
    let answer: String

    static let questions: [DailyBreathTriviaQuestion] = [
        .init(id: "rest", prompt: "Which practice can help create a moment of calm before a difficult day?", options: ["Slow breathing", "Skipping every meal", "Rushing through every task", "Avoiding sleep"], answer: "Slow breathing"),
        .init(id: "reflection", prompt: "What is a helpful first step in a reflection practice?", options: ["Notice what you are feeling", "Judge yourself immediately", "Ignore your experience", "Compare your day to someone else’s"], answer: "Notice what you are feeling"),
        .init(id: "scripture", prompt: "A sacred text can be read thoughtfully by…", options: ["Pausing to consider its meaning", "Treating it as a race", "Reading only one word", "Never asking questions"], answer: "Pausing to consider its meaning"),
        .init(id: "community", prompt: "When you need support, a healthy next step can be to…", options: ["Reach out to someone you trust", "Carry every burden alone", "Hide from everyone", "Give up on your routine"], answer: "Reach out to someone you trust"),
        .init(id: "gratitude", prompt: "Gratitude journaling is meant to help you…", options: ["Notice what is good and sustaining", "Pretend hard things do not exist", "Make a perfect list", "Compete with friends"], answer: "Notice what is good and sustaining")
    ]
}

struct DailyBreathTriviaView: View {
    @AppStorage("dailyBreathTheme") private var themeID = DailyBreathTheme.seasonal.id
    @State private var index = 0
    @State private var selectedAnswer: String?
    @State private var score = 0
    @State private var complete = false

    private var theme: DailyBreathTheme { DailyBreathTheme(id: themeID) }
    private var question: DailyBreathTriviaQuestion { DailyBreathTriviaQuestion.questions[index] }

    var body: some View {
        ZStack {
            DailyBreathThemeBackground(theme: theme)
            if complete { completion } else { quiz }
        }
        .foregroundStyle(theme.primary)
        .navigationTitle("Trivia")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var quiz: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                Text("DAILYBREATH TRIVIA")
                    .font(.caption.weight(.black)).tracking(1.8).foregroundStyle(theme.accent)
                HStack { Text("Question \(index + 1) of \(DailyBreathTriviaQuestion.questions.count)").font(.headline); Spacer(); Text("\(score) correct").font(.headline).foregroundStyle(theme.accent) }
                ProgressView(value: Double(index + 1), total: Double(DailyBreathTriviaQuestion.questions.count)).tint(theme.accent)
                Text(question.prompt).font(.system(size: 30, weight: .bold, design: .rounded))
                Text("Take a breath, then choose the most caring answer.").foregroundStyle(.secondary)
                VStack(spacing: 12) {
                    ForEach(question.options, id: \.self) { option in
                        Button { selectedAnswer = option } label: {
                            HStack { Text(option).multilineTextAlignment(.leading); Spacer(); if selectedAnswer == option { Image(systemName: "checkmark.circle.fill") } }
                                .font(.headline).padding(16).frame(maxWidth: .infinity, alignment: .leading)
                                .background(selectedAnswer == option ? theme.accent.opacity(0.2) : theme.primary.opacity(0.07), in: RoundedRectangle(cornerRadius: 16))
                        }
                        .buttonStyle(.plain).disabled(selectedAnswer != nil)
                    }
                }
                if let selectedAnswer {
                    let correct = selectedAnswer == question.answer
                    Text(correct ? "That’s right. Carry that practice into your day." : "A gentle reminder: \(question.answer).")
                        .font(.headline).foregroundStyle(correct ? .green : .orange)
                        .padding(14).frame(maxWidth: .infinity, alignment: .leading)
                        .background((correct ? Color.green : Color.orange).opacity(0.12), in: RoundedRectangle(cornerRadius: 14))
                    Button(index == DailyBreathTriviaQuestion.questions.count - 1 ? "See your reflection" : "Next question", action: advance)
                        .buttonStyle(.borderedProminent).tint(theme.accent).frame(maxWidth: .infinity)
                }
            }
            .padding(22)
        }
    }

    private var completion: some View {
        VStack(spacing: 18) {
            Image(systemName: "heart.text.square.fill").font(.system(size: 62)).foregroundStyle(theme.accent)
            Text("A moment well spent").font(.system(size: 32, weight: .bold, design: .rounded)).multilineTextAlignment(.center)
            Text("You answered \(score) of \(DailyBreathTriviaQuestion.questions.count) with care. Keep one helpful idea with you today.").multilineTextAlignment(.center).foregroundStyle(.secondary)
            Button("Try again", action: reset).buttonStyle(.borderedProminent).tint(theme.accent)
        }
        .padding(30)
    }

    private func advance() { if selectedAnswer == question.answer { score += 1 }; if index == DailyBreathTriviaQuestion.questions.count - 1 { complete = true } else { index += 1; selectedAnswer = nil } }
    private func reset() { index = 0; selectedAnswer = nil; score = 0; complete = false }
}
