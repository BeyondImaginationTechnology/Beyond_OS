import Foundation
import UserNotifications

enum DailyBreathNotificationService {
    private static let reminderPrefix = "dailybreath.daily-reminder"

    static let reminderMessages = [
        ("A minute for yourself?", "Open Daily Breath for today's verse, breath, or reflection."),
        ("Time for one slow breath.", "Pause for a quiet moment before the day keeps moving."),
        ("Begin again with today's verse.", "A small faithful step is enough for now."),
        ("A quiet moment is waiting.", "Read, breathe, pray, or reflect when you are ready."),
        ("Return to peace.", "Daily Breath is here for a simple reset."),
        ("One sentence. One breath.", "Make space for a gentle reflection today."),
        ("Start with stillness.", "Open Daily Breath and take the next faithful step.")
    ]

    static func authorizationStatus() async -> UNAuthorizationStatus {
        await UNUserNotificationCenter.current().notificationSettings().authorizationStatus
    }

    static func requestAuthorizationIfNeeded() async throws -> Bool {
        let center = UNUserNotificationCenter.current()
        let status = await center.notificationSettings().authorizationStatus
        switch status {
        case .authorized, .provisional, .ephemeral:
            return true
        case .denied:
            return false
        case .notDetermined:
            return try await center.requestAuthorization(options: [.alert, .badge, .sound])
        @unknown default:
            return false
        }
    }

    static func scheduleDailyReminder(hour: Int, minute: Int) async throws {
        let center = UNUserNotificationCenter.current()
        center.removePendingNotificationRequests(withIdentifiers: reminderIdentifiers)

        for weekday in 1...7 {
            let message = reminderMessages[(weekday - 1) % reminderMessages.count]
            let content = UNMutableNotificationContent()
            content.title = message.0
            content.body = message.1
            content.sound = .default
            content.threadIdentifier = "dailybreath"

            var components = DateComponents()
            components.calendar = Calendar.current
            components.weekday = weekday
            components.hour = hour
            components.minute = minute

            let trigger = UNCalendarNotificationTrigger(dateMatching: components, repeats: true)
            let request = UNNotificationRequest(
                identifier: "\(reminderPrefix).weekday-\(weekday)",
                content: content,
                trigger: trigger
            )
            try await center.add(request)
        }
    }

    static func cancelDailyReminder() {
        UNUserNotificationCenter.current().removePendingNotificationRequests(withIdentifiers: reminderIdentifiers)
    }

    private static var reminderIdentifiers: [String] {
        (1...7).map { "\(reminderPrefix).weekday-\($0)" }
    }
}
