import SwiftUI
import Combine

struct PracticesView: View {
    var body: some View {
        HealthScreen(title: "Practices") {
            VStack(alignment: .leading, spacing: 6) {
                Text("Meet the moment.").font(.largeTitle.bold()).foregroundStyle(.healthInk)
                Text("Short enough to begin. Stop whenever you need.")
                    .foregroundStyle(.secondary)
            }
            ForEach(PracticeKind.allCases) { practice in
                HealthCard {
                    Label(practice.rawValue, systemImage: practice.symbol)
                        .font(.headline).foregroundStyle(.healthInk)
                    Text(practice.detail).foregroundStyle(.secondary)
                    NavigationLink("Begin · \(practice.seconds / 60) min") {
                        PracticeSessionView(practice: practice)
                    }
                    .buttonStyle(.bordered)
                    .tint(.healthGreen)
                }
            }
        }
    }
}

struct PracticeSessionView: View {
    let practice: PracticeKind
    @State private var remaining = 0
    @State private var running = false
    private let ticker = Timer.publish(every: 1, on: .main, in: .common).autoconnect()

    var body: some View {
        HealthScreen(title: practice.rawValue) {
            HealthCard {
                Image(systemName: practice.symbol)
                    .font(.largeTitle).foregroundStyle(.healthGreen)
                Text(practice.detail).font(.title3)
                Text("\(remaining / 60):\(String(format: "%02d", remaining % 60))")
                    .font(.system(size: 58, weight: .bold, design: .rounded))
                    .monospacedDigit()
                    .foregroundStyle(.healthInk)
                    .frame(maxWidth: .infinity)
                HStack {
                    Button(running ? "Pause" : remaining == 0 ? "Restart" : "Start") {
                        if remaining == 0 { remaining = practice.seconds }
                        running.toggle()
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(.healthGreen)
                    Button("Reset") {
                        running = false
                        remaining = practice.seconds
                    }
                    .buttonStyle(.bordered)
                }
            }
        }
        .onAppear { remaining = practice.seconds }
        .onDisappear { running = false }
        .onReceive(ticker) { _ in
            guard running else { return }
            if remaining > 0 { remaining -= 1 }
            if remaining == 0 { running = false }
        }
    }
}
