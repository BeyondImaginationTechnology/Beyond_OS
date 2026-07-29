import SwiftUI

struct PracticeView: View {
    @EnvironmentObject private var store: AppStore
    @State private var answer = ""
    @State private var result: String?

    var body: some View {
        Form {
            Section("Lesson of the Day") {
                Text(store.lesson.challenge).font(.title3.bold())
                TextField("Type your answer", text: $answer)
                    .textInputAutocapitalization(.sentences)
                Button("Check answer") {
                    result = answer.trimmingCharacters(in: .whitespacesAndNewlines)
                        .localizedCaseInsensitiveCompare(store.lesson.answer) == .orderedSame
                        ? "Correct — très bien!" : "Try again: \(store.lesson.answer)"
                }
                if let result { Text(result).font(.headline).foregroundStyle(result.hasPrefix("Correct") ? .green : .orange) }
            }
        }.navigationTitle("Practice")
    }
}
