import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { TodayView() }
                .tabItem { Label("Today", systemImage: "sun.max.fill") }
            NavigationStack { BibleView() }
                .tabItem { Label("Bible", systemImage: "book.closed.fill") }
            NavigationStack { AcademyView() }
                .tabItem { Label("Academy", systemImage: "graduationcap.fill") }
            NavigationStack { BreatheView() }
                .tabItem { Label("Breathe", systemImage: "wind") }
            NavigationStack { JournalView() }
                .tabItem { Label("Journal", systemImage: "square.and.pencil") }
        }
        .tint(Color.dailyGold)
    }
}

struct BrandHeader: View {
    var body: some View {
        HStack(spacing: 12) {
            Image("DailyBreathIcon")
                .resizable()
                .scaledToFit()
                .frame(width: 54, height: 54)
                .clipShape(RoundedRectangle(cornerRadius: 14))
            VStack(alignment: .leading, spacing: 2) {
                Text("DAILYBREATH")
                    .font(.headline.weight(.black))
                Text("Faith-centered wellness")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            Spacer()
            Text("1.1")
                .font(.caption.bold())
                .padding(.horizontal, 10)
                .padding(.vertical, 6)
                .background(Color.dailyGreen.opacity(0.12), in: Capsule())
        }
    }
}

extension Color {
    static let dailyGreen = Color(red: 0.09, green: 0.25, blue: 0.17)
    static let dailyGold = Color(red: 0.82, green: 0.64, blue: 0.30)
    static let dailyCream = Color(red: 0.96, green: 0.92, blue: 0.84)
}
