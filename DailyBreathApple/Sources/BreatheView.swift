import SwiftUI

struct BreatheView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @State private var isBreathing = false

    var body: some View {
        VStack(spacing: 26) {
            Spacer()
            Text(isBreathing ? "Breathe with peace" : "Peace Breath")
                .font(.largeTitle.weight(.black))
            Text("Inhale for four, hold for four, exhale for six. Let the verse settle before the next breath.")
                .multilineTextAlignment(.center)
                .foregroundStyle(.secondary)
                .padding(.horizontal)
            ZStack {
                Circle()
                    .fill(Color.dailyGreen.opacity(0.16))
                    .frame(width: isBreathing ? 250 : 170, height: isBreathing ? 250 : 170)
                    .animation(.easeInOut(duration: 4).repeatForever(autoreverses: true), value: isBreathing)
                Circle()
                    .stroke(Color.dailyGold, lineWidth: 4)
                    .frame(width: 170, height: 170)
                Text(store.breathPhase)
                    .font(.title2.weight(.bold))
            }
            Button {
                isBreathing.toggle()
            } label: {
                Label(isBreathing ? "Pause" : "Begin", systemImage: isBreathing ? "pause.fill" : "play.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .tint(Color.dailyGreen)
            .controlSize(.large)
            .padding(.horizontal)
            List(store.practices) { practice in
                Label {
                    VStack(alignment: .leading) {
                        Text(practice.title)
                        Text(practice.subtitle).font(.caption).foregroundStyle(.secondary)
                    }
                } icon: {
                    Image(systemName: practice.systemImage).foregroundStyle(Color.dailyGold)
                }
            }
            .frame(height: 260)
            Spacer()
        }
        .navigationTitle("Breathe")
        .navigationBarTitleDisplayMode(.inline)
    }
}
