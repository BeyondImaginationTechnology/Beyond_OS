import SwiftUI

struct SettingsView: View {
    @AppStorage("dailyReminderEnabled") private var dailyReminderEnabled = false
    @AppStorage("useSimpleLanguage") private var useSimpleLanguage = false

    var body: some View {
        ZStack {
            SpaceBackground()
            Form {
                Section("Daily Space") {
                    Toggle("Daily reminder", isOn: $dailyReminderEnabled)
                    Text("Reminder authorization and scheduling will be connected in the next app milestone.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
                Section("Reading") {
                    Toggle("Prefer simpler language", isOn: $useSimpleLanguage)
                    Text("Beyond Space follows your iPhone text size, contrast, VoiceOver, and Reduce Motion settings.")
                        .font(.footnote)
                }
                Section("About") {
                    LabeledContent("Version", value: "0.1.0")
                    Link("Beyond Space on Instagram", destination: URL(string: "https://www.instagram.com/beyondspaceapp/")!)
                        .frame(minHeight: 44)
                }
            }
            .scrollContentBackground(.hidden)
        }
        .navigationTitle("Settings & Access")
    }
}
