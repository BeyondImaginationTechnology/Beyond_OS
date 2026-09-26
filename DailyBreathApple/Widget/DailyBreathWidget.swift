import Foundation
import SwiftUI
import WidgetKit

private struct VerseWidgetEntry: TimelineEntry {
    let date: Date
    let text: String
    let reference: String
    let readingLabel: String
    let themeID: String
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
        VerseWidgetEntry(date: Date(), text: "Be still, and know that I am God.", reference: "Psalm 46:10", readingLabel: "BIBLE VERSE", themeID: "seasonal")
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
            let label = shared?.string(forKey: "widgetReadingLabel") ?? "DAILY READING"
            let themeID = shared?.string(forKey: "widgetThemeID") ?? "seasonal"
            return VerseWidgetEntry(date: date, text: text, reference: reference, readingLabel: label, themeID: themeID)
        }

        guard let url = Bundle.main.url(forResource: "daily-verses", withExtension: "json"),
              let data = try? Data(contentsOf: url),
              let verses = try? JSONDecoder().decode(WidgetVerseDocument.self, from: data).entries,
              !verses.isEmpty else {
            return VerseWidgetEntry(
                date: date,
                text: "Be still, and know that I am God.",
                reference: "Psalm 46:10",
                readingLabel: "BIBLE VERSE",
                themeID: "seasonal"
            )
        }
        let day = Calendar.current.ordinality(of: .day, in: .era, for: date) ?? 1
        let verse = verses.first(where: { $0.scheduleDate == dateKey }) ?? verses[(day - 1) % verses.count]
        let themeID = UserDefaults(suiteName: "group.technology.co.beyondimagination.thedailybreath")?.string(forKey: "widgetThemeID") ?? "seasonal"
        return VerseWidgetEntry(date: date, text: verse.text, reference: verse.reference, readingLabel: "BIBLE VERSE", themeID: themeID)
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

    private var passageIsRightToLeft: Bool {
        entry.text.unicodeScalars.contains { scalar in
            (0x0590...0x05FF).contains(scalar.value) || (0x0600...0x06FF).contains(scalar.value)
        }
    }

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
                    Label(entry.readingLabel, systemImage: "sun.max.fill")
                        .font(.caption2.bold())
                        .foregroundStyle(Color(red: 0.96, green: 0.82, blue: 0.54))
                    Text(entry.text)
                        .font(.system(family == .systemSmall ? .callout : .title3, design: .serif).weight(.semibold))
                        .foregroundStyle(.white)
                        .lineLimit(family == .systemSmall ? 5 : 4)
                    Spacer(minLength: 0)
                    Text(entry.reference)
                        .font(.caption.bold())
                        .foregroundStyle(Color(red: 0.96, green: 0.82, blue: 0.54))
                }
            }
        }
        .environment(\.layoutDirection, passageIsRightToLeft ? .rightToLeft : .leftToRight)
        .containerBackground(for: .widget) {
            themeBackground
        }
        .widgetURL(URL(string: "dailybreath://today"))
    }

    private var themeBackground: some View {
        let colors: [Color]
        switch entry.themeID {
        case "bibleForest": colors = [Color(red: 0.03, green: 0.16, blue: 0.12), Color(red: 0.12, green: 0.34, blue: 0.25)]
        case "tanakhNavy": colors = [Color(red: 0.03, green: 0.08, blue: 0.19), Color(red: 0.10, green: 0.20, blue: 0.36)]
        case "quranEmerald": colors = [Color(red: 0.02, green: 0.18, blue: 0.15), Color(red: 0.11, green: 0.38, blue: 0.29)]
        case "fall": colors = [Color(red: 0.34, green: 0.21, blue: 0.13), Color(red: 0.68, green: 0.40, blue: 0.18)]
        case "forest": colors = [Color(red: 0.12, green: 0.30, blue: 0.21), Color(red: 0.17, green: 0.41, blue: 0.29)]
        case "botanical": colors = [Color(red: 0.20, green: 0.31, blue: 0.23), Color(red: 0.56, green: 0.68, blue: 0.50)]
        case "quranMoon": colors = [Color(red: 0.025, green: 0.045, blue: 0.10), Color(red: 0.08, green: 0.31, blue: 0.34)]
        case "torahLight": colors = [Color(red: 0.18, green: 0.36, blue: 0.62), Color(red: 0.68, green: 0.82, blue: 0.96)]
        case "dawn": colors = [Color(red: 0.49, green: 0.25, blue: 0.18), Color(red: 0.85, green: 0.63, blue: 0.36)]
        case "rose": colors = [Color(red: 0.62, green: 0.16, blue: 0.35), Color(red: 0.91, green: 0.45, blue: 0.62)]
        default:
            let month = Calendar.current.component(.month, from: entry.date)
            colors = month >= 9 && month <= 11
                ? [Color(red: 0.34, green: 0.21, blue: 0.13), Color(red: 0.68, green: 0.40, blue: 0.18)]
                : [Color(red: 0.12, green: 0.30, blue: 0.21), Color(red: 0.17, green: 0.41, blue: 0.29)]
        }
        return LinearGradient(colors: colors, startPoint: .topLeading, endPoint: .bottomTrailing)
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
