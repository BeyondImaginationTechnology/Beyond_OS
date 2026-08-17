import SwiftUI

struct AcademyView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("completedAcademyLessonIDs") private var completedLessonIDs = ""

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var completedIDs: Set<String> {
        Set(completedLessonIDs.split(separator: ",").map(String.init))
    }

    private var completedCount: Int {
        completedIDs.count
    }

    private var totalLessonCount: Int {
        store.academyPaths.reduce(0) { $0 + $1.lessons.count }
    }

    private var openLessonCount: Int {
        totalLessonCount
    }

    private var nextLesson: (path: AcademyPath, lesson: AcademyLesson, index: Int)? {
        for path in store.academyPaths {
            for index in path.lessons.indices where !completedIDs.contains("\(path.lessons[index].id)") {
                return (path, path.lessons[index], index)
            }
        }
        guard let path = store.academyPaths.first, let lesson = path.lessons.first else { return nil }
        return (path, lesson, 0)
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                hero
                metricGrid
                continueCard
                ForEach(store.academyPaths) { path in
                    AcademyModuleCard(path: path, completedIDs: completedIDs, theme: selectedTheme)
                }
            }
            .padding()
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle("Academy")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var hero: some View {
        VStack(alignment: .leading, spacing: 14) {
            Label("Daily Breath Academy", systemImage: "graduationcap.fill")
                .font(.caption.bold())
                .foregroundStyle(selectedTheme.accent)
            Text("Learn the rhythm behind the practice.")
                .font(.largeTitle.weight(.black))
            Text("Short local lessons for Scripture, prayer, breathing, and reflection. Everything here is available inside Daily Breath.")
                .foregroundStyle(.secondary)
        }
        .padding(20)
        .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
    }

    private var metricGrid: some View {
        LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
            AcademyMetricTile(title: "Lessons Complete", value: "\(completedCount)", systemImage: "checkmark.seal.fill", color: selectedTheme.primary)
            AcademyMetricTile(title: "Open Lessons", value: "\(openLessonCount)", systemImage: "lock.open.fill", color: selectedTheme.accent)
        }
    }

    @ViewBuilder
    private var continueCard: some View {
        if let nextLesson {
            NavigationLink {
                AcademyLessonView(path: nextLesson.path, lesson: nextLesson.lesson, lessonIndex: nextLesson.index)
            } label: {
                HStack(spacing: 14) {
                    Image(systemName: completedCount == totalLessonCount ? "arrow.clockwise.circle.fill" : "play.circle.fill")
                        .font(.title)
                        .foregroundStyle(selectedTheme.accent)
                    VStack(alignment: .leading, spacing: 4) {
                        Text(completedCount == totalLessonCount ? "Review Academy" : "Continue Academy")
                            .font(.caption.weight(.bold))
                            .foregroundStyle(.secondary)
                        Text("\(nextLesson.path.title) · Lesson \(nextLesson.index + 1)")
                            .font(.headline)
                        Text(nextLesson.lesson.summary)
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                            .lineLimit(2)
                    }
                    Spacer()
                    Image(systemName: "chevron.right")
                        .foregroundStyle(.secondary)
                }
                .padding(16)
                .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
            }
            .buttonStyle(.plain)
        }
    }
}

private struct AcademyMetricTile: View {
    let title: String
    let value: String
    let systemImage: String
    let color: Color

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Image(systemName: systemImage)
                .foregroundStyle(color)
            Text(value)
                .font(.title.weight(.black))
            Text(title)
                .font(.caption.bold())
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(16)
        .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
    }
}

private struct AcademyModuleCard: View {
    let path: AcademyPath
    let completedIDs: Set<String>
    let theme: DailyBreathTheme

    private var completedCount: Int {
        path.lessons.filter { completedIDs.contains("\($0.id)") }.count
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            HStack(alignment: .top, spacing: 14) {
                Image(systemName: path.systemImage)
                    .font(.title2)
                    .foregroundStyle(theme.accent)
                    .frame(width: 48, height: 48)
                    .background(theme.accent.opacity(0.12), in: RoundedRectangle(cornerRadius: 8))
                VStack(alignment: .leading, spacing: 5) {
                    Text(path.title)
                        .font(.title3.weight(.black))
                    Text(path.subtitle)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }
            }

            ProgressView(value: Double(completedCount), total: Double(max(path.lessons.count, 1)))
                .tint(theme.primary)

            VStack(spacing: 8) {
                ForEach(Array(path.lessons.enumerated()), id: \.element.id) { index, lesson in
                    AcademyLessonRow(path: path, lesson: lesson, lessonIndex: index, isComplete: completedIDs.contains("\(lesson.id)"), theme: theme)
                }
            }
        }
        .padding(18)
        .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
    }
}

private struct AcademyLessonRow: View {
    let path: AcademyPath
    let lesson: AcademyLesson
    let lessonIndex: Int
    let isComplete: Bool
    let theme: DailyBreathTheme

