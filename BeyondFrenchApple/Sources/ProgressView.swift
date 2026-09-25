import SwiftUI

struct LearningProgressView: View {
    @EnvironmentObject private var store: AppStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()

                ThemePicker()

                LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
                    MetricTile(title: "Difficulty Levels", value: "3", systemImage: "chart.bar.fill", color: .green)
                    MetricTile(title: "Practice", value: "\(store.correctPracticeCount)", systemImage: "bolt.fill", color: .orange)
                    MetricTile(title: "Dictionary", value: "\(store.dictionary.count)", systemImage: "character.book.closed.fill", color: .teal)
                    MetricTile(title: "Academy", value: "Free Beta", systemImage: "lock.open.fill", color: store.appTheme.accent)
                }

                VStack(alignment: .leading, spacing: 12) {
                    Text("Difficulty Progress")
                        .font(.title3.weight(.black))
                    ForEach(store.academy.ageGroups.filter { ["kids", "teen", "adult"].contains($0.slug) }) { ageGroup in
                        AgeProgressRow(ageGroup: ageGroup)
                    }
                }
                .padding(18)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))
                .overlay(RoundedRectangle(cornerRadius: 22).stroke(store.appTheme.accent.opacity(0.18), lineWidth: 1))

            }
            .padding()
        }
        .background(store.appTheme.appBackground)
        .navigationTitle("Progress")
    }
}

private struct AgeProgressRow: View {
    @EnvironmentObject private var store: AppStore
    let ageGroup: AgeGroup

    private var completed: Int {
        store.completedAcademyLessons(ageGroup: ageGroup)
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(ageGroup.slug == "kids" ? "Beginner" : ageGroup.slug == "teen" ? "Intermediate" : "Advanced")
                    .font(.subheadline.weight(.bold))
                Spacer()
                Text("\(completed)/\(store.totalAcademyLessons)")
                    .font(.caption.weight(.bold))
                    .foregroundStyle(.secondary)
            }
            SwiftUI.ProgressView(value: Double(completed), total: Double(max(store.totalAcademyLessons, 1)))
                .tint(store.appTheme.accent)
        }
    }
}
