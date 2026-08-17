import SwiftUI

struct TodayView: View {
    @EnvironmentObject private var store: AppStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 22) {
                BrandHeader()
                HStack {
                    AccessPill(text: "FREE LESSON OF THE DAY")
                    Spacer()
                    Text(store.statusMessage).font(.caption).foregroundStyle(.secondary)
                }
                VStack(alignment: .leading, spacing: 14) {
                    Text("TODAY'S PHRASE").font(.caption.bold()).tracking(2).foregroundStyle(store.appTheme.accent)
                    Text(store.lesson.english).font(.system(size: 38, weight: .black, design: .rounded))
                    Divider()
                    Text(store.lesson.french).font(.system(size: 34, weight: .bold, design: .rounded)).foregroundStyle(store.appTheme.accent)
                    Text(store.lesson.frenchPronunciation).font(.headline).foregroundStyle(.secondary)
                    HStack {
                        Button { store.speakLesson(store.lesson) } label: {
                            Label("Listen", systemImage: "speaker.wave.2.fill").frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.borderedProminent)
                        NavigationLink {
                            PracticeView()
                        } label: {
                            Label("Practice", systemImage: "pencil.and.outline").frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.bordered)
                    }
                    .controlSize(.large)
                }
                .padding(22)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 22))
                .overlay(RoundedRectangle(cornerRadius: 22).stroke(store.appTheme.accent.opacity(0.24), lineWidth: 1))
                .shadow(color: store.appTheme.accent.opacity(0.18), radius: 20, y: 10)

                LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
                    LanguageTile(flag: "FR", name: "Francais", value: store.lesson.french, color: store.appTheme.accent) {
                        store.speakLesson(store.lesson)
                    }
                    LanguageTile(flag: "HT", name: "Kreyòl", value: store.lesson.kreyol, color: .red) {
                        store.speak(store.lesson.kreyol, language: "ht-HT")
                    }
                    LanguageTile(flag: "JM", name: "Patois", value: store.lesson.patois, color: .green) {
                        store.speak(store.lesson.patois, language: "en-JM")
                    }
                    LanguageTile(flag: "ES", name: "Espanol", value: store.lesson.spanish, color: .orange) {
                        store.speak(store.lesson.spanish, language: "es-ES")
                    }
                }
                VStack(alignment: .leading, spacing: 8) {
                    Label("Culture note", systemImage: "globe.americas.fill")
                        .font(.headline)
                        .foregroundStyle(store.appTheme.accent)
                    Text(store.lesson.cultureNote)
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .foregroundStyle(.white.opacity(0.82))
                }
                .padding(16)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
                .overlay(RoundedRectangle(cornerRadius: 18).stroke(store.appTheme.accent.opacity(0.16), lineWidth: 1))
            }.padding()
        }
        .background(store.appTheme.appBackground)
        .refreshable { await store.refreshLesson() }
        .navigationTitle("Today")
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct LanguageTile: View {
    let flag: String, name: String, value: String
    let color: Color
    let listen: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(flag).font(.title3.weight(.black))
                Spacer()
                Button(action: listen) {
                    Image(systemName: "speaker.wave.2.fill")
                        .font(.caption.weight(.bold))
                        .frame(width: 30, height: 30)
                        .background(color.opacity(0.22), in: Circle())
                }
                .buttonStyle(.plain)
                .accessibilityLabel("Listen to \(name)")
            }
            Text(name.uppercased()).font(.caption.bold()).foregroundStyle(color)
            Text(value).font(.headline).frame(maxWidth: .infinity, alignment: .leading)
        }
        .foregroundStyle(.white)
        .padding()
        .frame(minHeight: 132)
        .background(color.opacity(0.18), in: RoundedRectangle(cornerRadius: 18))
        .overlay(RoundedRectangle(cornerRadius: 18).stroke(color.opacity(0.24), lineWidth: 1))
    }
}
