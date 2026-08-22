import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { TodayStencilView() }
                .tabItem { Label("Today", systemImage: "sparkles.rectangle.stack.fill") }
            NavigationStack { LibraryView() }
                .tabItem { Label("Library", systemImage: "square.grid.2x2.fill") }
            NavigationStack { HealingView() }
                .tabItem { Label("Healing", systemImage: "heart.text.square.fill") }
            NavigationStack { StudiosView() }
                .tabItem { Label("Studios", systemImage: "mappin.and.ellipse") }
            NavigationStack { ProfileView() }
                .tabItem { Label("Profile", systemImage: "person.crop.circle.fill") }
        }
        .tint(Color.tattooViolet)
    }
}

struct BrandHeader: View {
    var body: some View {
        HStack(spacing: 12) {
            Image("BeyondTattooLogo")
                .resizable()
                .scaledToFit()
                .frame(width: 54, height: 54)
                .clipShape(RoundedRectangle(cornerRadius: 12))
            VStack(alignment: .leading, spacing: 2) {
                Text("BEYOND TATTOO")
                    .font(.headline.weight(.black))
                Text("Stencil drops and healing care")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            Spacer()
            Text("1.2")
                .font(.caption.bold())
                .padding(.horizontal, 10)
                .padding(.vertical, 6)
                .background(Color.tattooGold.opacity(0.16), in: Capsule())
        }
    }
}

struct TattooScreen<Content: View>: View {
    let title: String
    @ViewBuilder var content: Content

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                content
            }
            .padding()
        }
        .background(Color.tattooBackground.ignoresSafeArea())
        .navigationTitle(title)
    }
}

struct SectionTitle: View {
    let text: String

    var body: some View {
        Text(text.uppercased())
            .font(.caption.weight(.black))
            .tracking(1.8)
            .foregroundStyle(Color.tattooGold)
    }
}

extension Color {
    static let tattooBackground = Color(red: 0.03, green: 0.03, blue: 0.04)
    static let tattooPanel = Color(red: 0.08, green: 0.07, blue: 0.09)
    static let tattooViolet = Color(red: 0.58, green: 0.20, blue: 0.92)
    static let tattooGold = Color(red: 0.83, green: 0.62, blue: 0.32)
    static let tattooInk = Color(red: 0.93, green: 0.91, blue: 0.95)
}
