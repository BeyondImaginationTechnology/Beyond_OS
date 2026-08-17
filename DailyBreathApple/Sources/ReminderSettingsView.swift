import SwiftUI
import UIKit
import UserNotifications

struct ReminderSettingsView: View {
    @Environment(\.openURL) private var openURL
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("dailyReminderEnabled") private var reminderEnabled = false
    @AppStorage("dailyReminderHour") private var reminderHour = 8
    @AppStorage("dailyReminderMinute") private var reminderMinute = 0

    @State private var authorizationStatus: UNAuthorizationStatus = .notDetermined
    @State private var statusMessage = ""
    @State private var isUpdating = false

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var reminderTime: Binding<Date> {
        Binding {
            Calendar.current.date(from: DateComponents(hour: reminderHour, minute: reminderMinute)) ?? Date()
        } set: { newValue in
            let components = Calendar.current.dateComponents([.hour, .minute], from: newValue)
            reminderHour = components.hour ?? 8
            reminderMinute = components.minute ?? 0
            guard reminderEnabled else { return }
            Task {
                await scheduleReminder()
            }
        }
    }

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Image(systemName: "bell.badge.fill")
                        .font(.largeTitle)
                        .foregroundStyle(selectedTheme.accent)
                    Text("Daily Reminder")
                        .font(.largeTitle.weight(.black))
                    Text("Choose a gentle daily nudge for Scripture, breath, prayer, or reflection.")
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }

            Section {
                Toggle("Enable Daily Reminder", isOn: $reminderEnabled)
                    .disabled(isUpdating)
                DatePicker("Reminder Time", selection: reminderTime, displayedComponents: .hourAndMinute)
                    .disabled(!reminderEnabled || isUpdating)
            } footer: {
                Text("Daily Breath asks for notification permission only when you enable reminders.")
            }

            Section("Reminder Copy Rotation") {
                ForEach(DailyBreathNotificationService.reminderMessages, id: \.0) { message in
                    VStack(alignment: .leading, spacing: 4) {
                        Text(message.0)
                            .font(.subheadline.weight(.semibold))
                        Text(message.1)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                }
            }

            if authorizationStatus == .denied {
                Section {
                    Button {
                        if let url = URL(string: UIApplication.openSettingsURLString) {
                            openURL(url)
                        }
                    } label: {
                        Label("Open Notification Settings", systemImage: "gear")
                    }
                } footer: {
                    Text("Notifications are disabled for Daily Breath in iOS Settings.")
                }
            }

            if !statusMessage.isEmpty {
                Section {
                    Text(statusMessage)
                        .foregroundStyle(.secondary)
                }
            }
        }
        .navigationTitle("Reminders")
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .tint(selectedTheme.primary)
        .task {
            authorizationStatus = await DailyBreathNotificationService.authorizationStatus()
        }
        .onChange(of: reminderEnabled) { _, isEnabled in
            Task {
                if isEnabled {
                    await scheduleReminder()
                } else {
                    DailyBreathNotificationService.cancelDailyReminder()
                    statusMessage = "Daily reminders are off."
                }
            }
        }
    }

    private func scheduleReminder() async {
        isUpdating = true
        defer { isUpdating = false }

        do {
            let allowed = try await DailyBreathNotificationService.requestAuthorizationIfNeeded()
            authorizationStatus = await DailyBreathNotificationService.authorizationStatus()
            guard allowed else {
                reminderEnabled = false
                statusMessage = "Notification permission was not granted."
                return
            }

            try await DailyBreathNotificationService.scheduleDailyReminder(hour: reminderHour, minute: reminderMinute)
            statusMessage = "Daily reminder scheduled for \(formattedReminderTime)."
        } catch {
            reminderEnabled = false
            statusMessage = "Daily reminder could not be scheduled."
        }
    }

    private var formattedReminderTime: String {
        let date = Calendar.current.date(from: DateComponents(hour: reminderHour, minute: reminderMinute)) ?? Date()
        return date.formatted(date: .omitted, time: .shortened)
    }
}
