import Foundation
import SwiftUI

@MainActor
final class HealthStore: ObservableObject {
    @Published private(set) var checkIns: [CheckIn] = []
    @Published private(set) var notes: [JournalNote] = []
    @Published private(set) var mealPlan: [String: String] = [:]
    @Published private(set) var prepMinutes: [String: Int] = [:]
    @Published var errorMessage: String?

    private struct Snapshot: Codable {
        var checkIns: [CheckIn]
        var notes: [JournalNote]
        var mealPlan: [String: String]?
        var prepMinutes: [String: Int]?
    }

    private let fileURL: URL

    init() {
        let directory = FileManager.default.urls(for: .applicationSupportDirectory, in: .userDomainMask)[0]
            .appendingPathComponent("BeyondHealth", isDirectory: true)
        fileURL = directory.appendingPathComponent("wellbeing-v0.0.1.json")
        guard let data = try? Data(contentsOf: fileURL),
              let snapshot = try? JSONDecoder().decode(Snapshot.self, from: data) else { return }
        checkIns = snapshot.checkIns.sorted { $0.date > $1.date }
        notes = snapshot.notes.sorted { $0.date > $1.date }
        mealPlan = snapshot.mealPlan ?? [:]
        prepMinutes = snapshot.prepMinutes ?? [:]
    }

    var latestMood: Mood? { checkIns.first?.mood }

    var recentCheckIns: [CheckIn] {
        let weekAgo = Calendar.current.date(byAdding: .day, value: -7, to: .now) ?? .distantPast
        return checkIns.filter { $0.date >= weekAgo }
    }

    func addCheckIn(mood: Mood, energy: Int, stress: Int, sleep: Int) {
        checkIns.insert(CheckIn(date: .now, mood: mood, energy: energy, stress: stress, sleep: sleep), at: 0)
        persist()
    }

    func addNote(_ text: String) {
        let clean = text.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !clean.isEmpty else { return }
        notes.insert(JournalNote(date: .now, text: clean), at: 0)
        persist()
    }

    func deleteNote(_ id: UUID) {
        notes.removeAll { $0.id == id }
        persist()
    }

    func clearAll() {
        checkIns = []
        notes = []
        mealPlan = [:]
        prepMinutes = [:]
        persist()
    }

    static func dayKey(_ date: Date) -> String {
        let parts = Calendar.current.dateComponents([.year, .month, .day], from: date)
        return String(format: "%04d-%02d-%02d", parts.year ?? 0, parts.month ?? 0, parts.day ?? 0)
    }

    func recipeID(on date: Date, slot: MealSlot) -> String {
        mealPlan["\(Self.dayKey(date))|\(slot.rawValue)"] ?? ""
    }

    func setMeal(on date: Date, slot: MealSlot, recipeID: String) {
        let key = "\(Self.dayKey(date))|\(slot.rawValue)"
        if recipeID.isEmpty { mealPlan.removeValue(forKey: key) }
        else { mealPlan[key] = recipeID }
        persist()
    }

    func prepTime(on date: Date) -> Int {
        prepMinutes[Self.dayKey(date)] ?? 15
    }

    func setPrepTime(_ minutes: Int, on date: Date) {
        prepMinutes[Self.dayKey(date)] = minutes
        persist()
    }

    private func persist() {
        do {
            try FileManager.default.createDirectory(at: fileURL.deletingLastPathComponent(), withIntermediateDirectories: true, attributes: nil)
            let data = try JSONEncoder().encode(Snapshot(checkIns: checkIns, notes: notes, mealPlan: mealPlan, prepMinutes: prepMinutes))
            try data.write(to: fileURL, options: [.atomic, .completeFileProtectionUnlessOpen])
            errorMessage = nil
        } catch {
            errorMessage = "Your entry could not be saved on this device. Please try again."
        }
    }
}
