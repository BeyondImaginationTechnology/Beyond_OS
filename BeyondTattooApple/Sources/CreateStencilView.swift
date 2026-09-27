import SwiftUI

struct CreateStencilView: View {
    @EnvironmentObject private var store: TattooStore

    var body: some View {
        TattooScreen(title: "Create") {
            VStack(alignment: .leading, spacing: 14) {
                AsyncImage(url: store.dailyDrop.previewURL) { phase in
                    switch phase {
                    case .success(let image): image.resizable().scaledToFill()
                    default: Color.tattooBackground.overlay(Image(systemName: "photo.artframe").font(.largeTitle))
                    }
                }
                .frame(height: 220)
                .clipShape(RoundedRectangle(cornerRadius: 10))

                SectionTitle(text: "Canvas ready")
                Text("Today’s released stencil is loaded as your starting point. Import a reference only when you want to replace it.")
                    .foregroundStyle(.secondary)
                NavigationLink {
                    LiveFeatureScreen(title: "Stencil editor", subtitle: "Jaguar render requests stay in this app while they process.", url: WebDestination.editor.url)
                } label: {
                    Label("Edit today’s stencil", systemImage: "wand.and.stars")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            VStack(alignment: .leading, spacing: 10) {
                SectionTitle(text: "Jaguar Draw")
                Text("Describe a new tattoo idea in the editor. Jaguar keeps the request in the app and returns the generation status there; Needle Bot remains available for stencil direction.")
                    .foregroundStyle(.secondary)
                NavigationLink {
                    NeedleBotView()
                } label: {
                    Label("Plan with Needle Bot", systemImage: "sparkles")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
        }
    }
}
