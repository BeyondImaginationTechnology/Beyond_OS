import Foundation
import SwiftUI
import WidgetKit

private struct VerseWidgetEntry: TimelineEntry {
    let date: Date
    let text: String
    let reference: String
}

private struct WidgetVerseDocument: Decodable {
    let entries: [WidgetVerseItem]
}

private struct WidgetVerseItem: Decodable {
    let text: String
    let reference: String
    let scheduleDate: String?

    enum CodingKeys: String, CodingKey {
        case text, reference
        case scheduleDate = "schedule_date"
    }
}

private struct VerseWidgetProvider: TimelineProvider {
    func placeholder(in context: Context) -> VerseWidgetEntry {
        VerseWidgetEntry(date: Date(), text: "Be still, and know that I am God.", reference: "Psalm 46:10")
    }

    func getSnapshot(in context: Context, completion: @escaping @Sendable (VerseWidgetEntry) -> Void) {
        completion(entry(for: Date()))
    }

    func getTimeline(in context: Context, completion: @escaping @Sendable (Timeline<VerseWidgetEntry>) -> Void) {
        let now = Date()
        let nextDay = Calendar.current.nextDate(
            after: now,
            matching: DateComponents(hour: 0, minute: 1),
            matchingPolicy: .nextTime
        ) ?? now.addingTimeInterval(86_400)
        completion(Timeline(entries: [entry(for: now)], policy: .after(nextDay)))
    }

    private func entry(for date: Date) -> VerseWidgetEntry {
        let dateKey = Self.dateKey(date)
        let shared = UserDefaults(suiteName: "group.technology.co.beyondimagination.thedailybreath")
        if shared?.string(forKey: "widgetVerseDate") == dateKey,
           let text = shared?.string(forKey: "widgetVerseText"),
           let reference = shared?.string(forKey: "widgetVerseReference") {
            return VerseWidgetEntry(date: date, text: text, reference: reference)
        }

        guard let url = Bundle.main.url(forResource: "daily-verses", withExtension: "json"),
              let data = try? Data(contentsOf: url),
              let verses = try? JSONDecoder().decode(WidgetVerseDocument.self, from: data).entries,
              !verses.isEmpty else {
            return VerseWidgetEntry(
                date: date,
                text: "Be still, and know that I am God.",
                reference: "Psalm 46:10"
            )
        }
        let day = Calendar.current.ordinality(of: .day, in: .era, for: date) ?? 1
        let verse = verses.first(where: { $0.scheduleDate == dateKey }) ?? verses[(day - 1) % verses.count]
        return VerseWidgetEntry(date: date, text: verse.text, reference: verse.reference)
    }

    private static func dateKey(_ date: Date) -> String {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = .current
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter.string(from: date)
    }
}

private struct VerseWidgetView: View {
    @Environment(\.widgetFamily) private var family
    let entry: VerseWidgetEntry

    var body: some View {
        Group {
            switch family {
            case .accessoryInline:
                Text("\(entry.reference): \(entry.text)")
            case .accessoryRectangular:
                VStack(alignment: .leading, spacing: 3) {
                    Text(entry.reference).font(.headline)
                    Text(entry.text).font(.caption).lineLimit(2)
                }
            default:
                VStack(alignment: .leading, spacing: 8) {
                    Label("VERSE OF THE DAY", systemImage: "sun.max.fill")
                        .font(.caption2.bold())
                        .foregroundStyle(.secondary)
                    Text(entry.text)
                        .font(.system(family == .systemSmall ? .callout : .title3, design: .serif).weight(.semibold))
                        .lineLimit(family == .systemSmall ? 5 : 4)
                    Spacer(minLength: 0)
                    Text(entry.reference).font(.caption.bold())
                }
            }
        }
        .containerBackground(for: .widget) {
            LinearGradient(
                colors: [Color(red: 0.96, green: 0.93, blue: 0.86), Color(red: 0.76, green: 0.84, blue: 0.72)],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
        }
        .widgetURL(URL(string: "dailybreath://today"))
    }
}

struct DailyBreathVerseWidget: Widget {
    let kind = "DailyBreathVerseWidget"

    var body: some WidgetConfiguration {
        StaticConfiguration(kind: kind, provider: VerseWidgetProvider()) { entry in
            VerseWidgetView(entry: entry)
        }
        .configurationDisplayName("Verse of the Day")
        .description("Carry today’s Daily Breath verse on your Home Screen or Lock Screen.")
        .supportedFamilies([.systemSmall, .systemMedium, .accessoryInline, .accessoryRectangular])
    }
}

@main
struct DailyBreathWidgetBundle: WidgetBundle {
    var body: some Widget {
        DailyBreathVerseWidget()
    }
}
