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
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    private var breathOrb: some View {
        PremiumBreathHourglass(
            progress: progress,
            isRunning: isBreathing,
            reduceMotion: reduceMotion,
            primary: selectedTheme.primary,
            accent: selectedTheme.accent,
            phaseText: isBreathing ? store.breathPhase : timeRemainingText,
            instruction: didCompleteSession ? "Complete" : breathPattern.instruction
        )
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
        }
    }

    private func completeSession() {
        remainingSeconds = 0
        isBreathing = false
        didCompleteSession = true
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
        completedBreathDayKeys = keys.suffix(366).joined(separator: ",")
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

private struct PremiumBreathHourglass: View {
    let progress: Double
    let isRunning: Bool
    let reduceMotion: Bool
    let primary: Color
    let accent: Color
    let phaseText: String
    let instruction: String

    private var sandProgress: Double {
        isRunning ? progress : (progress == 0 ? 0.14 : progress)
    }

    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 32, style: .continuous)
                .fill(.ultraThinMaterial)
                .overlay {
                    RoundedRectangle(cornerRadius: 32, style: .continuous)
                        .stroke(primary.opacity(0.18), lineWidth: 1)
                }
                .shadow(color: primary.opacity(0.14), radius: 24, y: 12)

            VStack(spacing: 10) {
                ZStack {
                    HourglassSand(
                        progress: sandProgress,
                        color: accent,
                        reduceMotion: reduceMotion,
                        isExhaling: phaseText == "Exhale"
                    )
                    .frame(width: 116, height: 148)

                    HourglassFrame(color: primary)
                        .frame(width: 116, height: 148)
                }
                .frame(height: 154)

                Text(phaseText)
                    .font(.title3.weight(.bold))
                    .foregroundStyle(primary)
                    .contentTransition(.opacity)
                    .animation(reduceMotion ? nil : .easeInOut(duration: 0.24), value: phaseText)
                Text(instruction)
                    .font(.caption.weight(.medium))
                    .foregroundStyle(.secondary)
                    .multilineTextAlignment(.center)
                    .lineLimit(2)
                    .frame(width: 190)
            }
            .padding(.vertical, 18)
        }
        .frame(maxWidth: .infinity)
        .frame(height: 254)
        .scaleEffect(isRunning && !reduceMotion ? 1.025 : 1)
        .animation(reduceMotion ? nil : .spring(response: 0.48, dampingFraction: 0.72), value: isRunning)
    }
}

private struct HourglassFrame: View {
    let color: Color

    var body: some View {
        ZStack {
            Capsule()
                .fill(color)
                .frame(width: 104, height: 9)
                .offset(y: -69)
            Capsule()
                .fill(color)
                .frame(width: 104, height: 9)
                .offset(y: 69)
            HourglassOutline()
                .stroke(color, style: StrokeStyle(lineWidth: 5, lineCap: .round, lineJoin: .round))
                .padding(.horizontal, 11)
                .padding(.vertical, 9)
        }
        .shadow(color: color.opacity(0.22), radius: 5, y: 3)
    }
}

private struct HourglassSand: View {
    let progress: Double
    let color: Color
    let reduceMotion: Bool
    let isExhaling: Bool

