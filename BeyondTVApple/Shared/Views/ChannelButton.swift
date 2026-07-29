import Foundation
import SwiftUI

struct ChannelButton: View {
    let channel: Channel
    let selected: Bool
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            VStack(alignment: .leading, spacing: 12) {
                HStack {
                    Text(channel.icon)
                        .font(.largeTitle)
                    Spacer()
                    Text(String(format: "%02d", channel.number))
                        .font(.caption.monospacedDigit().bold())
                        .foregroundStyle(.secondary)
                }
                Spacer()
                Text(channel.name)
                    .font(.headline)
                    .lineLimit(2)
                Text(channelDetail)
                    .font(.caption2.bold())
                    .tracking(1)
                    .foregroundStyle(.secondary)
            }
            .frame(width: 190, height: 130, alignment: .leading)
            .padding(18)
            .background(
                LinearGradient(
                    colors: selected ? [.purple.opacity(0.8), .indigo.opacity(0.8)] : [.white.opacity(0.12), .white.opacity(0.06)],
                    startPoint: .topLeading,
                    endPoint: .bottomTrailing
                ),
                in: RoundedRectangle(cornerRadius: 18)
            )
            .overlay {
                RoundedRectangle(cornerRadius: 18)
                    .stroke(selected ? .white.opacity(0.8) : .white.opacity(0.1), lineWidth: selected ? 2 : 1)
            }
        }
        .buttonStyle(.plain)
    }

    private var channelDetail: String {
        #if os(tvOS)
        channel.isWebPlaybackChannel ? "iPHONE / iPAD" : channel.category.uppercased()
        #else
        channel.category.uppercased()
        #endif
    }
}
