import SwiftUI

struct NeedleBotView: View {
    var body: some View {
        TattooScreen(title: "Needle Bot") {
            VStack(spacing: 14) {
                AsyncImage(url: URL(string: "https://beyondimagination.co.technology/beyond-tattoo/assets/img/needle-bot-v2.png")) { phase in
                    switch phase {
                    case .success(let image): image.resizable().scaledToFit()
                    default: Image(systemName: "sparkles").font(.system(size: 58)).foregroundStyle(Color.tattooViolet)
                    }
                }
                .frame(height: 180)

                VStack(spacing: 6) {
                    Text("Your tattoo AI companion").font(.title2.weight(.black)).foregroundStyle(Color.tattooInk)
                    Text("Needle Bot helps with concepts, composition, placement, linework, stencil preparation, and the editor. Jaguar Draw handles image-generation requests in the editor.")
                        .multilineTextAlignment(.center).foregroundStyle(.secondary)
                }

                NavigationLink {
                    LiveFeatureScreen(title: "Needle Bot", subtitle: "Tattoo and stencil guidance in your Beyond Tattoo session.", url: WebDestination.needleBot.url)
                } label: {
                    Label("Start a conversation", systemImage: "message.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
        }
    }
}
