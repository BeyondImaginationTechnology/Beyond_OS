import SwiftUI

struct AcademyView: View {
    @EnvironmentObject private var store: AppStore
    @AppStorage("BeyondFrench.academyDifficulty") private var selectedDifficulty = "beginner"

    private var selectedGroup: AgeGroup {
        let trackSlug: String
        switch selectedDifficulty {
        case "normal", "intermediate": trackSlug = "teen"
        case "advanced": trackSlug = "adult"
        default: trackSlug = "kids"
        }
        return store.academy.ageGroups.first { $0.slug == trackSlug }
            ?? store.academy.ageGroups.first
            ?? AcademyCatalog.fallback.ageGroups[0]
    }

    private var nextLesson: (module: AcademyModule, lesson: AcademyLesson, index: Int)? {
        for module in store.academy.modules {
            for index in module.lessons.indices where store.isLessonUnlocked(module: module, lessonIndex: index, ageGroup: selectedGroup) && !store.isLessonCompleted(module: module, lessonIndex: index, ageGroup: selectedGroup) {
                return (module, module.lessons[index], index)
            }
        }
        return nil
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                VStack(alignment: .leading, spacing: 14) {
                    AccessPill(text: "ALL LESSONS FREE · 1.2 BETA")
                    Text("Choose a path and start speaking.")
                        .font(.largeTitle.weight(.black))
                    Text("Learn French through short lessons, speaking, and everyday situations.")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                    Picker("Difficulty", selection: $selectedDifficulty) {
                        Text("Beginner").tag("beginner")
                        Text("Intermediate").tag("intermediate")
                        Text("Advanced").tag("advanced")
                    }
                    .pickerStyle(.menu)
                    FrenchGuidesView()
                }
                .padding(20)
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))
                .overlay(RoundedRectangle(cornerRadius: 22).stroke(store.appTheme.accent.opacity(0.18), lineWidth: 1))

                LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
                    MetricTile(title: "Lessons Done", value: "\(store.completedAcademyLessons(ageGroup: selectedGroup))/\(store.totalAcademyLessons)", systemImage: "checkmark.seal.fill", color: .green)
                    MetricTile(title: "Modules Open", value: "\(store.academy.modules.filter { store.isLessonUnlocked(module: $0, lessonIndex: 0, ageGroup: selectedGroup) }.count)", systemImage: "lock.open.fill", color: store.appTheme.accent)
                }

                if let nextLesson {
                    NavigationLink {
                        AcademyLessonDetailView(module: nextLesson.module, lesson: nextLesson.lesson, lessonIndex: nextLesson.index, ageGroup: selectedGroup)
                    } label: {
                        HStack(spacing: 14) {
                            Image(systemName: "play.circle.fill")
                                .font(.title)
                                .foregroundStyle(store.appTheme.accent)
                            VStack(alignment: .leading, spacing: 4) {
                                Text("Continue Academy")
                                    .font(.caption.weight(.bold))
                                    .foregroundStyle(.secondary)
                                Text("\(nextLesson.module.title) · Lesson \(nextLesson.index + 1)")
                                    .font(.headline)
                                Text(nextLesson.lesson.experience(for: selectedGroup).supportLine)
                                    .font(.subheadline)
                                    .foregroundStyle(.secondary)
                            }
                            Spacer()
                            Image(systemName: "chevron.right")
                                .foregroundStyle(.secondary)
                        }
                        .padding(16)
                        .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
                        .overlay(RoundedRectangle(cornerRadius: 18).stroke(store.appTheme.accent.opacity(0.16), lineWidth: 1))
                    }
                    .buttonStyle(.plain)
                }

                ForEach(store.academy.modules) { module in
                    ModuleCard(module: module, ageGroup: selectedGroup)
                }
            }
            .padding()
        }
        .background(store.appTheme.appBackground)
        .navigationTitle("Academy")
        .onAppear {
            if selectedDifficulty == "normal" { selectedDifficulty = "intermediate" }
        }
    }
}

private struct ModuleCard: View {
    @EnvironmentObject private var store: AppStore
    let module: AcademyModule
    let ageGroup: AgeGroup

    private var completedCount: Int {
        module.lessons.indices.filter { store.isLessonCompleted(module: module, lessonIndex: $0, ageGroup: ageGroup) }.count
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            HStack(alignment: .top, spacing: 14) {
                Text(module.icon)
                    .font(.largeTitle)
                    .frame(width: 52, height: 52)
                    .background(store.appTheme.accent.opacity(0.10), in: RoundedRectangle(cornerRadius: 16))
                VStack(alignment: .leading, spacing: 5) {
                    HStack {
                        Text(module.title)
                            .font(.title3.weight(.black))
                        Spacer()
                        Text("BETA FREE").font(.caption2.weight(.black)).foregroundStyle(.green)
                    }
                    Text(module.description)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }
            }

            SwiftUI.ProgressView(value: Double(completedCount), total: Double(max(module.lessons.count, 1)))
                .tint(store.appTheme.accent)

            VStack(spacing: 8) {
                ForEach(Array(module.lessons.enumerated()), id: \.offset) { index, lesson in
                    LessonRow(module: module, lesson: lesson, lessonIndex: index, ageGroup: ageGroup)
                }
            }
        }
        .padding(18)
        .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))
        .overlay(RoundedRectangle(cornerRadius: 22).stroke(store.appTheme.accent.opacity(0.18), lineWidth: 1))
    }
}

