import SwiftUI

struct JournalView: View {
    @EnvironmentObject private var store: DailyBreathStore

    var body: some View {
        List {
            Section("Reflection Prompt") {
                Text("Where do I need to practice stillness today?")
                    .font(.headline)
                TextEditor(text: $store.journalText)
                    .frame(minHeight: 140)
                Button { store.saveJournalEntry() } label: {
                    Label("Save Reflection", systemImage: "checkmark.circle.fill")
                }
                .disabled(store.journalText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
            Section("Saved Reflections") {
                if store.entries.isEmpty {
                    Text("Your reflections will appear here.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(store.entries) { entry in
                        VStack(alignment: .leading, spacing: 6) {
                            Text(entry.createdAt, style: .date)
                                .font(.caption.bold())
                                .foregroundStyle(Color.dailyGold)
                            Text(entry.text)
                        }
                    }
                }
            }
        }
        .navigationTitle("Journal")
    }
}
