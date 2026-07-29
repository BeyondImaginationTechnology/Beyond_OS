import SwiftUI

struct AcademyView: View {
    @EnvironmentObject private var store: AppStore
    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                AccessPill(text: store.hasBeyondID ? "FULL ACADEMY" : "1 FREE MODULE LESSON")
                Text("Greetings · Lesson 1").font(.largeTitle.bold())
                GroupBox {
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Say hello with confidence").font(.title2.bold())
                        Text("Hello").foregroundStyle(.secondary)
                        Text("Bonjour").font(.system(size: 34, weight: .black)).foregroundStyle(.blue)
                        Text("bohn-ZHOOR").foregroundStyle(.secondary)
                        Button { store.speak("Bonjour") } label: { Label("Listen", systemImage: "speaker.wave.2.fill") }
                            .buttonStyle(.borderedProminent)
                    }.frame(maxWidth: .infinity, alignment: .leading)
                }
                if !store.hasBeyondID {
                    VStack(alignment: .leading, spacing: 10) {
                        Label("Lessons 2–10", systemImage: "lock.fill")
                        Label("Tests and module exams", systemImage: "lock.fill")
                        Label("Saved progress and bit$ rewards", systemImage: "lock.fill")
                        Link("Create a free Beyond ID", destination: URL(string: "https://beyondimagination.co.technology/beyond-id/auth/register.php?app=beyond-french")!)
                            .buttonStyle(.borderedProminent)
                    }.padding().background(.blue.opacity(0.08), in: RoundedRectangle(cornerRadius: 20))
                }
            }.padding()
        }.navigationTitle("Academy")
    }
}
