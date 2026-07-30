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
                Button(speaking ? "Stop narration" : "Listen to free lesson", systemImage: speaking ? "stop.fill" : "speaker.wave.2.fill") {
                    if speaking { speaker.stopSpeaking(at: .immediate); speaking = false }
                    else {
                        let utterance = AVSpeechUtterance(string: "\(lesson.title). \(lesson.teaching). Worked example. \(lesson.example)")
                        utterance.voice = AVSpeechSynthesisVoice(language: "en-US")
                        utterance.rate = 0.48
                        speaker.speak(utterance)
                        speaking = true
                    }
                }.buttonStyle(.borderedProminent)
                LessonCard(title: "Discover", copy: lesson.teaching, color: .mathBlue)
                LessonCard(title: "Worked example", copy: lesson.example, color: .mathGold)
                NavigationLink { LessonGameView(lesson: lesson) } label: {
                    Label("Play this lesson’s game", systemImage: "gamecontroller.fill").frame(maxWidth: .infinity)
                }.buttonStyle(.borderedProminent).tint(.mathGreen)
            }.padding()
        }.background(Color.mathNavy.ignoresSafeArea())
    }
}

private struct LessonCard: View {
    let title: String
    let copy: String
    let color: Color
    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text(title.uppercased()).font(.caption.bold()).foregroundStyle(color)
            Text(copy).font(.title3).lineSpacing(5)
        }.frame(maxWidth: .infinity, alignment: .leading).padding(22).background(Color.mathPanel, in: RoundedRectangle(cornerRadius: 22))
    }
}

struct LessonGameView: View {
    let lesson: MathLesson
    @EnvironmentObject private var progress: LearningProgress
    @State private var feedback = "Choose your answer."

    var body: some View {
        VStack(spacing: 22) {
            Image(systemName: "gamecontroller.fill").font(.system(size: 48)).foregroundStyle(Color.mathGreen)
            Text(lesson.game.question).font(.title.bold()).multilineTextAlignment(.center)
            ForEach(lesson.game.choices, id: \.self) { choice in
                Button(choice) {
                    if choice == lesson.game.answer {
                        feedback = "⭐ Great reasoning! Lesson complete."
                        progress.complete(lesson.id)
                    } else { feedback = "Keep going—try another strategy." }
                }.buttonStyle(.bordered).controlSize(.large)
            }
            Text(feedback).font(.headline).foregroundStyle(feedback.hasPrefix("⭐") ? Color.mathGreen : .secondary)
        }.padding().navigationTitle("Game \(lesson.id)")
    }
}
