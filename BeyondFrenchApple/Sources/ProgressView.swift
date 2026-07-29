import SwiftUI

struct ProgressView: View {
    @EnvironmentObject private var store: AppStore
    var body: some View {
        List {
            Section {
                LabeledContent("Guest access", value: "Active")
                LabeledContent("Daily lesson", value: "Free")
                LabeledContent("Dictionary", value: "Free")
                LabeledContent("Academy", value: store.hasBeyondID ? "Full" : "Lesson 1")
            } header: { Text("Your access") }
            if !store.hasBeyondID {
                Section("Continue with Beyond ID") {
                    Text("Sign in to save streaks, lesson tests, Academy progress, and bit$ rewards.")
                    Link("Create or sign in", destination: URL(string: "https://beyondimagination.co.technology/beyond-id/auth/login.php?app=beyond-french")!)
                }
            }
        }.navigationTitle("Progress")
    }
}
