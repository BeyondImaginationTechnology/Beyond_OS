import SwiftUI

struct LearningProgressView: View {
    @EnvironmentObject private var store: AppStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()

                ThemePicker()

                LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
                    MetricTile(title: "Age Paths", value: "\(store.academy.ageGroups.count)", systemImage: "person.3.fill", color: .green)
                    MetricTile(title: "Practice", value: "\(store.correctPracticeCount)", systemImage: "bolt.fill", color: .orange)
                    MetricTile(title: "Dictionary", value: "\(store.dictionary.count)", systemImage: "character.book.closed.fill", color: .teal)
                    MetricTile(title: "Academy", value: store.hasFullAcademyAccess ? "Full" : "Greetings", systemImage: "lock.shield.fill", color: store.appTheme.accent)
                }

                VStack(alignment: .leading, spacing: 12) {
                    Text("Age Path Progress")
                        .font(.title3.weight(.black))
                    ForEach(store.academy.ageGroups) { ageGroup in
                        AgeProgressRow(ageGroup: ageGroup)
                    }
                }
                .padding(18)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))
                .overlay(RoundedRectangle(cornerRadius: 22).stroke(store.appTheme.accent.opacity(0.18), lineWidth: 1))

                if !store.hasBeyondID {
                    VStack(alignment: .leading, spacing: 12) {
                        Label("Continue with Beyond ID", systemImage: "lock.open.fill")
                            .font(.headline)
                        Text("Sign in to save cloud progress, lesson tests, Academy exams, and bit$ rewards.")
                            .foregroundStyle(.secondary)
                        Link(destination: URL(string: "https://beyondimagination.co.technology/beyond-id/auth/login.php?app=beyond-french")!) {
                            Label("Create or sign in", systemImage: "person.crop.circle.fill.badge.plus")
                                .frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.borderedProminent)
                        .controlSize(.large)
                    }
                    .padding(18)
                    .background(store.appTheme.accent.opacity(0.08), in: RoundedRectangle(cornerRadius: 22))
                }
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
                Text("\(ageGroup.title) · \(ageGroup.ages)")
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
