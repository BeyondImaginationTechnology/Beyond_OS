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
                Text("Be still, and know that I am God.")
                    .font(.system(.title2, design: .serif).weight(.semibold))
                Text("Psalm 46:10")
                    .font(.headline.weight(.black))
                    .foregroundStyle(Color.clipGreen)
                Divider()
                Text("Begin slowly. Make room for quiet, notice your breath, and let the next faithful step be enough for today.")
                    .foregroundStyle(.secondary)
            }
            .padding(20)
            .background(.background, in: RoundedRectangle(cornerRadius: 22))

            Link(destination: URL(string: "https://beyondimagination.co.technology/dailybreath/")!) {
                Label("Open Full App", systemImage: "arrow.down.app.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .tint(Color.clipGreen)
            .controlSize(.large)

            Spacer()
        }
        .padding()
        .background(ClipThemeBackground())
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
