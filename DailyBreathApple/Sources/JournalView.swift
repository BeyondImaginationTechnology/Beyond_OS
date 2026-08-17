import SwiftUI

struct JournalView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @State private var editingEntry: JournalEntry?

    private let moods = ["Peaceful", "Grateful", "Heavy", "Hopeful"]

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var weeklyEntryCount: Int {
        let calendar = Calendar.current
        let today = calendar.startOfDay(for: Date())
        return store.entries.filter { entry in
            let entryDay = calendar.startOfDay(for: entry.createdAt)
            guard let days = calendar.dateComponents([.day], from: entryDay, to: today).day else { return false }
            return days >= 0 && days < 7
        }.count
    }

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Label("\(weeklyEntryCount) reflections this week", systemImage: "calendar.badge.checkmark")
                        .font(.caption.bold())
                        .foregroundStyle(selectedTheme.accent)
                    Text(store.journalPrompt)
                        .font(.title3.weight(.bold))
                    moodPicker
                    TextEditor(text: $store.journalText)
                        .frame(minHeight: 140)
                        .scrollContentBackground(.hidden)
                        .padding(8)
                        .background(Color(.secondarySystemGroupedBackground), in: RoundedRectangle(cornerRadius: 8))
                    Button {
                        store.saveJournalEntry()
                    } label: {
                        Label("Save Reflection", systemImage: "checkmark.circle.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(selectedTheme.primary)
                    .disabled(store.journalText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                }
                .padding(.vertical, 6)
            } header: {
                Text("Reflection Prompt")
            } footer: {
                Text("Your reflections stay on this device.")
            }

            Section("Saved Reflections") {
                if store.entries.isEmpty {
                    ContentUnavailableView(
                        "No Reflections Yet",
                        systemImage: "square.and.pencil",
                        description: Text("Write one honest sentence to begin.")
                    )
                } else {
                    ForEach(store.entries) { entry in
                        Button {
                            editingEntry = entry
                        } label: {
                            VStack(alignment: .leading, spacing: 7) {
                                HStack {
                                    Text(entry.createdAt, style: .date)
                                        .font(.caption.bold())
                                        .foregroundStyle(selectedTheme.accent)
                                    if let mood = entry.mood {
                                        Text(mood)
                                            .font(.caption.bold())
                                            .foregroundStyle(selectedTheme.primary)
                                    }
                                }
                                Text(entry.prompt)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Text(entry.text)
                                    .foregroundStyle(.primary)
                                    .lineLimit(4)
                            }
                        }
                    }
                    .onDelete(perform: store.deleteJournalEntries)
                }
            }
        }
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle("Journal")
        .sheet(item: $editingEntry) { entry in
            JournalEntryEditor(entry: entry)
                .environmentObject(store)
        }
    }

    private var moodPicker: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 8) {
                ForEach(moods, id: \.self) { mood in
                    Button {
                        store.journalMood = mood
                    } label: {
                        Text(mood)
                            .font(.caption.bold())
                    }
                    .buttonStyle(.bordered)
                    .tint(store.journalMood == mood ? selectedTheme.primary : .secondary)
                }
            }
        }
    }
}

private struct JournalEntryEditor: View {
    @EnvironmentObject private var store: DailyBreathStore
    @Environment(\.dismiss) private var dismiss
    let entry: JournalEntry

    @State private var text: String
    @State private var mood: String

    private let moods = ["Peaceful", "Grateful", "Heavy", "Hopeful"]

    init(entry: JournalEntry) {
        self.entry = entry
        _text = State(initialValue: entry.text)
        _mood = State(initialValue: entry.mood ?? "Peaceful")
    }

    var body: some View {
        NavigationStack {
            List {
                Section("Prompt") {
                    Text(entry.prompt)
                }

                Section("Mood") {
                    Picker("Mood", selection: $mood) {
                        ForEach(moods, id: \.self) { mood in
                            Text(mood).tag(mood)
                        }
                    }
                    .pickerStyle(.segmented)
                }

                Section("Reflection") {
                    TextEditor(text: $text)
                        .frame(minHeight: 180)
                }
            }
            .navigationTitle("Edit Reflection")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        store.updateJournalEntry(entry, text: text, mood: mood)
                        dismiss()
                    }
                    .disabled(text.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                }
            }
        }
    }
}
