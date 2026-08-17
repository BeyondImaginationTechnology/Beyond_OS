import SwiftUI
import UIKit

struct BreatheView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @Environment(\.scenePhase) private var scenePhase

    @AppStorage("breathDurationSeconds") private var durationSeconds = 120
    @AppStorage("completedBreathDayKeys") private var completedBreathDayKeys = ""
    @AppStorage("lastBreathMood") private var lastMood = ""
    @AppStorage("lastBreathComparison") private var lastComparison = ""
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    @State private var isBreathing = false
    @State private var remainingSeconds = 120
    @State private var didCompleteSession = false
    @State private var lastBackgroundedAt: Date?

    private let timer = Timer.publish(every: 1, on: .main, in: .common).autoconnect()
    private let durations = [60, 120, 180, 300]
    private let moods = ["Calm", "Good", "Okay", "Heavy"]
    private let comparisons = ["Calmer", "Same", "Harder"]

    private var breathPattern: BreathPattern {
        BreathPattern.breathOfTheDay()
    }

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var weeklyBreathCount: Int {
        completedDayDates.count
    }

    private var completedDayDates: [Date] {
        let calendar = Calendar.current
        let today = calendar.startOfDay(for: Date())
        return completedBreathDayKeys
            .split(separator: ",")
            .compactMap { Self.dayFormatter.date(from: String($0)) }
            .filter { date in
                guard let days = calendar.dateComponents([.day], from: date, to: today).day else { return false }
                return days >= 0 && days < 7
            }
    }

    private var progress: Double {
        guard durationSeconds > 0 else { return 0 }
        return Double(durationSeconds - remainingSeconds) / Double(durationSeconds)
    }

    private var timeRemainingText: String {
        let minutes = remainingSeconds / 60
        let seconds = remainingSeconds % 60
        return "\(minutes):\(String(format: "%02d", seconds))"
    }

    var body: some View {
        ScrollView {
            VStack(spacing: 24) {
                header
                breathOrb
                sessionControls
                if didCompleteSession {
                    completionPanel
                }
                practiceList
            }
            .padding(.horizontal, 20)
            .padding(.vertical, 24)
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle("Breathe")
        .navigationBarTitleDisplayMode(.inline)
        .onAppear {
            remainingSeconds = durationSeconds
        }
        .onReceive(timer) { _ in
            tick()
        }
        .onChange(of: durationSeconds) { _, newValue in
            guard !isBreathing else { return }
            remainingSeconds = newValue
            didCompleteSession = false
        }
        .onChange(of: scenePhase) { _, phase in
            handleScenePhase(phase)
        }
    }

    private var header: some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Breath of the Day", systemImage: "sparkles")
                .font(.caption.weight(.bold))
                .foregroundStyle(selectedTheme.accent)
            Text(breathPattern.title)
                .font(.largeTitle.weight(.black))
                .foregroundStyle(selectedTheme.primary)
            Text(breathPattern.intention)
                .font(.body)
                .foregroundStyle(.secondary)
            Text(breathPattern.rhythmText)
                .font(.subheadline.weight(.semibold))
                .foregroundStyle(selectedTheme.primary)
            Button {
                store.speakBreathPattern(breathPattern)
            } label: {
                Label("Listen to Guidance", systemImage: "speaker.wave.2.fill")
            }
            .buttonStyle(.bordered)
            .tint(selectedTheme.primary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    private var breathOrb: some View {
        ZStack {
            Circle()
                .fill(selectedTheme.primary.opacity(0.14))
                .frame(width: isBreathing && !reduceMotion ? 252 : 178, height: isBreathing && !reduceMotion ? 252 : 178)
                .animation(reduceMotion ? nil : .easeInOut(duration: Double(breathPattern.inhale)).repeatForever(autoreverses: true), value: isBreathing)
            Circle()
                .stroke(selectedTheme.accent, lineWidth: 4)
                .frame(width: 178, height: 178)
            VStack(spacing: 8) {
                Text(isBreathing ? store.breathPhase : timeRemainingText)
                    .font(.title2.weight(.bold))
                Text(didCompleteSession ? "Complete" : breathPattern.instruction)
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    .multilineTextAlignment(.center)
                    .frame(width: 132)
            }
        }
        .accessibilityElement(children: .combine)
        .accessibilityLabel(didCompleteSession ? "Breathing session complete" : "\(timeRemainingText) remaining")
    }

    private var sessionControls: some View {
        VStack(spacing: 14) {
            Picker("Duration", selection: $durationSeconds) {
                ForEach(durations, id: \.self) { seconds in
                    Text(durationLabel(for: seconds)).tag(seconds)
                }
            }
            .pickerStyle(.segmented)
            .disabled(isBreathing)

            Button {
                isBreathing ? pauseSession() : startSession()
            } label: {
                Label(isBreathing ? "Pause" : didCompleteSession ? "Quick Repeat" : "Begin", systemImage: isBreathing ? "pause.fill" : didCompleteSession ? "repeat" : "play.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .tint(selectedTheme.primary)
            .controlSize(.large)

            HStack {
                Label("\(weeklyBreathCount) days this week", systemImage: "calendar.badge.checkmark")
                Spacer()
                Text("Remembers \(durationLabel(for: durationSeconds))")
            }
            .font(.caption.weight(.semibold))
            .foregroundStyle(.secondary)
        }
    }

    private var completionPanel: some View {
        VStack(alignment: .leading, spacing: 14) {
            Text("How did that feel?")
                .font(.headline)
            HStack {
                ForEach(moods, id: \.self) { mood in
                    Button(mood) {
                        lastMood = mood
                    }
                    .buttonStyle(.bordered)
                    .tint(lastMood == mood ? selectedTheme.primary : .secondary)
                }
            }
            Text("Compared with yesterday")
                .font(.subheadline.weight(.semibold))
            HStack {
                ForEach(comparisons, id: \.self) { comparison in
                    Button(comparison) {
                        lastComparison = comparison
                    }
                    .buttonStyle(.bordered)
                    .tint(lastComparison == comparison ? selectedTheme.accent : .secondary)
                }
            }
            NavigationLink {
                JournalView()
            } label: {
                Label("Save Feeling to Journal", systemImage: "square.and.pencil")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .tint(selectedTheme.primary)
            .simultaneousGesture(TapGesture().onEnded {
                store.prepareJournalReflection(
                    prompt: "After today's breath session",
                    text: breathJournalText,
                    mood: lastMood.isEmpty ? "Peaceful" : lastMood
                )
            })
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(16)
        .background(.white.opacity(0.72), in: RoundedRectangle(cornerRadius: 8))
    }

    private var breathJournalText: String {
        let moodText = lastMood.isEmpty ? "I noticed how I felt after breathing." : "I felt \(lastMood.lowercased()) after breathing."
        guard !lastComparison.isEmpty else { return moodText }
        return "\(moodText) Compared with yesterday, today felt \(lastComparison.lowercased())."
    }

    private var practiceList: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Practices")
                .font(.headline)
            ForEach(store.practices) { practice in
                Label {
                    VStack(alignment: .leading, spacing: 3) {
                        Text(practice.title)
                            .font(.body.weight(.semibold))
                        Text(practice.subtitle)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                } icon: {
                    Image(systemName: practice.systemImage)
                        .foregroundStyle(selectedTheme.accent)
                }
                .frame(maxWidth: .infinity, alignment: .leading)
                .padding(.vertical, 4)
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    private func startSession() {
        if didCompleteSession {
            remainingSeconds = durationSeconds
            didCompleteSession = false
        }
        store.breathPhase = "Inhale"
        store.speakBreathCue("Inhale")
        isBreathing = true
    }

    private func pauseSession() {
        isBreathing = false
    }

    private func tick() {
        guard isBreathing else { return }
        guard remainingSeconds > 1 else {
            completeSession()
            return
        }
        remainingSeconds -= 1
        updateBreathPhase()
    }

    private func updateBreathPhase() {
        let cycleLength = breathPattern.inhale + breathPattern.hold + breathPattern.exhale
        let elapsed = (durationSeconds - remainingSeconds) % cycleLength
        let nextPhase: String
        if elapsed < breathPattern.inhale {
            nextPhase = "Inhale"
        } else if elapsed < breathPattern.inhale + breathPattern.hold {
            nextPhase = "Hold"
        } else {
            nextPhase = "Exhale"
        }
        if nextPhase != store.breathPhase {
            store.breathPhase = nextPhase
            store.speakBreathCue(nextPhase)
        }
    }

    private func completeSession() {
        remainingSeconds = 0
        isBreathing = false
        didCompleteSession = true
        store.speakBreathCue("Complete")
        recordToday()
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }

    private func recordToday() {
        let todayKey = Self.dayFormatter.string(from: Date())
        var keys = completedBreathDayKeys
            .split(separator: ",")
            .map(String.init)
            .filter { !$0.isEmpty }
        if !keys.contains(todayKey) {
            keys.append(todayKey)
        }
        completedBreathDayKeys = keys.suffix(14).joined(separator: ",")
    }

    private func handleScenePhase(_ phase: ScenePhase) {
        switch phase {
        case .inactive, .background:
            lastBackgroundedAt = isBreathing ? Date() : nil
        case .active:
            guard isBreathing, let lastBackgroundedAt else { return }
            let elapsed = max(0, Int(Date().timeIntervalSince(lastBackgroundedAt)))
            remainingSeconds = max(0, remainingSeconds - elapsed)
            if remainingSeconds == 0 {
                completeSession()
            }
            self.lastBackgroundedAt = nil
        @unknown default:
            break
        }
    }

    private func durationLabel(for seconds: Int) -> String {
        "\(seconds / 60)m"
    }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}
