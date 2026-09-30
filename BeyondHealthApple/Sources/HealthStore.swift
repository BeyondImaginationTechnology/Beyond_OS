import Foundation
import SwiftUI

@MainActor
final class HealthStore: ObservableObject {
    @Published private(set) var checkIns: [CheckIn] = []
    @Published private(set) var notes: [JournalNote] = []
    @Published var errorMessage: String?

    private struct Snapshot: Codable {
        var checkIns: [CheckIn]
        var notes: [JournalNote]
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
        persist()
    }

    private func persist() {
        do {
            try FileManager.default.createDirectory(at: fileURL.deletingLastPathComponent(), withIntermediateDirectories: true, attributes: nil)
            let data = try JSONEncoder().encode(Snapshot(checkIns: checkIns, notes: notes))
            try data.write(to: fileURL, options: [.atomic, .completeFileProtectionUnlessOpen])
            errorMessage = nil
        } catch {
            errorMessage = "Your entry could not be saved on this device. Please try again."
        }
    }
}
