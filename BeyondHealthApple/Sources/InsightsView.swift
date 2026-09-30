import SwiftUI

struct InsightsView: View {
    @EnvironmentObject private var store: HealthStore
    var body: some View {
        HealthScreen(title: "Insights") {
            VStack(alignment: .leading, spacing: 6) {
                Text("Your rhythm, in context.").font(.largeTitle.bold()).foregroundStyle(.healthInk)
                Text("Personal reflections, never a diagnosis.").foregroundStyle(.secondary)
            }
            HealthCard {
                Text("This week").font(.caption.weight(.bold)).foregroundStyle(.healthGreen)
                if store.recentCheckIns.isEmpty {
                    Text("Your rhythm starts here.").font(.title3.weight(.semibold))
                    Text("Check in a few times to see your own patterns.")
                        .foregroundStyle(.secondary)
                } else {
                    Text("\(store.recentCheckIns.count) check-in\(store.recentCheckIns.count == 1 ? "" : "s") this week")
                        .font(.title3.weight(.semibold))
                    Text("Average energy: \(average(\.energy)) / 5")
                    Text("Average stress: \(average(\.stress)) / 5")
                    Text("Average sleep: \(average(\.sleep)) / 5")
                    Text("Notice what helped on your steadier days.")
                        .font(.footnote).foregroundStyle(.secondary)
                }
            }
            HealthCard {
                Text("Recent check-ins").font(.headline)
                if store.checkIns.isEmpty {
                    Text("No check-ins yet. Begin with Today.").foregroundStyle(.secondary)
                } else {
                    ForEach(store.checkIns.prefix(12)) { item in
                        HStack {
                            Label(item.mood.rawValue, systemImage: item.mood.symbol)
                            Spacer()
                            Text(item.date.formatted(date: .abbreviated, time: .omitted))
                                .font(.caption).foregroundStyle(.secondary)
                        }
                        Divider()
                    }
                }
            }
        }
    }

    private func average(_ keyPath: KeyPath<CheckIn, Int>) -> Int {
        let items = store.recentCheckIns
        guard !items.isEmpty else { return 0 }
        return Int((Double(items.reduce(0) { $0 + $1[keyPath: keyPath] }) / Double(items.count)).rounded())
    }
}
