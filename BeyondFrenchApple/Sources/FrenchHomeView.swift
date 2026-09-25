import SwiftUI

struct FrenchHomeView: View {
    @EnvironmentObject private var store: AppStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                BrandHeader()

                VStack(alignment: .leading, spacing: 14) {
                    Text("FRENCH · LESSON OF THE DAY")
                        .font(.caption.weight(.black))
                        .tracking(1.5)
                        .foregroundStyle(store.appTheme.accent)
                    Text(store.lesson.english)
                        .font(.system(size: 30, weight: .black, design: .rounded))
                        .fixedSize(horizontal: false, vertical: true)
                    Text(store.lesson.french)
                        .font(.system(size: 32, weight: .black, design: .rounded))
                        .foregroundStyle(store.appTheme.accent)
                        .fixedSize(horizontal: false, vertical: true)
                    Text(store.lesson.frenchPronunciation)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                    Button { store.speakLesson(store.lesson) } label: {
                        Label("Listen to the French phrase", systemImage: "speaker.wave.2.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.bordered)
                    NavigationLink { PracticeView() } label: {
                        Label("Daily challenge", systemImage: "arrow.right.circle.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .controlSize(.large)
                }
                .padding(20)
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 24))

                VStack(alignment: .leading, spacing: 10) {
                    Text("Learn French with four guides")
                        .font(.title3.weight(.black))
                    Text("Louis, Irie, Jazzy, and Pablo bring different voices and cultures to the same French lesson.")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                    FrenchGuidesView()
                }

                NavigationLink { AcademyView() } label: {
                    HomeActionCard(title: "Academy", subtitle: "Choose your difficulty and follow a lesson path", symbol: "graduationcap.fill", color: store.appTheme.accent)
                }
                NavigationLink { TodayView() } label: {
                    HomeActionCard(title: "Explore today's phrase", subtitle: "Meaning, pronunciation, and cultural bridges", symbol: "sun.max.fill", color: .yellow)
                }
                NavigationLink { FrenchQuestHubView() } label: {
                    HomeActionCard(title: "French Quest", subtitle: "Trivia and challenges", symbol: "map.fill", color: .pink)
                }
                NavigationLink { FrenchSettingsView() } label: {
                    HomeActionCard(title: "Settings", subtitle: "Appearance", symbol: "gearshape.fill", color: .purple)
                }
            }
            .padding(20)
        }
        .background(store.appTheme.appBackground.ignoresSafeArea())
        .navigationTitle("Beyond French")
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct HomeActionCard: View {
    let title: String
    let subtitle: String
    let symbol: String
    let color: Color

    var body: some View {
        HStack(spacing: 14) {
            Image(systemName: symbol)
                .font(.title2.weight(.black))
                .foregroundStyle(color)
                .frame(width: 40)
            VStack(alignment: .leading, spacing: 4) {
                Text(title).font(.headline.weight(.black))
                Text(subtitle).font(.subheadline).foregroundStyle(.secondary)
            }
            Spacer(minLength: 4)
            Image(systemName: "chevron.right").foregroundStyle(.secondary)
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.white.opacity(0.09), in: RoundedRectangle(cornerRadius: 18))
        .foregroundStyle(.white)
    }
}

struct FrenchQuestHubView: View {
    var body: some View {
        List {
            Section("FRENCH QUEST") {
                NavigationLink { TriviaView() } label: {
                    Label("Duo Trivia", systemImage: "person.2.fill")
                }
            }
        }
        .navigationTitle("French Quest")
    }
}

private struct FrenchSettingsView: View {
    var body: some View {
        ScrollView {
            ThemePicker().padding()
        }
        .navigationTitle("Settings")
    }
}
