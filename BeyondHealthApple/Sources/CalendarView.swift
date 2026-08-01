import SwiftUI

struct HealthCalendarView: View {
    @EnvironmentObject private var store: HealthStore

    private var days: [Date] {
        let calendar = Calendar.current
        return (0..<14).compactMap { offset in
            calendar.date(byAdding: .day, value: -offset, to: calendar.startOfDay(for: .now))
        }
    }

    var body: some View {
        HealthScreen(title: "Calendar") {
            FamilySwitcher()

            HealthPanel {
                HealthEyebrow(text: "Daily body calendar")
                ScrollView(.horizontal, showsIndicators: false) {
                    HStack(spacing: 10) {
                        ForEach(days, id: \.self) { day in
                            let isSelected = Calendar.current.isDate(day, inSameDayAs: store.selectedDate)
                            Button {
                                store.selectedDate = day
                            } label: {
                                VStack(spacing: 6) {
                                    Text(day.shortDayText)
                                        .font(.caption.weight(.bold))
                                    Text(day.dayNumberText)
                                        .font(.title3.weight(.black))
                                    Text("\(store.entries(on: day, memberID: store.selectedMemberID).count)")
                                        .font(.caption2.weight(.black))
                                        .foregroundStyle(isSelected ? .white : Color.healthTeal)
                                }
                                .foregroundStyle(isSelected ? .white : .secondary)
                                .frame(width: 58, height: 78)
                                .background(isSelected ? Color.healthTeal : Color.healthPanelSoft, in: RoundedRectangle(cornerRadius: 8))
                            }
                            .buttonStyle(.plain)
                        }
                    }
                }
            }

            HealthPanel {
                HealthEyebrow(text: store.selectedDate.fullDayText)
                if store.selectedDayEntries.isEmpty {
                    EmptyState(title: "Nothing logged on this date", systemImage: "calendar")
                } else {
                    ForEach(store.selectedDayEntries) { entry in
                        LogEntryRow(entry: entry)
                    }
                }
            }

            HealthPanel {
                HealthEyebrow(text: "Category totals")
                LazyVGrid(columns: [GridItem(.adaptive(minimum: 132), spacing: 10)], spacing: 10) {
                    ForEach(HealthCategory.allCases) { category in
                        HStack {
                            Image(systemName: category.systemImage)
                                .foregroundStyle(category.color)
                            Text(category.rawValue)
                                .font(.caption.weight(.bold))
                                .foregroundStyle(.white)
                            Spacer()
                            Text("\(store.categoryCount(category, memberID: store.selectedMemberID))")
                                .font(.caption.weight(.black))
                                .foregroundStyle(.secondary)
                        }
                        .padding(12)
                        .background(Color.healthPanelSoft, in: RoundedRectangle(cornerRadius: 8))
                    }
                }
            }
        }
    }
}
