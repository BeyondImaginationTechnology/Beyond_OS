import SwiftUI

struct AcademyView: View {
    @EnvironmentObject private var store: DailyBreathStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                Text("Bible Academy")
                    .font(.largeTitle.weight(.black))
                Text("Guided Bible learning for teens and adults, with narrated lessons, checks, and saved progress.")
                    .foregroundStyle(.secondary)
                ForEach(store.modules) { module in
                    ModuleCard(module: module)
                }
            }
            .padding()
        }
        .background(Color(.systemGroupedBackground))
        .navigationTitle("Academy")
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct ModuleCard: View {
    let module: AcademyModule

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Text(module.title)
                    .font(.title3.weight(.bold))
                Spacer()
                if module.isFree {
                    Text("FREE")
                        .font(.caption.bold())
                        .padding(.horizontal, 9)
                        .padding(.vertical, 5)
                        .background(.green.opacity(0.12), in: Capsule())
                        .foregroundStyle(.green)
                }
            }
            Text(module.subtitle)
                .foregroundStyle(.secondary)
            ProgressView(value: module.progress)
                .tint(Color.dailyGold)
            Text("\(Int(module.progress * 100))% complete")
                .font(.caption.bold())
                .foregroundStyle(.secondary)
        }
        .padding()
        .background(.background, in: RoundedRectangle(cornerRadius: 20))
    }
}
