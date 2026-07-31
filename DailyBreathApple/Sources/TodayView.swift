import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: DailyBreathStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                verseCard
                devotionalCard
                quickActions
            }
            .padding()
        }
        .background(Color(.systemGroupedBackground))
        .navigationTitle("Today")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var verseCard: some View {
        VStack(alignment: .leading, spacing: 18) {
            Label("Verse of the Day", systemImage: "sun.max.fill")
                .font(.caption.bold())
                .tracking(1.4)
                .foregroundStyle(Color.dailyGold)
            Text("\"\(store.verse.text)\"")
                .font(.system(size: 36, weight: .semibold, design: .serif))
                .foregroundStyle(.white)
                .fixedSize(horizontal: false, vertical: true)
            Text(store.verse.reference)
                .font(.headline.weight(.black))
                .foregroundStyle(Color.dailyGold)
            Divider().overlay(.white.opacity(0.22))
            Text(store.verse.reflection)
                .font(.body)
                .foregroundStyle(.white.opacity(0.82))
            Button { store.speakVerse() } label: {
                Label("Read & Listen", systemImage: "speaker.wave.2.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .tint(Color.dailyGold)
            .controlSize(.large)
        }
        .padding(24)
        .background(
            LinearGradient(colors: [Color.dailyGreen, Color(red: 0.17, green: 0.41, blue: 0.29)], startPoint: .topLeading, endPoint: .bottomTrailing),
            in: RoundedRectangle(cornerRadius: 26)
        )
    }

    private var devotionalCard: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("TODAY'S DEVOTIONAL")
                .font(.caption.bold())
                .tracking(1.6)
                .foregroundStyle(Color.dailyGreen)
            Text(store.devotional.title)
                .font(.title2.weight(.bold))
            Text(store.devotional.excerpt)
                .foregroundStyle(.secondary)
            Label("\(store.devotional.scripture) · \(store.devotional.minutes) minute read", systemImage: "clock.fill")
                .font(.caption.bold())
                .foregroundStyle(Color.dailyGold)
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(.background, in: RoundedRectangle(cornerRadius: 22))
    }

    private var quickActions: some View {
        LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
            QuickAction(title: "Bible Library", subtitle: "Read and reflect", systemImage: "book.closed.fill")
            QuickAction(title: "Peace Breath", subtitle: "Four-count calm", systemImage: "wind")
            QuickAction(title: "Specific Prayers", subtitle: "Guidance and healing", systemImage: "hands.sparkles.fill")
            QuickAction(title: "Weekly Challenge", subtitle: "Faith in action", systemImage: "calendar.badge.checkmark")
        }
    }
}

private struct QuickAction: View {
    let title: String
    let subtitle: String
    let systemImage: String

    var body: some View {
        VStack(alignment: .leading, spacing: 9) {
            Image(systemName: systemImage)
                .font(.title2)
                .foregroundStyle(Color.dailyGold)
            Text(title)
                .font(.headline)
            Text(subtitle)
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .padding()
        .frame(maxWidth: .infinity, minHeight: 126, alignment: .topLeading)
        .background(.background, in: RoundedRectangle(cornerRadius: 20))
    }
}
