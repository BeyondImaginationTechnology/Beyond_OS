import Foundation

@MainActor
final class HealthStore: ObservableObject {
    @Published private(set) var members = FamilyMember.seed
    @Published private(set) var entries = HealthSeed.entries
    @Published private(set) var routines = HealthSeed.routines
    @Published private(set) var workouts = HealthSeed.workouts
    @Published var selectedMemberID = FamilyMember.seed[0].id
    @Published var selectedDate = Calendar.current.startOfDay(for: .now)

    var selectedMember: FamilyMember {
        members.first { $0.id == selectedMemberID } ?? members[0]
    }

    var selectedDayEntries: [HealthLogEntry] {
        entries(on: selectedDate, memberID: selectedMemberID)
    }

    var selectedDayRoutineItems: [RoutineItem] {
        routines.filter { $0.memberID == selectedMemberID }
    }

    var recommendedWorkout: WorkoutRecommendation? {
        workouts.first { $0.memberID == selectedMemberID } ?? workouts.first
    }

    var completionRatio: Double {
        guard !selectedDayRoutineItems.isEmpty else { return 0 }
        let completeCount = selectedDayRoutineItems.filter(\.isComplete).count
        return Double(completeCount) / Double(selectedDayRoutineItems.count)
    }

    func entries(on date: Date, memberID: String) -> [HealthLogEntry] {
        entries
            .filter { $0.memberID == memberID && Calendar.current.isDate($0.date, inSameDayAs: date) }
            .sorted { $0.date > $1.date }
    }

    func categoryCount(_ category: HealthCategory, memberID: String) -> Int {
        entries.filter { $0.memberID == memberID && $0.category == category }.count
    }

    func addEntry(category: HealthCategory, title: String, detail: String, attachmentLabel: String? = nil, date: Date = .now) {
        let cleanTitle = title.trimmingCharacters(in: .whitespacesAndNewlines)
        let cleanDetail = detail.trimmingCharacters(in: .whitespacesAndNewlines)
        let entry = HealthLogEntry(
            memberID: selectedMemberID,
            date: date,
            category: category,
            title: cleanTitle.isEmpty ? category.rawValue : cleanTitle,
            detail: cleanDetail.isEmpty ? "Logged from Today" : cleanDetail,
            attachmentLabel: attachmentLabel
        )
        entries.insert(entry, at: 0)
        selectedDate = Calendar.current.startOfDay(for: date)
    }

    func toggleRoutine(_ item: RoutineItem) {
        guard let index = routines.firstIndex(where: { $0.id == item.id }) else { return }
        routines[index].isComplete.toggle()
    }
}

enum HealthSeed {
    static let now = Date.now

    static let entries: [HealthLogEntry] = [
        HealthLogEntry(memberID: "mia", date: now.addingTimeInterval(-900), category: .food, title: "Breakfast bowl", detail: "Greek yogurt, berries, chia, black coffee.", attachmentLabel: "Food photo"),
        HealthLogEntry(memberID: "mia", date: now.addingTimeInterval(-3600), category: .sleep, title: "Woke at 6:42 AM", detail: "Dream journal: ocean house, calm but vivid."),
        HealthLogEntry(memberID: "mia", date: now.addingTimeInterval(-7200), category: .medication, title: "Morning supplements", detail: "Vitamin D, magnesium, probiotic."),
        HealthLogEntry(memberID: "zak", date: now.addingTimeInterval(-1800), category: .body, title: "Low energy note", detail: "Said stomach felt tight after lunch."),
        HealthLogEntry(memberID: "nana", date: now.addingTimeInterval(-5400), category: .medication, title: "Blood pressure pill", detail: "Taken with breakfast.")
    ]

    static let routines: [RoutineItem] = [
        RoutineItem(id: "mia-water", memberID: "mia", title: "Water bottle refill", category: .body, dueTime: "10:00 AM", isComplete: true),
        RoutineItem(id: "mia-walk", memberID: "mia", title: "20 minute walk", category: .workout, dueTime: "1:00 PM", isComplete: false),
        RoutineItem(id: "mia-skincare", memberID: "mia", title: "Evening skin care", category: .hygiene, dueTime: "9:30 PM", isComplete: false),
        RoutineItem(id: "zak-vitamins", memberID: "zak", title: "Kids vitamin", category: .medication, dueTime: "8:00 AM", isComplete: true),
        RoutineItem(id: "zak-brush", memberID: "zak", title: "Brush teeth", category: .hygiene, dueTime: "8:30 PM", isComplete: false),
        RoutineItem(id: "nana-meds", memberID: "nana", title: "Evening medication", category: .medication, dueTime: "7:00 PM", isComplete: false)
    ]

    static let workouts: [WorkoutRecommendation] = [
        WorkoutRecommendation(id: "mia-mobility", memberID: "mia", title: "Low-impact reset", durationMinutes: 18, intensity: "Gentle", reason: "Light movement after a short sleep night.", moves: ["Cat-cow", "Wall pushups", "Hip circles", "Easy walk"]),
        WorkoutRecommendation(id: "zak-play", memberID: "zak", title: "Playground cardio", durationMinutes: 20, intensity: "Play", reason: "Energy outlet with simple bodyweight movement.", moves: ["Jumping jacks", "Bear crawl", "Balance walk"]),
        WorkoutRecommendation(id: "nana-chair", memberID: "nana", title: "Chair mobility", durationMinutes: 12, intensity: "Easy", reason: "Joint-friendly movement and circulation.", moves: ["Seated march", "Ankle circles", "Shoulder rolls"])
    ]
}
