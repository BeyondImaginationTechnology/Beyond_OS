import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { LearningCenterView() }
                .tabItem { Label("Learn", systemImage: "graduationcap.fill") }
            NavigationStack { PracticeHubView() }
                .tabItem { Label("Play", systemImage: "gamecontroller.fill") }
            NavigationStack { ProgressView() }
                .tabItem { Label("Progress", systemImage: "chart.bar.fill") }
        }
        .preferredColorScheme(.dark)
    }
}

struct LearningCenterView: View {
    @EnvironmentObject private var progress: LearningProgress

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 22) {
                VStack(alignment: .leading, spacing: 8) {
                    Text("KIDS · MODULE 1").font(.caption.bold()).foregroundStyle(Color.mathGreen)
                    Text("Number Sense").font(.largeTitle.bold())
                    Text("Learn with narrated explanations, worked examples, and games—not just tests.")
                        .foregroundStyle(.secondary)
                    ProgressView(value: Double(progress.completed.count), total: 10)
                }
                ForEach(NumberSenseContent.lessons) { lesson in
                    NavigationLink(value: lesson) {
                        HStack(spacing: 14) {
                            Text("\(lesson.id)").font(.title3.bold()).frame(width: 44, height: 44).background(Color.mathBlue.opacity(0.18), in: RoundedRectangle(cornerRadius: 13))
                            VStack(alignment: .leading) {
                                Text(lesson.title).font(.headline)
                                Text(lesson.focus).font(.subheadline).foregroundStyle(.secondary)
                            }
                            Spacer()
                            Image(systemName: progress.completed.contains(lesson.id) ? "checkmark.circle.fill" : "chevron.right")
                                .foregroundStyle(progress.completed.contains(lesson.id) ? Color.mathGreen : .secondary)
                        }
                        .padding().background(Color.mathPanel, in: RoundedRectangle(cornerRadius: 20))
                    }.buttonStyle(.plain)
                }
            }.padding()
        }
        .background(Color.mathNavy.ignoresSafeArea())
        .navigationTitle("Learning Center")
        .navigationDestination(for: MathLesson.self) { LessonView(lesson: $0) }
    }
}

struct PracticeHubView: View {
    var body: some View {
        List(NumberSenseContent.lessons) { lesson in
            NavigationLink(lesson.game.question) { LessonGameView(lesson: lesson) }
        }
        .navigationTitle("Game Lab")
    }
}

struct ProgressView: View {
    @EnvironmentObject private var progress: LearningProgress
    var body: some View {
        VStack(spacing: 22) {
            Image(systemName: "chart.bar.fill").font(.system(size: 56)).foregroundStyle(Color.mathGreen)
            Text("\(progress.completed.count) of 10").font(.system(size: 44, weight: .bold))
            Text("Number Sense lessons complete").foregroundStyle(.secondary)
            ProgressView(value: Double(progress.completed.count), total: 10).padding(.horizontal, 42)
        }.navigationTitle("Your Progress")
    }
}