    var body: some View {
        NavigationLink {
            AcademyLessonView(path: path, lesson: lesson, lessonIndex: lessonIndex)
        } label: {
            HStack(spacing: 12) {
                Text("\(lessonIndex + 1)")
                    .font(.headline.weight(.black))
                    .foregroundStyle(isComplete ? .white : theme.accent)
                    .frame(width: 34, height: 34)
                    .background(isComplete ? theme.primary : theme.accent.opacity(0.12), in: Circle())
                VStack(alignment: .leading, spacing: 3) {
                    Text(lesson.title)
                        .font(.subheadline.weight(.bold))
                    Text("\(lesson.duration) · \(lesson.scripture)")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Spacer()
                Image(systemName: isComplete ? "checkmark.circle.fill" : "chevron.right")
                    .foregroundStyle(isComplete ? theme.primary : .secondary)
            }
            .padding(12)
            .background(Color(.secondarySystemGroupedBackground), in: RoundedRectangle(cornerRadius: 8))
        }
        .buttonStyle(.plain)
    }
}

private struct AcademyLessonView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("completedAcademyLessonIDs") private var completedLessonIDs = ""

    let path: AcademyPath
    let lesson: AcademyLesson
    let lessonIndex: Int

    @State private var answer = ""
    @State private var checkResult: LessonCheckResult?

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var isComplete: Bool {
        completedLessonIDs.split(separator: ",").contains(Substring("\(lesson.id)"))
    }

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Label(path.title, systemImage: path.systemImage)
                        .font(.caption.bold())
                        .foregroundStyle(selectedTheme.accent)
                    Text(lesson.title)
                        .font(.largeTitle.weight(.black))
                    Text(lesson.summary)
                        .foregroundStyle(.secondary)
                    HStack {
                        Label(lesson.scripture, systemImage: "book.closed.fill")
                        Spacer()
                        Label(lesson.duration, systemImage: "clock.fill")
                    }
                    .font(.caption.bold())
                    .foregroundStyle(selectedTheme.primary)
                }
                .padding(.vertical, 8)
            }

            Section("Scripture") {
                Text(lesson.scripture)
                    .font(.headline)
                    .foregroundStyle(selectedTheme.primary)
            }

            Section("Teaching") {
                Text(lesson.teaching)
                    .font(.body)
                Button {
                    store.speakAcademyLesson(lesson)
                } label: {
                    Label("Listen to Lesson", systemImage: "speaker.wave.2.fill")
                }
            }

            Section("Practice") {
                Text(lesson.practice)
            }

            Section("Reflection") {
                Text(lesson.reflectionPrompt)
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Reflect in Journal", systemImage: "square.and.pencil")
                }
                .simultaneousGesture(TapGesture().onEnded {
                    store.prepareJournalReflection(prompt: lesson.reflectionPrompt)
                })
            }

            Section("Lesson Check") {
                Text(lesson.checkPrompt)
                    .foregroundStyle(.secondary)
                TextField("Your answer", text: $answer)
                    .textInputAutocapitalization(.sentences)
                    .submitLabel(.done)
                    .onSubmit(checkLesson)
                HStack {
                    Button {
                        checkLesson()
                    } label: {
                        Label("Check", systemImage: "checkmark.circle.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(selectedTheme.primary)

                    Button {
                        checkResult = .revealed
                    } label: {
                        Label("Reveal", systemImage: "eye.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.bordered)
                }
                .controlSize(.large)

                if let checkResult {
                    Text(checkResult.message(expected: lesson.checkAnswer))
                        .font(.headline)
                        .foregroundStyle(checkResult.color)
                        .padding(12)
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .background(checkResult.color.opacity(0.10), in: RoundedRectangle(cornerRadius: 8))
                }
            }

            Section {
                Button {
                    markComplete()
                } label: {
                    Label(isComplete ? "Lesson Complete" : "Mark Lesson Complete", systemImage: isComplete ? "checkmark.circle.fill" : "circle")
                }
                .foregroundStyle(selectedTheme.primary)
            }
        }
        .navigationTitle("Lesson \(lessonIndex + 1)")
        .navigationBarTitleDisplayMode(.inline)
    }

    private func checkLesson() {
        if answer.normalizedAcademyAnswer.contains(lesson.checkAnswer.normalizedAcademyAnswer) {
            checkResult = .correct
            markComplete()
        } else {
            checkResult = .incorrect
        }
    }

    private func markComplete() {
        var ids = completedAcademyIDs
        let lessonID = "\(lesson.id)"
        if !ids.contains(lessonID) {
            ids.append(lessonID)
        }
        completedLessonIDs = ids.joined(separator: ",")
    }

    private var completedAcademyIDs: [String] {
        completedLessonIDs
            .split(separator: ",")
            .map(String.init)
            .filter { !$0.isEmpty }
    }
}

private enum LessonCheckResult {
    case correct
    case incorrect
    case revealed

    var color: Color {
        switch self {
        case .correct: .green
        case .incorrect: .red
        case .revealed: .orange
        }
    }

    func message(expected: String) -> String {
        switch self {
        case .correct:
            return "Correct. Lesson marked complete."
        case .incorrect:
            return "Not quite yet. Try again, or reveal the answer."
        case .revealed:
            return "Suggested answer: \(expected)"
        }
    }
}

private extension String {
    var normalizedAcademyAnswer: String {
        lowercased()
            .folding(options: [.diacriticInsensitive, .caseInsensitive], locale: .current)
            .replacingOccurrences(of: "[^a-z0-9 ]", with: "", options: .regularExpression)
            .trimmingCharacters(in: .whitespacesAndNewlines)
    }
}