private struct LessonRow: View {
    @EnvironmentObject private var store: AppStore
    let module: AcademyModule
    let lesson: AcademyLesson
    let lessonIndex: Int
    let ageGroup: AgeGroup

    private var isUnlocked: Bool {
        store.isLessonUnlocked(module: module, lessonIndex: lessonIndex, ageGroup: ageGroup)
    }

    private var experience: AcademyLessonExperience {
        lesson.experience(for: ageGroup)
    }

    var body: some View {
        NavigationLink {
            AcademyLessonDetailView(module: module, lesson: lesson, lessonIndex: lessonIndex, ageGroup: ageGroup)
        } label: {
            HStack(spacing: 12) {
                Text("\(lessonIndex + 1)")
                    .font(.headline.weight(.black))
                    .foregroundStyle(isUnlocked ? store.appTheme.accent : .secondary)
                    .frame(width: 34, height: 34)
                    .background((isUnlocked ? store.appTheme.accent : Color.secondary).opacity(0.10), in: Circle())
                VStack(alignment: .leading, spacing: 3) {
                    Text(lesson.title).font(.subheadline.weight(.bold))
                    Text(experience.supportLine).font(.caption).foregroundStyle(.secondary)
                }
                Spacer()
                Image(systemName: store.isLessonCompleted(module: module, lessonIndex: lessonIndex, ageGroup: ageGroup) ? "checkmark.circle.fill" : (isUnlocked ? "chevron.right" : "lock.fill"))
                    .foregroundStyle(store.isLessonCompleted(module: module, lessonIndex: lessonIndex, ageGroup: ageGroup) ? .green : .secondary)
            }
            .contentShape(Rectangle())
            .opacity(isUnlocked ? 1 : 0.55)
        }
        .disabled(!isUnlocked)
        .buttonStyle(.plain)
    }
}

private struct AcademyLessonDetailView: View {
    @EnvironmentObject private var store: AppStore
    let module: AcademyModule
    let lesson: AcademyLesson
    let lessonIndex: Int
    let ageGroup: AgeGroup
    @State private var answer = ""
    @State private var result: LessonCheckResult?
    @State private var showNextLesson = false
    @State private var selectedGuide: FrenchGuide = .louis
    @FocusState private var answerFocused: Bool

    private var experience: AcademyLessonExperience {
        lesson.experience(for: ageGroup)
    }

    private var phrase: BeyondPhrase {
        lesson.beyondPhrase
    }

