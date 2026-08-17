import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { HomeView() }
                .tabItem { Label("Home", systemImage: "house.fill") }
            NavigationStack { ScannerView() }
                .tabItem { Label("Scan", systemImage: "camera.viewfinder") }
            NavigationStack { CoursesView() }
                .tabItem { Label("Courses", systemImage: "book.closed.fill") }
            NavigationStack { TutorView() }
                .tabItem { Label("AI Tutor", systemImage: "brain.head.profile") }
            NavigationStack { EarnView() }
                .tabItem { Label("Earn", systemImage: "bitcoinsign.circle.fill") }
        }
        .preferredColorScheme(.dark)
    }
}

struct HomeView: View {
    @EnvironmentObject private var progress: LearningProgress
    private let featured = BeyondMathContent.courses[0]

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                HeaderPanel(bits: progress.bits, xp: progress.xp)
                ContinueLearningCard(course: featured)
                DailyChallengeCard()

                Text("Recommended")
                    .font(.title3.bold())
                    .padding(.top, 4)

                LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 14) {
                    ForEach(BeyondMathContent.courses) { course in
                        NavigationLink(value: course) {
                            CourseTile(course: course)
                        }
                        .buttonStyle(.plain)
                    }
                }
            }
            .padding()
        }
        .background(AppBackground())
        .navigationTitle("Beyond Math")
        .navigationBarTitleDisplayMode(.inline)
        .navigationDestination(for: LearningCourse.self) { CourseDetailView(course: $0) }
    }
}

struct ScannerView: View {
    @State private var mode = "Steps"
    private let modes = ["Answer", "Steps", "Teach Me"]

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                ScannerFrame()

                Picker("Mode", selection: $mode) {
                    ForEach(modes, id: \.self) { Text($0) }
                }
                .pickerStyle(.segmented)

                NeonPanel {
                    VStack(alignment: .leading, spacing: 14) {
                        Label("Solution", systemImage: "sparkles")
                            .font(.headline)
                            .foregroundStyle(Color.mathBlue)
                        Text("x = 5")
                            .font(.system(size: 46, weight: .black, design: .rounded))
                            .foregroundStyle(Color.mathGreen)

                        ForEach(BeyondMathContent.scannerSteps) { step in
                            VStack(alignment: .leading, spacing: 6) {
                                Text("Step \(step.id): \(step.title)")
                                    .font(.subheadline.bold())
                                Text(step.expression)
                                    .font(.system(.body, design: .rounded))
                                    .foregroundStyle(.secondary)
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }
            .padding()
        }
        .background(AppBackground())
        .navigationTitle("AI Scanner")
    }
}

struct CoursesView: View {
    var body: some View {
        List {
            ForEach(BeyondTrack.allCases) { track in
                Section(track.rawValue) {
                    ForEach(BeyondMathContent.courses.filter { $0.track == track }) { course in
                        NavigationLink(value: course) {
                            HStack(spacing: 14) {
                                TrackIcon(track: course.track, size: 44)
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(course.title).font(.headline)
                                    Text(course.subtitle).font(.subheadline).foregroundStyle(.secondary)
                                }
                            }
                            .padding(.vertical, 6)
                        }
                    }
                }
            }
        }
        .scrollContentBackground(.hidden)
        .background(AppBackground())
        .navigationTitle("Courses")
        .navigationDestination(for: LearningCourse.self) { CourseDetailView(course: $0) }
    }
}

struct TutorView: View {
    @State private var prompt = ""
    private let starters = ["Help me solve 3x - 7 = 14", "Quiz me on algebra", "Explain Python variables"]

    var body: some View {
        VStack(spacing: 16) {
            NeonPanel {
                VStack(alignment: .leading, spacing: 16) {
                    TrackIcon(track: .ai, size: 58)
                    Text("Your AI Tutor")
                        .font(.largeTitle.bold())
                    Text("Ask for a hint, a step-by-step explanation, or a quick check of your reasoning.")
                        .foregroundStyle(.secondary)
                }
            }

            ForEach(starters, id: \.self) { starter in
                Button {
                    prompt = starter
                } label: {
                    HStack {
                        Text(starter)
                        Spacer()
                        Image(systemName: "arrow.up.right")
                    }
                    .padding()
                    .background(Color.mathPanel, in: RoundedRectangle(cornerRadius: 16))
                }
                .buttonStyle(.plain)
            }

            Spacer()

            HStack(spacing: 10) {
                TextField("Ask Beyond Math", text: $prompt)
                    .textFieldStyle(.plain)
                    .padding()
                    .background(Color.mathPanel, in: RoundedRectangle(cornerRadius: 18))
                Button {
                    prompt = ""
                } label: {
                    Image(systemName: "paperplane.fill")
                        .frame(width: 48, height: 48)
                        .background(Color.mathGreen, in: Circle())
                        .foregroundStyle(.black)
                }
            }
        }
        .padding()
        .background(AppBackground())
        .navigationTitle("AI Tutor")
    }
}