    var body: some View {
        TimelineView(.animation(minimumInterval: reduceMotion ? 1 : 1.0 / 30.0)) { timeline in
            let p = min(max(progress, 0), 1)
            let sand = LinearGradient(
                colors: [color.opacity(0.72), color, color.opacity(0.82)],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
            let cycle = timeline.date.timeIntervalSinceReferenceDate
                .truncatingRemainder(dividingBy: 1.2) / 1.2

            ZStack {
                HourglassChamber(isTop: true)
                    .fill(color.opacity(0.10))
                HourglassChamber(isTop: false)
                    .fill(color.opacity(0.10))
                HourglassSandFill(isTop: true, amount: 1 - p)
                    .fill(sand)
                HourglassSandFill(isTop: false, amount: p)
                    .fill(sand)
                Capsule()
                    .fill(color)
                    .frame(width: isExhaling && !reduceMotion ? 4 : 3, height: p < 0.98 ? 24 : 0)
                    .offset(y: -12)
                    .opacity(isExhaling ? 1 : 0.72)

                if isExhaling && !reduceMotion && p < 0.98 {
                    ForEach(0..<4, id: \.self) { index in
                        let particlePhase = (cycle + Double(index) * 0.23)
                            .truncatingRemainder(dividingBy: 1)
                        Circle()
                            .fill(color.opacity(0.9))
                            .frame(width: index.isMultiple(of: 2) ? 3 : 2, height: index.isMultiple(of: 2) ? 3 : 2)
                            .offset(
                                x: CGFloat(index - 1) * 1.8,
                                y: CGFloat(particlePhase * 28 - 12)
                            )
                            .opacity(0.3 + (1 - particlePhase) * 0.7)
                    }
                }
            }
            .padding(.horizontal, 14)
            .padding(.vertical, 18)
            .animation(reduceMotion ? nil : .easeInOut(duration: 0.82), value: p)
        }
    }
}

private struct HourglassOutline: Shape {
    func path(in rect: CGRect) -> Path {
        var path = Path()
        let inset: CGFloat = 8
        let top = rect.minY + inset
        let bottom = rect.maxY - inset
        let left = rect.minX + inset
        let right = rect.maxX - inset
        let middle = rect.midY
        path.move(to: CGPoint(x: left, y: top))
        path.addLine(to: CGPoint(x: left, y: top + 16))
        path.addCurve(to: CGPoint(x: rect.midX, y: middle), control1: CGPoint(x: left, y: middle - 25), control2: CGPoint(x: rect.midX - 9, y: middle - 9))
        path.addCurve(to: CGPoint(x: right, y: bottom - 16), control1: CGPoint(x: rect.midX + 9, y: middle + 9), control2: CGPoint(x: right, y: middle + 25))
        path.addLine(to: CGPoint(x: right, y: bottom))
        path.move(to: CGPoint(x: right, y: top))
        path.addLine(to: CGPoint(x: right, y: top + 16))
        path.addCurve(to: CGPoint(x: rect.midX, y: middle), control1: CGPoint(x: right, y: middle - 25), control2: CGPoint(x: rect.midX + 9, y: middle - 9))
        path.addCurve(to: CGPoint(x: left, y: bottom - 16), control1: CGPoint(x: rect.midX - 9, y: middle + 9), control2: CGPoint(x: left, y: middle + 25))
        path.addLine(to: CGPoint(x: left, y: bottom))
        return path
    }
}

private struct HourglassChamber: Shape {
    let isTop: Bool

    func path(in rect: CGRect) -> Path {
        var path = Path()
        let middle = rect.midY
        if isTop {
            path.move(to: CGPoint(x: rect.minX, y: rect.minY))
            path.addLine(to: CGPoint(x: rect.maxX, y: rect.minY))
            path.addLine(to: CGPoint(x: rect.midX, y: middle))
        } else {
            path.move(to: CGPoint(x: rect.midX, y: middle))
            path.addLine(to: CGPoint(x: rect.maxX, y: rect.maxY))
            path.addLine(to: CGPoint(x: rect.minX, y: rect.maxY))
        }
        path.closeSubpath()
        return path
    }
}

private struct HourglassSandFill: Shape {
    let isTop: Bool
    let amount: Double

    func path(in rect: CGRect) -> Path {
        let fill = min(max(amount, 0), 1)
        let middle = rect.midY
        var path = Path()

        if isTop {
            let endY = rect.minY + (middle - rect.minY) * fill
            let inset = (endY - rect.minY) / 2
            path.move(to: CGPoint(x: rect.minX, y: rect.minY))
            path.addLine(to: CGPoint(x: rect.maxX, y: rect.minY))
            path.addLine(to: CGPoint(x: rect.maxX - inset, y: endY))
            path.addLine(to: CGPoint(x: rect.minX + inset, y: endY))
        } else {
            let startY = middle
            let endY = middle + (rect.maxY - middle) * fill
            let inset = (endY - middle) / 2
            path.move(to: CGPoint(x: rect.midX, y: startY))
            path.addLine(to: CGPoint(x: rect.maxX - inset, y: endY))
            path.addLine(to: CGPoint(x: rect.minX + inset, y: endY))
        }
        path.closeSubpath()
        return path
    }
}