    private var nextLessonRoute: (module: AcademyModule, lesson: AcademyLesson, index: Int)? {
        var foundCurrentLesson = false
        for academyModule in store.academy.modules {
            for index in academyModule.lessons.indices {
                let isCurrentLesson = academyModule.id == module.id && index == lessonIndex
                if foundCurrentLesson,
                   store.isLessonUnlocked(module: academyModule, lessonIndex: index, ageGroup: ageGroup) {
                    return (academyModule, academyModule.lessons[index], index)
                }
                if isCurrentLesson {
                    foundCurrentLesson = true
                }
            }
        }
        return nil
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                Text(module.icon)
                    .font(.largeTitle)
                Text(lesson.title)
                    .font(.largeTitle.weight(.black))
                VStack(alignment: .leading, spacing: 6) {
                    Text(difficultyTitle)
                        .font(.caption.weight(.bold))
                        .foregroundStyle(store.appTheme.accent)
                    Text(lesson.english)
                        .font(.title3)
                        .foregroundStyle(.secondary)
                }

                VStack(alignment: .leading, spacing: 12) {
                    Text(lesson.french)
                        .font(.system(size: 36, weight: .black, design: .rounded))
                        .foregroundStyle(store.appTheme.accent)
                    Text(lesson.pronunciation)
                        .font(.headline)
                        .foregroundStyle(.secondary)
                    Button { store.speak(lesson.french, language: "fr-FR") } label: {
                        Label("Listen in French", systemImage: "speaker.wave.2.fill").frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .controlSize(.large)
                }
                .padding(20)
                .background(store.appTheme.accent.opacity(0.08), in: RoundedRectangle(cornerRadius: 20))

                VStack(alignment: .leading, spacing: 12) {
                    Text("Learn with a guide")
                        .font(.headline)
                    FrenchGuidesView(selectedGuide: selectedGuide) { selectedGuide = $0 }
                    Text(selectedGuide.prompt)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                    if !selectedGuide.bridge(in: phrase).isEmpty {
                        Text(selectedGuide.bridge(in: phrase))
                            .font(.title3.weight(.bold))
                        Button {
                            store.speak(selectedGuide.bridge(in: phrase), language: selectedGuide.audioLocale)
                        } label: {
                            Label("Listen with \(selectedGuide.name)", systemImage: "speaker.wave.2.fill")
                        }
                        .buttonStyle(.bordered)
                    } else {
                        Text("\(selectedGuide.name)'s bridge is coming soon. Practice the French phrase above.")
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    }
                }
                .padding(16)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
                .overlay(RoundedRectangle(cornerRadius: 18).stroke(store.appTheme.accent.opacity(0.16), lineWidth: 1))

                LessonInfoBlock(title: "French tip", text: experience.teaching, systemImage: "lightbulb.fill", color: .yellow)
                LessonInfoBlock(title: "Practice idea", text: experience.practice, systemImage: "person.wave.2.fill", color: .teal)
                LessonInfoBlock(title: "Culture", text: lesson.culture, systemImage: "globe.americas.fill", color: .orange)

                VStack(alignment: .leading, spacing: 12) {
                    Text("Lesson Check")
                        .font(.headline)
                    Text(experience.checkPrompt)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                    TextField("French answer", text: $answer)
                        .textInputAutocapitalization(.sentences)
                        .submitLabel(.done)
                        .focused($answerFocused)
                        .padding(14)
                        .background(Color.white.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
                        .overlay(RoundedRectangle(cornerRadius: 14).stroke(Color.white.opacity(0.12), lineWidth: 1))
                        .onSubmit(checkLesson)
                    HStack {
                        Button(action: checkLesson) {
                            Label("Check", systemImage: "checkmark.circle.fill").frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.borderedProminent)
                        Button {
                            result = .revealed
                        } label: {
                            Label("Reveal", systemImage: "eye.fill").frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.bordered)
                    }
                    .controlSize(.large)
                    if let result {
                        Text(result.message(expected: phrase.french))
                            .font(.headline)
                            .foregroundStyle(result.color)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .padding(14)
                            .background(result.color.opacity(0.10), in: RoundedRectangle(cornerRadius: 14))
                            .transition(.move(edge: .top).combined(with: .opacity))
                    }
                }
                .animation(.snappy(duration: 0.24), value: result)
                .padding(16)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
                .overlay(RoundedRectangle(cornerRadius: 18).stroke(store.appTheme.accent.opacity(0.16), lineWidth: 1))
            }
            .padding()
        }
        .background(store.appTheme.appBackground)
        .navigationTitle("Lesson \(lessonIndex + 1)")
        .navigationBarTitleDisplayMode(.inline)
        .navigationDestination(isPresented: $showNextLesson) {
            if let nextLessonRoute {
                AcademyLessonDetailView(
                    module: nextLessonRoute.module,
                    lesson: nextLessonRoute.lesson,
                    lessonIndex: nextLessonRoute.index,
                    ageGroup: ageGroup
                )
            }
        }
    }

    private func checkLesson() {
        if store.checkAnswer(answer, expected: phrase.french) {
            answerFocused = false
            store.completeLesson(module: module, lessonIndex: lessonIndex, ageGroup: ageGroup)
            withAnimation(.snappy(duration: 0.24)) {
                answer = ""
                result = .correct
            }
            advanceToNextLessonIfNeeded()
        } else {
            withAnimation(.snappy(duration: 0.24)) {
                result = .incorrect
            }
        }
    }

    private var difficultyTitle: String {
        switch ageGroup.slug {
        case "teen": "Intermediate French"
        case "adult": "Advanced French"
        default: "Beginner French"
        }
    }

    private func advanceToNextLessonIfNeeded() {
        guard nextLessonRoute != nil else { return }
        Task {
            try? await Task.sleep(for: .milliseconds(850))
            await MainActor.run {
                guard result == .correct else { return }
                showNextLesson = true
            }
        }
    }
}

private enum LessonCheckResult: Equatable {
    case correct
    case incorrect
    case revealed

    var color: Color {
        switch self {
        case .correct: .green
        case .incorrect: .orange
        case .revealed: .indigo
        }
    }

    func message(expected: String) -> String {
        switch self {
        case .correct: "Lesson complete. Great work."
        case .incorrect: "Not yet. Listen again and try once more."
        case .revealed: "Answer: \(expected)"
        }
    }
}

struct LessonInfoBlock: View {
    @EnvironmentObject private var store: AppStore
    let title: String
    let text: String
    let systemImage: String
    let color: Color

    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            Image(systemName: systemImage)
                .foregroundStyle(color)
                .frame(width: 24)
            VStack(alignment: .leading, spacing: 4) {
                Text(title).font(.headline)
                Text(text).foregroundStyle(.secondary)
            }
            Spacer()
        }
        .padding(16)
        .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
        .overlay(RoundedRectangle(cornerRadius: 18).stroke(color.opacity(0.16), lineWidth: 1))
    }
}