struct EarnView: View {
    @EnvironmentObject private var progress: LearningProgress

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                NeonPanel {
                    HStack {
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Bit$ Wallet")
                                .font(.title2.bold())
                            Text("Complete lessons, scans, and challenges to unlock rewards.")
                                .foregroundStyle(.secondary)
                        }
                        Spacer()
                        Text("Bit$ \(progress.bits)")
                            .font(.title2.bold())
                            .foregroundStyle(Color.mathGold)
                            .padding(.horizontal, 16)
                            .padding(.vertical, 10)
                            .background(Color.mathGold.opacity(0.12), in: Capsule())
                    }
                }

                Text("Achievements")
                    .font(.title3.bold())

                ForEach(BeyondMathContent.achievements) { achievement in
                    AchievementRow(achievement: achievement)
                }
            }
            .padding()
        }
        .background(AppBackground())
        .navigationTitle("Earn")
    }
}

struct CourseDetailView: View {
    let course: LearningCourse

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                NeonPanel {
                    VStack(alignment: .leading, spacing: 14) {
                        TrackIcon(track: course.track, size: 54)
                        Text(course.title)
                            .font(.largeTitle.bold())
                        Text(course.subtitle)
                            .foregroundStyle(.secondary)
                        ProgressView(value: course.progress)
                            .tint(color(for: course.track))
                    }
                }

                ForEach(course.lessons) { lesson in
                    NavigationLink(value: lesson) {
                        LessonRow(lesson: lesson, track: course.track)
                    }
                    .buttonStyle(.plain)
                }
            }
            .padding()
        }
        .background(AppBackground())
        .navigationTitle(course.track.rawValue)
        .navigationDestination(for: MathLesson.self) { LessonView(lesson: $0) }
    }
}

private struct HeaderPanel: View {
    let bits: Int
    let xp: Int

    var body: some View {
        NeonPanel {
            VStack(alignment: .leading, spacing: 18) {
                HStack(alignment: .top) {
                    VStack(alignment: .leading, spacing: 4) {
                        Text("Level 12")
                            .foregroundStyle(.secondary)
                        Text("Explorer")
                            .font(.largeTitle.bold())
                    }
                    Spacer()
                    Text("Bit$ \(bits)")
                        .font(.headline.bold())
                        .foregroundStyle(Color.mathGold)
                        .padding(.horizontal, 16)
                        .padding(.vertical, 10)
                        .background(Color.mathGold.opacity(0.12), in: Capsule())
                        .overlay(Capsule().stroke(Color.mathGold.opacity(0.45)))
                }

                VStack(alignment: .leading, spacing: 8) {
                    HStack {
                        Text("Today's Progress")
                        Spacer()
                        Text("\(xp) / 5,000 XP")
                    }
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    ProgressView(value: min(Double(xp) / 5000, 1))
                        .tint(Color.mathGreen)
                }
            }
        }
    }
}

private struct ContinueLearningCard: View {
    let course: LearningCourse

    var body: some View {
        NavigationLink(value: course) {
            NeonPanel {
                HStack(spacing: 16) {
                    TrackIcon(track: course.track, size: 64)
                    VStack(alignment: .leading, spacing: 7) {
                        Text("Continue Learning")
                            .font(.caption.bold())
                            .foregroundStyle(Color.mathBlue)
                        Text(course.title)
                            .font(.title2.bold())
                        Text(course.subtitle)
                            .foregroundStyle(.secondary)
                        ProgressView(value: course.progress)
                            .tint(Color.mathBlue)
                    }
                    Image(systemName: "play.fill")
                        .frame(width: 48, height: 48)
                        .background(Color.mathBlue, in: Circle())
                        .foregroundStyle(.white)
                }
            }
        }
        .buttonStyle(.plain)
    }
}

