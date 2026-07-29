import SwiftUI

struct ChannelGuideView: View {
    @EnvironmentObject private var model: AppModel

    var body: some View {
        NavigationStack {
            List(model.channels) { channel in
                Button {
                    Task { await model.tune(to: channel) }
                } label: {
                    HStack(spacing: 18) {
                        Text(channel.icon)
                            .font(.largeTitle)
                            .frame(width: 58, height: 58)
                            .background(.purple.opacity(0.18), in: RoundedRectangle(cornerRadius: 14))
                        VStack(alignment: .leading, spacing: 5) {
                            Text("CHANNEL \(channel.number)")
                                .font(.caption.bold())
                                .foregroundStyle(.secondary)
                            Text(channel.name)
                                .font(.headline)
                            Text(channel.description)
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                                .lineLimit(2)
                        }
                        Spacer()
                        #if os(tvOS)
                        if channel.isWebPlaybackChannel {
                            Text("iPHONE / iPAD")
                                .font(.caption2.bold())
                                .foregroundStyle(.secondary)
                        }
                        #endif
                        Image(systemName: model.selectedChannel == channel ? "waveform.circle.fill" : "play.circle")
                            .font(.title2)
                            .foregroundStyle(model.selectedChannel == channel ? .purple : .secondary)
                    }
                    .padding(.vertical, 8)
                }
                .buttonStyle(.plain)
            }
            .navigationTitle("Live Guide")
            .overlay {
                if model.channels.isEmpty {
                    ProgressView("Loading channels…")
                }
            }
        }
    }
}
