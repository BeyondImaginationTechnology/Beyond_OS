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

                SectionTitle(text: "Tattoo imagination")
                Text("Describe an idea with Needle Bot and build a six-piece direction: stencil, stylesheet, lore, reference, placement, and a printer-ready asset.")
                    .foregroundStyle(.secondary)
                NavigationLink {
                    LiveFeatureScreen(title: "Stencil generator", subtitle: "Needle Bot and Llama Jaguar · six-piece tattoo direction", url: WebDestination.generator.url)
                } label: {
                    Label("Start a tattoo idea", systemImage: "wand.and.stars")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            VStack(alignment: .leading, spacing: 10) {
                SectionTitle(text: "Violet Trace")
                Text("Photograph a drawing or choose an image, tune its outline, then save, share, or print a stencil PNG.")
                    .foregroundStyle(.secondary)
                NavigationLink {
                    LiveFeatureScreen(title: "Violet Trace", subtitle: "Picture to stencil · violet or black outline", url: WebDestination.camera.url)
                } label: {
                    Label("Open stencil camera", systemImage: "camera.viewfinder")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)
                NavigationLink { NeedleBotView() } label: {
                    Label("Plan with Needle Bot", systemImage: "sparkles").frame(maxWidth: .infinity)
                }.buttonStyle(.bordered)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
        }
    }
}
