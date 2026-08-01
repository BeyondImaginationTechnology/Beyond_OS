import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: HealthStore

    var body: some View {
        HealthScreen(title: "Today") {
            FamilySwitcher()

            HealthPanel {
                HealthEyebrow(text: store.selectedMember.name)
                Text(Date.now.fullDayText)
                    .font(.title2.weight(.black))
                    .foregroundStyle(.white)
                Text("A daily body calendar for food, sleep, meds, smoke sessions, care routines, and anything worth remembering.")
                    .foregroundStyle(.secondary)
                HStack(spacing: 10) {
                    StatTile(title: "Entries", value: "\(store.selectedDayEntries.count)")
                    StatTile(title: "Checklist", value: "\(Int(store.completionRatio * 100))%")
                }
            }

            if let workout = store.recommendedWorkout {
                WorkoutCard(workout: workout)
            }

            HealthPanel {
                HealthEyebrow(text: "Reminders")
                ForEach(store.selectedDayRoutineItems) { item in
                    Button {
                        store.toggleRoutine(item)
                    } label: {
                        HStack(spacing: 12) {
                            Image(systemName: item.isComplete ? "checkmark.circle.fill" : "circle")
                                .font(.title3)
                                .foregroundStyle(item.isComplete ? Color.healthTeal : .secondary)
                            VStack(alignment: .leading, spacing: 2) {
                                Text(item.title)
                                    .font(.subheadline.weight(.bold))
                                    .foregroundStyle(.white)
                                Text(item.dueTime)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            Spacer()
                            CategoryPill(category: item.category)
                        }
                    }
                    .buttonStyle(.plain)
                }
            }

            HealthPanel {
                HealthEyebrow(text: "Timeline")
                if store.selectedDayEntries.isEmpty {
                    EmptyState(title: "No entries for this day yet", systemImage: "calendar.badge.plus")
                } else {
                    ForEach(store.selectedDayEntries) { entry in
                        LogEntryRow(entry: entry)
                    }
                }
            }
        }
    }
}

struct WorkoutCard: View {
    let workout: WorkoutRecommendation

    var body: some View {
        HealthPanel {
            HStack {
                VStack(alignment: .leading, spacing: 4) {
                    HealthEyebrow(text: "Recommended workout")
                    Text(workout.title)
                        .font(.title3.weight(.black))
                        .foregroundStyle(.white)
                    Text(workout.reason)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }
                Spacer()
                VStack {
                    Text("\(workout.durationMinutes)")
                        .font(.title.weight(.black))
                        .foregroundStyle(Color.healthTeal)
                    Text("min")
                        .font(.caption.weight(.bold))
                        .foregroundStyle(.secondary)
                }
            }
            Text(workout.moves.joined(separator: " / "))
                .font(.footnote.weight(.semibold))
                .foregroundStyle(.white)
        }
    }
}

struct LogEntryRow: View {
    let entry: HealthLogEntry

    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            Image(systemName: entry.category.systemImage)
                .frame(width: 34, height: 34)
                .foregroundStyle(entry.category.color)
                .background(entry.category.color.opacity(0.14), in: RoundedRectangle(cornerRadius: 8))
            VStack(alignment: .leading, spacing: 4) {
                HStack {
                    Text(entry.title)
                        .font(.subheadline.weight(.bold))
                        .foregroundStyle(.white)
                    Spacer()
                    Text(entry.date.timeText)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Text(entry.detail)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                if let attachmentLabel = entry.attachmentLabel {
                    Label(attachmentLabel, systemImage: "photo.fill")
                        .font(.caption.weight(.bold))
                        .foregroundStyle(Color.healthGold)
                }
            }
        }
    }
}

struct StatTile: View {
    let title: String
    let value: String

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title.uppercased())
                .font(.caption2.weight(.black))
                .foregroundStyle(.secondary)
            Text(value)
                .font(.headline.weight(.black))
                .foregroundStyle(.white)
        }
        .padding(14)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.healthPanelSoft, in: RoundedRectangle(cornerRadius: 8))
    }
}
