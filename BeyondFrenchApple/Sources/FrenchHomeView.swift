import SwiftUI

struct FrenchHomeView: View {
    @EnvironmentObject private var store: AppStore

    private var focusText: String { store.lesson.text(for: store.learningLanguage.audioLanguage) }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                Text("LESSON OF THE DAY").font(.caption.weight(.black)).tracking(1.8).foregroundStyle(store.appTheme.accent)
                VStack(alignment: .leading, spacing: 12) {
                    Text(store.lesson.english).font(.title2.weight(.black))
                    Text(focusText).font(.system(size: 32, weight: .black, design: .rounded)).foregroundStyle(store.appTheme.accent)
                    if store.learningLanguage == .french { Text(store.lesson.frenchPronunciation).foregroundStyle(.secondary) }
                    Button { store.speakLesson(store.lesson, language: store.learningLanguage.audioLanguage) } label: { Label("Listen", systemImage: "speaker.wave.2.fill") }
                        .buttonStyle(.borderedProminent)
                }
                .padding(22).background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))

                NavigationLink { PracticeView() } label: { HomeActionCard(title: "Continue Learning", subtitle: "Practice today’s phrase", symbol: "play.circle.fill", color: store.appTheme.accent) }
                NavigationLink { AcademyView() } label: { HomeActionCard(title: "Academy", subtitle: "Follow your guided course", symbol: "graduationcap.fill", color: .orange) }
                NavigationLink { FrenchQuestHubView() } label: { HomeActionCard(title: "French Quest", subtitle: "Story Mode, trivia, and challenges", symbol: "map.fill", color: .pink) }
                NavigationLink { FrenchSettingsView() } label: { HomeActionCard(title: "Settings", subtitle: "Language path and appearance", symbol: "gearshape.fill", color: .purple) }
            }
            .padding(20)
        }
        .background(store.appTheme.appBackground)
        .navigationTitle("Beyond French")
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct HomeActionCard: View {
    let title: String, subtitle: String, symbol: String, color: Color
    var body: some View {
        HStack(spacing: 15) {
            Image(systemName: symbol).font(.title2.weight(.black)).foregroundStyle(color).frame(width: 40)
            VStack(alignment: .leading, spacing: 3) { Text(title).font(.headline.weight(.black)); Text(subtitle).font(.caption).foregroundStyle(.secondary) }
            Spacer(); Image(systemName: "chevron.right").foregroundStyle(.secondary)
        }
        .padding(18).background(Color.white.opacity(0.09), in: RoundedRectangle(cornerRadius: 18)).foregroundStyle(.white)
    }
}

private struct FrenchQuestHubView: View {
    var body: some View {
        List {
            Section("FRENCH QUEST") {
                NavigationLink { Text("Choose Jazzy, Louis, Irie, or Pablo and begin your route.").padding().navigationTitle("Story Mode") } label: { Label("Story Mode · Coming next", systemImage: "theatermasks.fill") }
                NavigationLink { TriviaView() } label: { Label("Duo Trivia", systemImage: "person.2.fill") }
            }
        }
        .navigationTitle("French Quest")
    }
}

private struct FrenchSettingsView: View {
    @EnvironmentObject private var store: AppStore
    var body: some View {
        Form {
            Section("Learning language") { Picker("Language", selection: $store.learningLanguage) { ForEach(FrenchLearningLanguage.allCases) { Text("\($0.symbol) \($0.title)").tag($0) } } }
            Section("Appearance") { ThemePicker() }
        }
        .navigationTitle("Settings")
    }
}
