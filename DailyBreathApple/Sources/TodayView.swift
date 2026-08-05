import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                themePicker
                verseCard
                devotionalCard
                quickActions
            }
            .padding()
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle("Today")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var themePicker: some View {
        Menu {
            ForEach(DailyBreathTheme.allCases) { theme in
                Button {
                    selectedThemeID = theme.id
                } label: {
                    Label(theme.name, systemImage: theme.symbolName)
                }
            }
        } label: {
            Label(selectedTheme.name, systemImage: selectedTheme.symbolName)
                .font(.caption.bold())
                .padding(.horizontal, 12)
                .padding(.vertical, 8)
                .background(.background.opacity(0.86), in: Capsule())
        }
        .tint(selectedTheme.primary)
    }

    private var verseCard: some View {
        VStack(alignment: .leading, spacing: 18) {
            Label("Verse of the Day", systemImage: "sun.max.fill")
                .font(.caption.bold())
                .tracking(1.4)
                .foregroundStyle(selectedTheme.accent)
            Text("\"\(store.verse.text)\"")
                .font(.system(size: 36, weight: .semibold, design: .serif))
                .foregroundStyle(.white)
                .fixedSize(horizontal: false, vertical: true)
            Text(store.verse.reference)
                .font(.headline.weight(.black))
                .foregroundStyle(selectedTheme.accent)
            Divider().overlay(.white.opacity(0.22))
            Text(store.verse.reflection)
                .font(.body)
                .foregroundStyle(.white.opacity(0.82))
            HStack(spacing: 10) {
                Button { store.speakVerse() } label: {
                    Label("Listen", systemImage: "speaker.wave.2.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(selectedTheme.accent)

                NavigationLink {
                    VerseDetailView(verse: store.verse)
                } label: {
                    Label("Open", systemImage: "book.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)
                .tint(.white)
            }
            .controlSize(.large)
        }
        .padding(24)
        .background(
            LinearGradient(colors: [selectedTheme.primary, selectedTheme.secondary], startPoint: .topLeading, endPoint: .bottomTrailing),
            in: RoundedRectangle(cornerRadius: 26)
        )
    }

    private var devotionalCard: some View {
        NavigationLink {
            DevotionalDetailView(devotional: store.devotional)
        } label: {
            HStack(alignment: .top, spacing: 14) {
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
                Spacer(minLength: 8)
                Image(systemName: "chevron.right")
                    .font(.footnote.weight(.bold))
                    .foregroundStyle(.tertiary)
                    .padding(.top, 6)
            }
            .padding(20)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(.background, in: RoundedRectangle(cornerRadius: 22))
        }
        .buttonStyle(.plain)
    }

    private var quickActions: some View {
        LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
            NavigationLink {
                BibleView()
            } label: {
                QuickAction(title: "Bible Library", subtitle: "Read and reflect", systemImage: "book.closed.fill")
            }
            NavigationLink {
                BreatheView()
            } label: {
                QuickAction(title: "Peace Breath", subtitle: "Four-count calm", systemImage: "wind")
            }
            NavigationLink {
                PrayerPracticesView()
            } label: {
                QuickAction(title: "Specific Prayers", subtitle: "Guidance and healing", systemImage: "hands.sparkles.fill")
            }
            NavigationLink {
                WeeklyChallengeView()
            } label: {
                QuickAction(title: "Weekly Challenge", subtitle: "Faith in action", systemImage: "calendar.badge.checkmark")
            }
        }
        .buttonStyle(.plain)
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

private struct VerseDetailView: View {
    let verse: Verse

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                Label("Verse of the Day", systemImage: "sun.max.fill")
                    .font(.caption.bold())
                    .tracking(1.4)
                    .foregroundStyle(Color.dailyGold)
                Text("\"\(verse.text)\"")
                    .font(.system(size: 38, weight: .semibold, design: .serif))
                    .fixedSize(horizontal: false, vertical: true)
                Text(verse.reference)
                    .font(.title3.weight(.black))
                    .foregroundStyle(Color.dailyGreen)
                Divider()
                Text(verse.reflection)
                    .font(.body)
                    .foregroundStyle(.secondary)
                NavigationLink {
                    BibleView()
                } label: {
                    Label("Open Bible Library", systemImage: "book.closed.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(Color.dailyGreen)
                .controlSize(.large)
            }
            .padding()
        }
        .background(Color(.systemGroupedBackground))
        .navigationTitle(verse.reference)
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct DevotionalDetailView: View {
    let devotional: Devotional

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Text(devotional.scripture)
                        .font(.caption.bold())
                        .foregroundStyle(Color.dailyGold)
                    Text(devotional.title)
                        .font(.largeTitle.weight(.black))
                    Label("\(devotional.minutes) minute read", systemImage: "clock.fill")
                        .font(.caption.bold())
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }

            Section("Reflection") {
                Text(devotional.body)
                    .font(.body)
            }

            Section("Prayer") {
                Text(devotional.prayer)
                    .font(.body)
            }

            Section("Practice") {
                Text(devotional.practice)
                    .font(.body)
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Reflect in Journal", systemImage: "square.and.pencil")
                }
            }
        }
        .navigationTitle("Devotional")
    }
}

private struct PrayerPracticesView: View {
    @EnvironmentObject private var store: DailyBreathStore

    var body: some View {
        List {
            Section {
                ForEach(store.practices.filter { $0.title != "Peace Breath" && $0.title != "Weekly Challenge" }) { practice in
                    NavigationLink {
                        PrayerPracticeDetailView(practice: practice)
                    } label: {
                        Label {
                            VStack(alignment: .leading, spacing: 3) {
                                Text(practice.title)
                                Text(practice.subtitle)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                        } icon: {
                            Image(systemName: practice.systemImage)
                                .foregroundStyle(Color.dailyGold)
                        }
                    }
                }
            }
        }
        .navigationTitle("Specific Prayers")
    }
}

private struct PrayerPracticeDetailView: View {
    let practice: PrayerPractice

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Image(systemName: practice.systemImage)
                        .font(.largeTitle)
                        .foregroundStyle(Color.dailyGold)
                    Text(practice.title)
                        .font(.largeTitle.weight(.black))
                    Text(practice.subtitle)
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }

            Section("Prayer") {
                Text(prayerText)
            }

            Section("Next Step") {
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Write a Reflection", systemImage: "square.and.pencil")
                }
            }
        }
        .navigationTitle(practice.title)
    }

    private var prayerText: String {
        switch practice.title {
        case "Guidance Prayer":
            return "Lord, give me wisdom for the decision in front of me. Help me listen before I move and choose what brings peace, truth, and love."
        case "Gratitude Reset":
            return "Lord, open my eyes to what is good. Teach me to receive today with humility and respond with generosity."
        default:
            return "Lord, meet me in this practice and shape my next step with grace."
        }
    }
}

private struct WeeklyChallengeView: View {
    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Image(systemName: "calendar.badge.checkmark")
                        .font(.largeTitle)
                        .foregroundStyle(Color.dailyGold)
                    Text("Faith in Action")
                        .font(.largeTitle.weight(.black))
                    Text("Choose one quiet act of faith this week and make it concrete.")
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }

            Section("This Week") {
                Label("Encourage someone who needs courage.", systemImage: "message.fill")
                Label("Give without needing credit.", systemImage: "gift.fill")
                Label("Return to stillness before reacting.", systemImage: "pause.circle.fill")
            }

            Section("Track It") {
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Record Your Challenge", systemImage: "square.and.pencil")
                }
            }
        }
        .navigationTitle("Weekly Challenge")
    }
}
