import SwiftUI

struct StudiosView: View {
    @EnvironmentObject private var store: TattooStore

    var body: some View {
        TattooScreen(title: "Studios") {
            VStack(alignment: .leading, spacing: 10) {
                SectionTitle(text: "Verified marketplace")
                Text("Find artists ready for Beyond Tattoo stencil packs.")
                    .font(.title2.weight(.bold))
                    .foregroundStyle(Color.tattooInk)
                Link(destination: WebDestination.studios.url) {
                    Label("Open studio directory", systemImage: "arrow.up.forward.app.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            ForEach(store.studios) { studio in
                StudioCard(studio: studio)
            }
        }
    }
}

private struct StudioCard: View {
    let studio: StudioLead

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                VStack(alignment: .leading, spacing: 4) {
                    Text(studio.name)
                        .font(.headline.weight(.bold))
                        .foregroundStyle(Color.tattooInk)
                    Text(studio.city)
                        .foregroundStyle(.secondary)
                }
                Spacer()
                if studio.isVerified {
                    Image(systemName: "checkmark.seal.fill")
                        .foregroundStyle(.green)
                }
            }
            HStack {
                ForEach(studio.specialties, id: \.self) { specialty in
                    Text(specialty)
                        .font(.caption.weight(.semibold))
                        .padding(.horizontal, 8)
                        .padding(.vertical, 5)
                        .background(Color.tattooViolet.opacity(0.15), in: Capsule())
                }
            }
            Label("Response: \(studio.responseTime)", systemImage: "clock.fill")
                .font(.caption)
                .foregroundStyle(Color.tattooGold)
        }
        .padding()
        .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}
