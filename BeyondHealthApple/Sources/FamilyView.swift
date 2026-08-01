import SwiftUI

struct FamilyView: View {
    @EnvironmentObject private var store: HealthStore

    var body: some View {
        HealthScreen(title: "Family") {
            HealthPanel {
                HealthEyebrow(text: "Profile switcher")
                Text("Track care separately for each person while keeping one shared family timeline.")
                    .foregroundStyle(.secondary)
                FamilySwitcher()
            }

            ForEach(store.members) { member in
                HealthPanel {
                    HStack {
                        Circle()
                            .fill(member.accent.color)
                            .frame(width: 42, height: 42)
                            .overlay(
                                Text(String(member.name.prefix(1)))
                                    .font(.headline.weight(.black))
                                    .foregroundStyle(.white)
                            )
                        VStack(alignment: .leading, spacing: 2) {
                            Text(member.name)
                                .font(.headline.weight(.black))
                                .foregroundStyle(.white)
                            Text("\(member.relationship) / \(member.ageSummary)")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                        Spacer()
                        Text("\(store.entries.filter { $0.memberID == member.id }.count) logs")
                            .font(.caption.weight(.bold))
                            .foregroundStyle(Color.healthTeal)
                    }
                }
            }
        }
    }
}
