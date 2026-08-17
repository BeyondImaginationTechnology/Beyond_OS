import AVFoundation
import SwiftUI

struct LessonView: View {
    let lesson: MathLesson
    @State private var speaking = false
    private let speaker = AVSpeechSynthesizer()

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                Text("LESSON \(lesson.id) · \(lesson.focus.uppercased())").font(.caption.bold()).foregroundStyle(Color.mathGreen)
                Text(lesson.title).font(.largeTitle.bold())
                Button(speaking ? "Stop narration" : "Listen to lesson", systemImage: speaking ? "stop.fill" : "speaker.wave.2.fill") {
                    if speaking { speaker.stopSpeaking(at: .immediate); speaking = false }
                    else {
                        let utterance = AVSpeechUtterance(string: "\(lesson.title). \(lesson.teaching). Worked example. \(lesson.example)")
                        utterance.voice = AVSpeechSynthesisVoice(language: "en-US")
                        utterance.rate = 0.48
                        speaker.speak(utterance)
                        speaking = true
                    }
                }.buttonStyle(.borderedProminent)
                LessonCard(title: "Discover", copy: lesson.teaching, symbol: "lightbulb.fill", color: .mathBlue)
                LessonCard(title: "Worked example", copy: lesson.example, symbol: "function", color: .mathGold)
                NavigationLink { LessonGameView(lesson: lesson) } label: {
                    Label("Start Practice", systemImage: "bolt.fill").frame(maxWidth: .infinity)
                }.buttonStyle(.borderedProminent).tint(.mathGreen)
            }.padding()
        }.background(Color.mathNavy.ignoresSafeArea())
    }
}

private struct LessonCard: View {
    let title: String
    let copy: String
    let symbol: String
    let color: Color
    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            Label(title.uppercased(), systemImage: symbol).font(.caption.bold()).foregroundStyle(color)
            Text(copy).font(.title3).lineSpacing(5)
        }.frame(maxWidth: .infinity, alignment: .leading).padding(22).background(Color.mathPanel, in: RoundedRectangle(cornerRadius: 20))
    }
}

struct LessonGameView: View {
    let lesson: MathLesson
    @EnvironmentObject private var progress: LearningProgress
    @State private var feedback = "Choose your answer."
    @State private var selectedChoice: String?

    var body: some View {
        VStack(spacing: 20) {
            Spacer(minLength: 12)
            Image(systemName: "gamecontroller.fill").font(.system(size: 52)).foregroundStyle(Color.mathGreen)
            Text(lesson.game.question)
                .font(.title.bold())
                .multilineTextAlignment(.center)
                .padding(.bottom, 6)
            ForEach(lesson.game.choices, id: \.self) { choice in
                Button {
                    selectedChoice = choice
                    if choice == lesson.game.answer {
                        feedback = "Great reasoning. Lesson complete. +25 Bit$"
                        progress.complete(lesson.id)
                    } else { feedback = "Keep going. Try another strategy." }
                } label: {
                    HStack {
                        Text(choice)
                            .font(.headline)
                        Spacer()
                        if selectedChoice == choice {
                            Image(systemName: choice == lesson.game.answer ? "checkmark.circle.fill" : "xmark.circle.fill")
                        }
                    }
                    .padding()
                    .frame(maxWidth: .infinity)
                    .background(answerBackground(choice), in: RoundedRectangle(cornerRadius: 16))
                }
                .buttonStyle(.plain)
            }
            Text(feedback).font(.headline).foregroundStyle(feedback.hasPrefix("Great") ? Color.mathGreen : .secondary)
            Spacer()
        }
        .padding()
        .background(Color.mathNavy.ignoresSafeArea())
        .navigationTitle("Practice")
    }

    private func answerBackground(_ choice: String) -> Color {
        guard selectedChoice == choice else { return .mathPanel }
        return choice == lesson.game.answer ? .mathGreen.opacity(0.18) : .red.opacity(0.18)
    }
}
