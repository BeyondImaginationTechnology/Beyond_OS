import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { TodayView() }
                .tabItem { Label("Today", systemImage: "sun.max.fill") }
            NavigationStack { DictionaryView() }
                .tabItem { Label("Dictionary", systemImage: "character.book.closed.fill") }
            NavigationStack { AcademyView() }
                .tabItem { Label("Learn", systemImage: "graduationcap.fill") }
            NavigationStack { PracticeView() }
                .tabItem { Label("Practice", systemImage: "waveform.and.mic") }
            NavigationStack { ProgressView() }
                .tabItem { Label("Progress", systemImage: "chart.bar.fill") }
        }
        .tint(.blue)
    }
}

struct BrandHeader: View {
    var body: some View {
        HStack(spacing: 12) {
            Image("BeyondFrenchLogo").resizable().scaledToFit().frame(width: 54, height: 54).clipShape(Circle())
            VStack(alignment: .leading, spacing: 2) {
                Text("BEYOND FRENCH").font(.headline.weight(.black))
                Text("Daily Academy").font(.caption).foregroundStyle(.secondary)
            }
            Spacer()
            Text("2.0").font(.caption.bold()).padding(.horizontal, 10).padding(.vertical, 6).background(.blue.opacity(0.12), in: Capsule())
        }
    }
}

struct AccessPill: View {
    let text: String
    var body: some View {
        Label(text, systemImage: "checkmark.seal.fill")
            .font(.caption.bold()).foregroundStyle(.green)
            .padding(.horizontal, 10).padding(.vertical, 7)
            .background(.green.opacity(0.11), in: Capsule())
    }
}
