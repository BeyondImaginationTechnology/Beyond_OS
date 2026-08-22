import Foundation
import StoreKit
import SwiftUI

@main
struct DailyBreathClipApp: App {
    var body: some Scene {
        WindowGroup {
            DailyBreathClipView()
        }
    }
}

private struct DailyBreathClipView: View {
    @State private var showFullAppOverlay = false
    private let verse = ClipVerse.ofTheDay()

    var body: some View {
        VStack(spacing: 22) {
            Spacer()

            Image(systemName: "sun.max.fill")
                .font(.system(size: 52, weight: .bold))
                .foregroundStyle(Color.clipGold)

            VStack(spacing: 10) {
                Text("The Daily Breath")
                    .font(.largeTitle.weight(.black))
                    .multilineTextAlignment(.center)
                Text("Pause for one verse, one breath, and one faithful next step.")
                    .font(.title3)
                    .foregroundStyle(.secondary)
                    .multilineTextAlignment(.center)
            }

            VStack(alignment: .leading, spacing: 14) {
                Text(verse.text)
                    .font(.system(.title2, design: .serif).weight(.semibold))
                Text(verse.reference)
                    .font(.headline.weight(.black))
                    .foregroundStyle(Color.clipGreen)
                Divider()
                Text("Begin slowly. Make room for quiet, notice your breath, and let the next faithful step be enough for today.")
                    .foregroundStyle(.secondary)
            }
            .padding(20)
            .background(.background, in: RoundedRectangle(cornerRadius: 22))

            Button {
                showFullAppOverlay = true
            } label: {
                Label("Get the Full App", systemImage: "arrow.down.app.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .tint(Color.clipGreen)
            .controlSize(.large)

            Spacer()
        }
        .padding()
        .background(ClipThemeBackground())
        .appStoreOverlay(isPresented: $showFullAppOverlay) {
            SKOverlay.AppClipConfiguration(position: .bottom)
        }
    }
}

private struct ClipVerse {
    let text: String
    let reference: String

    private struct Document: Decodable { let entries: [Entry] }
    private struct Entry: Decodable {
        let text: String
        let reference: String
        let scheduleDate: String?

        enum CodingKeys: String, CodingKey {
            case text, reference
            case scheduleDate = "schedule_date"
        }
    }

    static func ofTheDay(for date: Date = Date(), bundle: Bundle = .main) -> ClipVerse {
        guard
            let url = bundle.url(forResource: "daily-verses", withExtension: "json"),
            let data = try? Data(contentsOf: url),
            let entries = try? JSONDecoder().decode(Document.self, from: data).entries,
            !entries.isEmpty
        else {
            return ClipVerse(text: "Be still, and know that I am God.", reference: "Psalm 46:10")
        }

        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = .current
        formatter.dateFormat = "yyyy-MM-dd"
        let dateKey = formatter.string(from: date)
        let day = Calendar.current.ordinality(of: .day, in: .era, for: date) ?? 1
        let entry = entries.first(where: { $0.scheduleDate == dateKey }) ?? entries[(day - 1) % entries.count]
        return ClipVerse(text: entry.text, reference: entry.reference)
    }
}

private struct ClipThemeBackground: View {
    var body: some View {
        ZStack {
            Color(red: 0.96, green: 0.93, blue: 0.86)
                .ignoresSafeArea()
            LinearGradient(
                colors: [
                    Color(red: 0.96, green: 0.93, blue: 0.86),
                    Color(red: 0.56, green: 0.68, blue: 0.50).opacity(0.20),
                    Color.clipGreen.opacity(0.12)
                ],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
            .ignoresSafeArea()
            LinearGradient(
                colors: [
                    Color.clipGold.opacity(0.18),
                    .clear,
                    Color.clipGreen.opacity(0.10)
                ],
                startPoint: .top,
                endPoint: .bottom
            )
            .ignoresSafeArea()
        }
    }
}

private extension Color {
    static let clipGreen = Color(red: 0.20, green: 0.31, blue: 0.23)
    static let clipGold = Color(red: 0.82, green: 0.64, blue: 0.30)
}
