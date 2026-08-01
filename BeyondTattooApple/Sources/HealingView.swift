import SwiftUI

struct HealingView: View {
    @EnvironmentObject private var store: TattooStore

    var body: some View {
        TattooScreen(title: "Healing") {
            VStack(alignment: .leading, spacing: 10) {
                SectionTitle(text: "Active tattoo")
                HStack(alignment: .firstTextBaseline) {
                    Text("Day 7")
                        .font(.system(size: 46, weight: .black, design: .rounded))
                        .foregroundStyle(Color.tattooInk)
                    Spacer()
                    Label("\(store.healingPhotoCount) photos", systemImage: "camera.fill")
                        .foregroundStyle(Color.tattooGold)
                }
                ProgressView(value: 0.5)
                    .tint(Color.tattooViolet)
                Text("Peeling window. Keep photos consistent and compare texture without overreacting to normal flakes.")
                    .foregroundStyle(.secondary)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            VStack(alignment: .leading, spacing: 12) {
                SectionTitle(text: "Care timeline")
                ForEach(store.healingMilestones) { milestone in
                    HealingMilestoneRow(milestone: milestone)
                }
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
        }
    }
}

private struct HealingMilestoneRow: View {
    let milestone: HealingMilestone

    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            Image(systemName: milestone.isComplete ? "checkmark.circle.fill" : "circle")
                .foregroundStyle(milestone.isComplete ? .green : .secondary)
                .font(.title3)
            VStack(alignment: .leading, spacing: 4) {
                Text("Day \(milestone.day): \(milestone.title)")
                    .font(.headline)
                    .foregroundStyle(Color.tattooInk)
                Text(milestone.instruction)
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }
            Spacer()
        }
        .padding(.vertical, 4)
    }
}
