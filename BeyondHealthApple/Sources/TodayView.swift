import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: HealthStore
    @State private var selectedMood: Mood?
    @State private var energy = 3
    @State private var stress = 3
    @State private var sleep = 3
    @State private var saved = false

    private var suggestedMood: Mood? { selectedMood ?? store.latestMood }

    var body: some View {
        HealthScreen(title: "Today") {
            VStack(alignment: .leading, spacing: 7) {
                Text(Date.now.formatted(date: .complete, time: .omitted).uppercased())
                    .font(.caption.weight(.bold)).foregroundStyle(Color.healthGreen)
                Text("How are you arriving?")
                    .font(.largeTitle.bold()).foregroundStyle(Color.healthInk)
                Text("Notice where you are. One honest signal is enough.")
                    .foregroundStyle(.secondary)
            }

            HealthCard {
                Text("Daily check-in").font(.headline)
                Text("Right now, I feel").font(.subheadline).foregroundStyle(.secondary)
                FlowMoodPicker(selection: $selectedMood)
                RatingRow(title: "Energy", value: $energy)
                RatingRow(title: "Stress", value: $stress)
                RatingRow(title: "Sleep", value: $sleep)
                Button {
                    guard let selectedMood else { return }
                    store.addCheckIn(mood: selectedMood, energy: energy, stress: stress, sleep: sleep)
                    saved = store.errorMessage == nil
                } label: {
                    Text("Save today’s check-in")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(.healthGreen)
                .disabled(selectedMood == nil)
                if saved { Label("Saved on this device", systemImage: "checkmark.circle.fill").font(.footnote).foregroundStyle(.healthGreen) }
            }

            HealthCard {
                Text("A gentle next step").font(.caption.weight(.bold)).foregroundStyle(.healthGreen)
                Text(suggestedMood?.suggestion ?? "Start with a pause.")
                    .font(.title3.weight(.semibold))
                if let practice = suggestedMood?.suggestedPractice {
                    NavigationLink {
                        PracticeSessionView(practice: practice)
                    } label: {
                        Label("\(practice.rawValue) · \(practice.seconds / 60) min", systemImage: practice.symbol)
                    }
                    .buttonStyle(.bordered)
                    .tint(.healthGreen)
                } else {
                    Text("Choose a feeling to find one small thing that may help.")
                        .foregroundStyle(.secondary)
                }
            }
        }
    }
}

private struct FlowMoodPicker: View {
    @Binding var selection: Mood?
    var body: some View {
        LazyVGrid(columns: [GridItem(.adaptive(minimum: 100), spacing: 8)], spacing: 8) {
            ForEach(Mood.allCases) { mood in
                Button {
                    selection = mood
                } label: {
                    Label(mood.rawValue, systemImage: mood.symbol)
                        .font(.caption.weight(.semibold))
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 10)
                }
                .buttonStyle(.plain)
                .foregroundStyle(selection == mood ? .white : Color.healthInk)
                .background(selection == mood ? Color.healthGreen : Color.healthMint, in: Capsule())
            }
        }
    }
}

private struct RatingRow: View {
    let title: String
    @Binding var value: Int
    var body: some View {
        Stepper(value: $value, in: 1...5) {
            HStack {
                Text(title)
                Spacer()
                Text("\(value) / 5").foregroundStyle(.secondary)
            }
        }
    }
}
