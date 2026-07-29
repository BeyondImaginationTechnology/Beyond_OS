import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: AppStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 22) {
                BrandHeader()
                HStack { AccessPill(text: "FREE LESSON OF THE DAY"); Spacer(); Text(store.statusMessage).font(.caption).foregroundStyle(.secondary) }
                VStack(alignment: .leading, spacing: 14) {
                    Text("TODAY’S PHRASE").font(.caption.bold()).tracking(2).foregroundStyle(.blue)
                    Text(store.lesson.english).font(.system(size: 38, weight: .black, design: .rounded))
                    Divider()
                    Text(store.lesson.french).font(.system(size: 34, weight: .bold, design: .rounded)).foregroundStyle(.blue)
                    Text(store.lesson.frenchPronunciation).font(.headline).foregroundStyle(.secondary)
                    Button { store.speak(store.lesson.french) } label: { Label("Listen in French", systemImage: "speaker.wave.2.fill").frame(maxWidth: .infinity) }
                        .buttonStyle(.borderedProminent).controlSize(.large)
                }
                .padding(22).background(.background, in: RoundedRectangle(cornerRadius: 26))
                .shadow(color: .black.opacity(0.08), radius: 20, y: 10)

                LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
                    LanguageTile(flag: "🇫🇷", name: "Français", value: store.lesson.french, color: .blue)
                    LanguageTile(flag: "🇭🇹", name: "Kreyòl", value: store.lesson.kreyol, color: .red)
                    LanguageTile(flag: "🇯🇲", name: "Patois", value: store.lesson.patois, color: .green)
                    LanguageTile(flag: "🇪🇸", name: "Español", value: store.lesson.spanish, color: .orange)
                }
                GroupBox("Culture note") { Text(store.lesson.cultureNote).frame(maxWidth: .infinity, alignment: .leading) }
            }.padding()
        }
        .background(Color(.systemGroupedBackground))
        .refreshable { await store.refreshLesson() }
        .navigationTitle("Today")
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct LanguageTile: View {
    let flag: String, name: String, value: String
    let color: Color
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(flag).font(.title)
            Text(name.uppercased()).font(.caption.bold()).foregroundStyle(color)
            Text(value).font(.headline).frame(maxWidth: .infinity, alignment: .leading)
        }.padding().frame(minHeight: 132).background(color.opacity(0.09), in: RoundedRectangle(cornerRadius: 20))
    }
}
