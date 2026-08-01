import SwiftUI

struct InsightsView: View {
    @EnvironmentObject private var store: HealthStore

    var body: some View {
        HealthScreen(title: "Insights") {
            FamilySwitcher()

            HealthPanel {
                HealthEyebrow(text: "Across time")
                Text("\(store.selectedMember.name)'s health patterns")
                    .font(.title3.weight(.black))
                    .foregroundStyle(.white)
                Text("Early MVP insights stay simple: category totals, routine completion, and recent notes grouped by day.")
                    .foregroundStyle(.secondary)
            }

            HealthPanel {
                HealthEyebrow(text: "Log mix")
                ForEach(HealthCategory.allCases) { category in
                    let count = store.categoryCount(category, memberID: store.selectedMemberID)
                    HStack {
                        Label(category.rawValue, systemImage: category.systemImage)
                            .font(.subheadline.weight(.bold))
                            .foregroundStyle(.white)
                        Spacer()
                        Text("\(count)")
                            .font(.headline.weight(.black))
                            .foregroundStyle(category.color)
                    }
                    ProgressView(value: Double(count), total: 5)
                        .tint(category.color)
                }
            }

            HealthPanel {
                HealthEyebrow(text: "Checklist momentum")
                ProgressView(value: store.completionRatio)
                    .tint(Color.healthTeal)
                Text("\(Int(store.completionRatio * 100))% complete today")
                    .font(.headline.weight(.bold))
                    .foregroundStyle(.white)
            }
        }
    }
}
