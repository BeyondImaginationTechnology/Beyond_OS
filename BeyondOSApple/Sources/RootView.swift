import SwiftUI

struct RootView: View {
    @Environment(\.openURL) private var openURL

    var body: some View {
        NavigationStack {
            ZStack {
                Color(red: 0.031, green: 0.051, blue: 0.098).ignoresSafeArea()
                VStack(alignment: .leading, spacing: 22) {
                    HStack {
                        VStack(alignment: .leading, spacing: 5) {
                            Text("BEYOND OS").font(.caption.weight(.heavy)).tracking(2).foregroundStyle(.cyan)
                            Text("Command center").font(.largeTitle.weight(.bold)).foregroundStyle(.white)
                        }
                        Spacer()
                        Image(systemName: "circle.grid.cross.fill").font(.title).foregroundStyle(.purple)
                    }
                    Text("A secure companion for the Beyond ecosystem. Sign in through Beyond ID, then continue in the workspace you need.").foregroundStyle(.secondary)
                    VStack(spacing: 12) {
                        OSCard(icon: "square.grid.2x2.fill", title: "Beyond OS Web", detail: "Overview, products, people, and system health") { open("https://os.beyondimagination.co.technology/") }
                        OSCard(icon: "wand.and.stars", title: "Beyond Studio", detail: "Create, review, and publish") { open("https://os.beyondimagination.co.technology/studio") }
                        OSCard(icon: "person.text.rectangle", title: "Beyond ID", detail: "Identity and account controls") { open("https://beyondimagination.co.technology/beyond-id/dashboard/") }
                    }
                    Spacer()
                    Text("iOS 0.1 · Companion edition").font(.caption).foregroundStyle(.secondary)
                }.padding(24)
            }.preferredColorScheme(.dark)
        }
    }

    private func open(_ string: String) {
        guard let url = URL(string: string) else { return }
        openURL(url)
    }
}

private struct OSCard: View {
    let icon: String
    let title: String
    let detail: String
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            HStack(spacing: 14) {
                Image(systemName: icon).frame(width: 28).font(.title3).foregroundStyle(.cyan)
                VStack(alignment: .leading, spacing: 3) { Text(title).fontWeight(.bold); Text(detail).font(.caption).foregroundStyle(.secondary) }
                Spacer(); Image(systemName: "arrow.up.right").foregroundStyle(.cyan)
            }.padding(16).background(Color(red: 0.067, green: 0.102, blue: 0.173)).clipShape(RoundedRectangle(cornerRadius: 17))
        }.buttonStyle(.plain)
    }
}
