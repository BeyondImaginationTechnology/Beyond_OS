import SwiftUI

struct JournalView: View {
    @EnvironmentObject private var store: HealthStore
    @State private var draft = ""
    var body: some View {
        HealthScreen(title: "Journal") {
            VStack(alignment: .leading, spacing: 6) {
                Text("Keep what mattered.").font(.largeTitle.bold()).foregroundStyle(.healthInk)
                Text("A thought, a win, a worry, or a small thing that helped.")
                    .foregroundStyle(.secondary)
            }
            HealthCard {
                Text("New note").font(.headline)
                TextEditor(text: $draft)
                    .frame(minHeight: 130)
                    .scrollContentBackground(.hidden)
                    .background(Color.healthBackground, in: RoundedRectangle(cornerRadius: 12))
                    .accessibilityLabel("Journal note")
                Button("Save note") {
                    store.addNote(draft)
                    if store.errorMessage == nil { draft = "" }
                }
                .buttonStyle(.borderedProminent)
                .tint(.healthGreen)
                .disabled(draft.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
            HealthCard {
                Text("Recent notes").font(.headline)
                if store.notes.isEmpty {
                    Text("Your private notes will appear here.").foregroundStyle(.secondary)
                } else {
                    ForEach(store.notes) { note in
                        HStack(alignment: .top, spacing: 12) {
                            VStack(alignment: .leading, spacing: 6) {
                                Text(note.text)
                                Text(note.date.formatted(date: .abbreviated, time: .shortened))
                                    .font(.caption).foregroundStyle(.secondary)
                            }
                            Spacer()
                            Button(role: .destructive) { store.deleteNote(note.id) } label: {
                                Image(systemName: "trash")
                            }
                            .accessibilityLabel("Delete note")
                        }
                        Divider()
                    }
                }
            }
        }
    }
}
