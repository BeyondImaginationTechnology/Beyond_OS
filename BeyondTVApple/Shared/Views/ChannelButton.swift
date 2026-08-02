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
                    Image(systemName: channel.symbolName)
                        .font(.title.bold())
                        .frame(width: 44, height: 44)
                        .background(.black.opacity(0.22), in: RoundedRectangle(cornerRadius: 12))
                    Spacer()
                    Text(channel.displayNumber)
                        .font(.caption.monospacedDigit().bold())
                        .padding(.horizontal, 9)
                        .padding(.vertical, 6)
                        .background(.black.opacity(0.25), in: Capsule())
                }
                Spacer()
                Text(channel.category.uppercased())
                    .font(.caption2.bold())
                    .tracking(1)
                    .foregroundStyle(.orange.opacity(0.92))
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
                    colors: selected ? channel.gradientColors : channel.gradientColors.map { $0.opacity(0.74) },
                    startPoint: .topLeading,
                    endPoint: .bottomTrailing
                ),
                in: RoundedRectangle(cornerRadius: 20)
            )
            .overlay {
                RoundedRectangle(cornerRadius: 20)
                    .stroke(selected ? .white.opacity(0.8) : .white.opacity(0.14), lineWidth: selected ? 2 : 1)
            }
            .foregroundStyle(.white)
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
