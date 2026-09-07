import SwiftUI
import UIKit

private enum BreathPhase: String {
    case inhale
    case holdIn
    case exhale
    case holdOut

    var title: String {
        switch self {
        case .inhale: return "Inhale"
        case .holdIn: return "Hold"
        case .exhale: return "Exhale"
        case .holdOut: return "Rest"
        }
    }
}

struct BreatheView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @Environment(\.scenePhase) private var scenePhase

    @AppStorage("breathDurationSeconds") private var durationSeconds = 120
    @AppStorage("breathPatternID") private var selectedPatternID = 0
    @AppStorage("completedBreathDayKeys") private var completedBreathDayKeys = ""
    @AppStorage("lastBreathMood") private var lastMood = ""
    @AppStorage("lastBreathComparison") private var lastComparison = ""
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    @State private var isBreathing = false
    @State private var remainingSeconds = 120
    @State private var didCompleteSession = false
    @State private var lastBackgroundedAt: Date?
    @State private var currentPhase: BreathPhase = .inhale
    @State private var hasStartedSession = false

    private let timer = Timer.publish(every: 1, on: .main, in: .common).autoconnect()
    private let durations = [60, 120, 180, 300]
    private let moods = ["Calm", "Good", "Okay", "Heavy"]
    private let comparisons = ["Calmer", "Same", "Harder"]

    private var breathPattern: BreathPattern {
        selectedPatternID == 0
            ? BreathPattern.breathOfTheDay()
            : BreathPattern.sessionPatterns.first(where: { $0.id == selectedPatternID }) ?? BreathPattern.breathOfTheDay()
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

    private var timeRemainingText: String {
        let minutes = remainingSeconds / 60
        let seconds = remainingSeconds % 60
        return "\(minutes):\(String(format: "%02d", seconds))"
    }

    private var cycleLength: Int {
        breathPattern.inhale + breathPattern.hold + breathPattern.exhale + breathPattern.holdOut
    }

    private var cyclePosition: Int {
        guard cycleLength > 0 else { return 0 }
        return max(0, durationSeconds - remainingSeconds) % cycleLength
    }

    private var phaseDuration: Int {
        switch currentPhase {
        case .inhale: return breathPattern.inhale
        case .holdIn: return breathPattern.hold
        case .exhale: return breathPattern.exhale
        case .holdOut: return breathPattern.holdOut
        }
    }

    private var phaseElapsed: Int {
        switch currentPhase {
        case .inhale: return cyclePosition
        case .holdIn: return cyclePosition - breathPattern.inhale
        case .exhale: return cyclePosition - breathPattern.inhale - breathPattern.hold
        case .holdOut: return cyclePosition - breathPattern.inhale - breathPattern.hold - breathPattern.exhale
        }
    }

    private var phaseProgress: Double {
        guard phaseDuration > 0 else { return 1 }
        return min(1, max(0, Double(phaseElapsed) / Double(phaseDuration)))
    }

    private var phaseCountdownText: String {
        "\(max(1, phaseDuration - phaseElapsed))s"
    }

    private var cycleText: String {
        let completedCycles = max(0, durationSeconds - remainingSeconds) / max(1, cycleLength)
        let totalCycles = max(1, Int(ceil(Double(durationSeconds) / Double(max(1, cycleLength)))))
        return "Cycle \(min(completedCycles + 1, totalCycles)) of \(totalCycles)"
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
            }
            .padding(.horizontal, 20)
            .padding(.vertical, 24)
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle("Breathe")
        .navigationBarTitleDisplayMode(.inline)
        .onAppear {
            remainingSeconds = durationSeconds
            updateBreathPhase(playHaptic: false)
        }
        .onReceive(timer) { _ in
            tick()
        }
        .onChange(of: durationSeconds) { _, newValue in
            guard !isBreathing else { return }
            remainingSeconds = newValue
            didCompleteSession = false
            hasStartedSession = false
            updateBreathPhase(playHaptic: false)
        }
        .onChange(of: selectedPatternID) { _, _ in
            guard !isBreathing else { return }
            didCompleteSession = false
            hasStartedSession = false
            updateBreathPhase(playHaptic: false)
        }
        .onChange(of: scenePhase) { _, phase in
            handleScenePhase(phase)
        }
    }

    private var header: some View {
        VStack(alignment: .leading, spacing: 12) {
            Label(selectedPatternID == 0 ? "Breath of the Day" : "Breathing pattern", systemImage: "sparkles")
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
            phaseProgress: phaseProgress,
            phase: currentPhase,
            isRunning: isBreathing,
            reduceMotion: reduceMotion,
            primary: selectedTheme.primary,
            accent: selectedTheme.accent,
            phaseText: isBreathing ? currentPhase.title : timeRemainingText,
            phaseDetail: isBreathing ? "\(phaseCountdownText) · \(cycleText)" : cycleText,
            instruction: didCompleteSession ? "Complete" : breathPattern.instruction
        )
        .accessibilityElement(children: .combine)
        .accessibilityLabel(didCompleteSession ? "Breathing session complete" : "\(currentPhase.title), \(phaseCountdownText), \(cycleText), \(timeRemainingText) remaining")
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

            Picker("Breathing pattern", selection: $selectedPatternID) {
                Text("Daily: \(BreathPattern.breathOfTheDay().title)").tag(0)
                ForEach(BreathPattern.sessionPatterns) { pattern in
                    Text(pattern.title).tag(pattern.id)
                }
            }
            .pickerStyle(.menu)
            .tint(selectedTheme.primary)
            .disabled(isBreathing)

            Button {
                isBreathing ? pauseSession() : startSession()
            } label: {
                Label(sessionButtonTitle, systemImage: sessionButtonSymbol)
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .tint(selectedTheme.primary)
            .controlSize(.large)

            if hasStartedSession && !isBreathing && !didCompleteSession {
                Button("Start over") {
                    remainingSeconds = durationSeconds
                    hasStartedSession = false
                    updateBreathPhase(playHaptic: false)
                }
                .font(.subheadline.weight(.semibold))
                .foregroundStyle(selectedTheme.primary)
            }

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

    private var sessionButtonTitle: String {
        if isBreathing { return "Pause" }
        if didCompleteSession { return "Quick Repeat" }
        return hasStartedSession ? "Resume" : "Begin"
    }

    private var sessionButtonSymbol: String {
        if isBreathing { return "pause.fill" }
        if didCompleteSession { return "repeat" }
        return "play.fill"
    }

    private var breathJournalText: String {
        let moodText = lastMood.isEmpty ? "I noticed how I felt after breathing." : "I felt \(lastMood.lowercased()) after breathing."
        guard !lastComparison.isEmpty else { return moodText }
        return "\(moodText) Compared with yesterday, today felt \(lastComparison.lowercased())."
    }

    private func startSession() {
        if didCompleteSession {
            remainingSeconds = durationSeconds
            didCompleteSession = false
        }
        updateBreathPhase(playHaptic: false)
        store.breathPhase = currentPhase.title
        UIImpactFeedbackGenerator(style: .soft).impactOccurred()
        hasStartedSession = true
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
        updateBreathPhase(playHaptic: true)
    }

    private func updateBreathPhase(playHaptic: Bool) {
        let nextPhase: BreathPhase
        if cyclePosition < breathPattern.inhale {
            nextPhase = .inhale
        } else if cyclePosition < breathPattern.inhale + breathPattern.hold {
            nextPhase = .holdIn
        } else if cyclePosition < breathPattern.inhale + breathPattern.hold + breathPattern.exhale {
            nextPhase = .exhale
        } else {
            nextPhase = .holdOut
        }
        if nextPhase != currentPhase {
            currentPhase = nextPhase
            store.breathPhase = nextPhase.title
            if playHaptic {
                UIImpactFeedbackGenerator(style: .medium).impactOccurred()
            }
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
            } else {
                updateBreathPhase(playHaptic: false)
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
    let phaseProgress: Double
    let phase: BreathPhase
    let isRunning: Bool
    let reduceMotion: Bool
    let primary: Color
    let accent: Color
    let phaseText: String
    let phaseDetail: String
    let instruction: String

    private var sandProgress: Double {
        guard isRunning else { return 0.14 }
        switch phase {
        case .inhale:
            return 1 - phaseProgress
        case .holdIn:
            return 0
        case .exhale:
            return phaseProgress
        case .holdOut:
            return 1
        }
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
                        isExhaling: phase == .exhale
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
                Text(phaseDetail)
                    .font(.caption2.weight(.bold))
                    .foregroundStyle(primary.opacity(0.72))
                    .textCase(.uppercase)
                    .monospacedDigit()
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
            ZStack(alignment: .top) {
                Capsule()
                    .fill(
                        LinearGradient(
                            colors: [color.opacity(0.72), color, color.opacity(0.42)],
                            startPoint: .top,
                            endPoint: .bottom
                        )
                    )
                Capsule().fill(.white.opacity(0.58)).frame(width: 94, height: 1.5).offset(y: 1.5)
            }
            .frame(width: 104, height: 9)
            .offset(y: -69)
            ZStack(alignment: .bottom) {
                Capsule()
                    .fill(
                        LinearGradient(
                            colors: [color.opacity(0.42), color, color.opacity(0.72)],
                            startPoint: .top,
                            endPoint: .bottom
                        )
                    )
                Capsule().fill(.white.opacity(0.34)).frame(width: 94, height: 1.5).offset(y: -1.5)
            }
            .frame(width: 104, height: 9)
            .offset(y: 69)
            HourglassOutline()
                .stroke(.black.opacity(0.22), style: StrokeStyle(lineWidth: 7, lineCap: .round, lineJoin: .round))
                .padding(.horizontal, 11)
                .padding(.vertical, 9)
                .offset(y: 2)
            HourglassOutline()
                .stroke(
                    LinearGradient(
                        colors: [.white.opacity(0.78), color, color.opacity(0.38)],
                        startPoint: .topLeading,
                        endPoint: .bottomTrailing
                    ),
                    style: StrokeStyle(lineWidth: 5, lineCap: .round, lineJoin: .round)
                )
                .padding(.horizontal, 11)
                .padding(.vertical, 9)
            GlassReflection()
                .stroke(.white.opacity(0.55), style: StrokeStyle(lineWidth: 2.2, lineCap: .round))
                .padding(.horizontal, 11)
                .padding(.vertical, 9)
        }
        .shadow(color: color.opacity(0.28), radius: 8, y: 5)
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

            GeometryReader { proxy in
                let chamberHeight = proxy.size.height / 2
                let topFillHeight = max(0, chamberHeight * (1 - p))
                let bottomFillHeight = max(0, chamberHeight * p)

                ZStack {
                    HourglassChamber(isTop: true)
                        .fill(color.opacity(0.10))
                    HourglassChamber(isTop: false)
                        .fill(color.opacity(0.10))

                    HourglassChamber(isTop: true)
                        .fill(sand)
                        .shadow(color: color.opacity(0.72), radius: 8)
                        .mask {
                            VStack(spacing: 0) {
                                Rectangle().frame(height: topFillHeight)
                                Spacer(minLength: 0)
                            }
                        }
                    HourglassChamber(isTop: false)
                        .fill(sand)
                        .shadow(color: color.opacity(0.72), radius: 8)
                        .mask {
                            VStack(spacing: 0) {
                                Spacer(minLength: 0)
                                Rectangle().frame(height: bottomFillHeight)
                            }
                        }

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
        let sideInset = max(3, rect.width * 0.05)
        let topInset = max(2, rect.height * 0.025)
        let waist = max(2, rect.width * 0.025)
        if isTop {
            path.move(to: CGPoint(x: rect.minX + sideInset, y: rect.minY + topInset))
            path.addLine(to: CGPoint(x: rect.maxX - sideInset, y: rect.minY + topInset))
            path.addCurve(
                to: CGPoint(x: rect.midX + waist, y: middle),
                control1: CGPoint(x: rect.maxX - sideInset, y: middle - rect.height * 0.22),
                control2: CGPoint(x: rect.midX + waist * 2, y: middle - rect.height * 0.05)
            )
            path.addLine(to: CGPoint(x: rect.midX - waist, y: middle))
            path.addCurve(
                to: CGPoint(x: rect.minX + sideInset, y: rect.minY + topInset),
                control1: CGPoint(x: rect.midX - waist * 2, y: middle - rect.height * 0.05),
                control2: CGPoint(x: rect.minX + sideInset, y: middle - rect.height * 0.22)
            )
        } else {
            path.move(to: CGPoint(x: rect.midX - waist, y: middle))
            path.addLine(to: CGPoint(x: rect.midX + waist, y: middle))
            path.addCurve(
                to: CGPoint(x: rect.maxX - sideInset, y: rect.maxY - topInset),
                control1: CGPoint(x: rect.midX + waist * 2, y: middle + rect.height * 0.05),
                control2: CGPoint(x: rect.maxX - sideInset, y: middle + rect.height * 0.22)
            )
            path.addLine(to: CGPoint(x: rect.minX + sideInset, y: rect.maxY - topInset))
            path.addCurve(
                to: CGPoint(x: rect.midX - waist, y: middle),
                control1: CGPoint(x: rect.minX + sideInset, y: middle + rect.height * 0.22),
                control2: CGPoint(x: rect.midX - waist * 2, y: middle + rect.height * 0.05)
            )
        }
        path.closeSubpath()
        return path
    }
}

private struct GlassReflection: Shape {
    func path(in rect: CGRect) -> Path {
        var path = Path()
        let left = rect.minX + rect.width * 0.16
        let middle = rect.midY
        path.move(to: CGPoint(x: left, y: rect.minY + rect.height * 0.16))
        path.addCurve(
            to: CGPoint(x: rect.midX - rect.width * 0.035, y: middle - rect.height * 0.035),
            control1: CGPoint(x: left, y: middle - rect.height * 0.22),
            control2: CGPoint(x: rect.midX - rect.width * 0.16, y: middle - rect.height * 0.10)
        )
        path.move(to: CGPoint(x: rect.midX + rect.width * 0.04, y: middle + rect.height * 0.05))
        path.addCurve(
            to: CGPoint(x: rect.minX + rect.width * 0.19, y: rect.maxY - rect.height * 0.14),
            control1: CGPoint(x: rect.midX - rect.width * 0.08, y: middle + rect.height * 0.15),
            control2: CGPoint(x: rect.minX + rect.width * 0.19, y: middle + rect.height * 0.26)
        )
        return path
    }
}