private struct DailyChallengeCard: View {
    var body: some View {
        NeonPanel {
            VStack(alignment: .leading, spacing: 14) {
                HStack {
                    Text("Daily Challenge")
                        .font(.title2.bold())
                    Spacer()
                    Text("+25 Bit$")
                        .font(.headline)
                        .foregroundStyle(Color.mathGold)
                }
                Text("Solve for x: 3x - 7 = 14")
                    .font(.title3.bold())
                NavigationLink {
                    LessonGameView(lesson: BeyondMathContent.algebraLessons[1])
                } label: {
                    Label("Start Challenge", systemImage: "bolt.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(Color.mathGreen)
            }
        }
    }
}

private struct CourseTile: View {
    let course: LearningCourse

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            TrackIcon(track: course.track, size: 42)
            Text(course.title)
                .font(.headline)
                .lineLimit(2)
                .fixedSize(horizontal: false, vertical: true)
            Text(course.track.rawValue)
                .font(.caption)
                .foregroundStyle(.secondary)
            ProgressView(value: course.progress)
                .tint(color(for: course.track))
        }
        .frame(maxWidth: .infinity, minHeight: 150, alignment: .topLeading)
        .padding()
        .background(Color.mathPanel, in: RoundedRectangle(cornerRadius: 18))
        .overlay(RoundedRectangle(cornerRadius: 18).stroke(color(for: course.track).opacity(0.35)))
    }
}

private struct ScannerFrame: View {
    var body: some View {
        VStack(spacing: 18) {
            ZStack {
                RoundedRectangle(cornerRadius: 26)
                    .fill(Color.black.opacity(0.3))
                    .overlay(RoundedRectangle(cornerRadius: 26).stroke(Color.mathBlue.opacity(0.6), lineWidth: 2))
                VStack(spacing: 14) {
                    Image(systemName: "viewfinder")
                        .font(.system(size: 54, weight: .semibold))
                        .foregroundStyle(Color.mathBlue)
                    Text("Solve for x:")
                        .font(.system(.title2, design: .rounded))
                    Text("2x + 5 = 15")
                        .font(.system(size: 42, weight: .bold, design: .rounded))
                }
                .padding()
            }
            .frame(height: 260)

            Button {
            } label: {
                Label("Scan Math Problem", systemImage: "camera.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .controlSize(.large)
            .tint(Color.mathBlue)
        }
    }
}

private struct AchievementRow: View {
    let achievement: Achievement

    var body: some View {
        HStack(spacing: 14) {
            Image(systemName: achievement.symbol)
                .font(.title2)
                .frame(width: 48, height: 48)
                .background(Color.mathPanel, in: Circle())
                .foregroundStyle(Color.mathGold)
            VStack(alignment: .leading, spacing: 4) {
                Text(achievement.title).font(.headline)
                Text(achievement.detail).font(.subheadline).foregroundStyle(.secondary)
            }
            Spacer()
            Text("+\(achievement.reward)")
                .font(.headline)
                .foregroundStyle(Color.mathGold)
        }
        .padding()
        .background(Color.mathPanel, in: RoundedRectangle(cornerRadius: 18))
    }
}

private struct LessonRow: View {
    let lesson: MathLesson
    let track: BeyondTrack

    var body: some View {
        HStack(spacing: 14) {
            Text("\(lesson.id)")
                .font(.headline.bold())
                .frame(width: 44, height: 44)
                .background(color(for: track).opacity(0.16), in: RoundedRectangle(cornerRadius: 12))
                .foregroundStyle(color(for: track))
            VStack(alignment: .leading, spacing: 5) {
                Text(lesson.title).font(.headline)
                Text(lesson.focus).font(.subheadline).foregroundStyle(.secondary)
            }
            Spacer()
            Image(systemName: "chevron.right")
                .foregroundStyle(.secondary)
        }
        .padding()
        .background(Color.mathPanel, in: RoundedRectangle(cornerRadius: 18))
    }
}

private struct TrackIcon: View {
    let track: BeyondTrack
    let size: CGFloat

    var body: some View {
        Image(systemName: track.symbol)
            .font(.system(size: size * 0.44, weight: .bold))
            .frame(width: size, height: size)
            .background(color(for: track).opacity(0.12), in: Circle())
            .overlay(Circle().stroke(color(for: track), lineWidth: 1.5))
            .foregroundStyle(color(for: track))
            .shadow(color: color(for: track).opacity(0.45), radius: 12)
    }
}

private struct NeonPanel<Content: View>: View {
    @ViewBuilder let content: Content

    var body: some View {
        content
            .frame(maxWidth: .infinity, alignment: .leading)
            .padding(20)
            .background(Color.mathPanel.opacity(0.92), in: RoundedRectangle(cornerRadius: 24))
            .overlay(RoundedRectangle(cornerRadius: 24).stroke(Color.white.opacity(0.1)))
    }
}

private struct AppBackground: View {
    var body: some View {
        LinearGradient(
            colors: [Color.mathNavy, Color.black, Color(red: 0.015, green: 0.035, blue: 0.075)],
            startPoint: .topLeading,
            endPoint: .bottomTrailing
        )
        .ignoresSafeArea()
    }
}

private func color(for track: BeyondTrack) -> Color {
    switch track {
    case .math: .mathBlue
    case .coding: .mathGreen
    case .stats: .mathGold
    case .ai: .mathPurple
    }
}
