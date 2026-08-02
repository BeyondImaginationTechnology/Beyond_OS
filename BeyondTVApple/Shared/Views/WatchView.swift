import AVKit
import SwiftUI

struct WatchView: View {
    @EnvironmentObject private var model: AppModel

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 24) {
                    hero
                    player
                    nowPlaying
                    onNow
                    channelRail
                }
                .padding()
            }
            .background(BeyondTVBackground().ignoresSafeArea())
            .toolbar {
                ToolbarItem(placement: .primaryAction) {
                    ThemeToggleButton()
                }
            }
        }
    }

    private var hero: some View {
        VStack(alignment: .leading, spacing: 18) {
            HStack(alignment: .center, spacing: 14) {
                header
            }

            VStack(alignment: .leading, spacing: 8) {
                Text("\(model.channels.count) CHANNELS · FREE TO WATCH · NO ACCOUNT REQUIRED")
                    .font(.caption.bold())
                    .tracking(1.8)
                    .foregroundStyle(.orange)
                Text(model.selectedChannel?.name ?? "Beyond After Dark")
                    .font(.system(size: 54, weight: .black, design: .rounded))
                    .minimumScaleFactor(0.62)
                    .lineLimit(2)
                Text(model.selectedChannel?.description ?? "Supernatural stories, cult animation, movies, sports, learning, and late-night mystery.")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                    .lineLimit(3)
            }
        }
    }

    private var header: some View {
        HStack(alignment: .center, spacing: 12) {
            Image("BeyondTVLogo")
                .resizable()
                .scaledToFit()
                .frame(width: 48, height: 48)
                .clipShape(RoundedRectangle(cornerRadius: 13))
                .accessibilityHidden(true)

            VStack(alignment: .leading, spacing: 4) {
                Text("BEYOND")
                    .font(.headline.bold())
                Text("TV")
                    .font(.caption.bold())
                    .tracking(2.2)
                    .foregroundStyle(.orange)
            }
            Spacer()
            Label("LIVE", systemImage: "dot.radiowaves.left.and.right")
                .font(.caption.bold())
                .padding(.horizontal, 12)
                .padding(.vertical, 8)
                .background(.red.opacity(0.20), in: Capsule())
                .foregroundStyle(.red.opacity(0.95))
        }
    }

    @ViewBuilder
    private var player: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 22)
                .fill(.black)
                .aspectRatio(16 / 9, contentMode: .fit)

            #if os(iOS)
            if let webPlaybackURL = model.webPlaybackURL {
                WebPlayerView(url: webPlaybackURL)
                    .aspectRatio(16 / 9, contentMode: .fit)
                    .clipShape(RoundedRectangle(cornerRadius: 22))
            } else {
                nativePlayer
            }
            #else
            nativePlayer
            #endif
        }
        .overlay {
            RoundedRectangle(cornerRadius: 24)
                .stroke(.white.opacity(0.16), lineWidth: 1)
        }
        .clipShape(RoundedRectangle(cornerRadius: 24))
        .shadow(color: .black.opacity(0.40), radius: 28, y: 18)
    }

    @ViewBuilder
    private var nativePlayer: some View {
        Group {
            if model.currentSource != nil {
                VideoPlayer(player: model.player)
                    .aspectRatio(16 / 9, contentMode: .fit)
                    .clipShape(RoundedRectangle(cornerRadius: 22))
            } else if model.isLoading {
                VStack(spacing: 12) {
                    ProgressView()
                    Text("Tuning \(model.selectedChannel?.name ?? "Beyond TV")")
                        .font(.headline)
                        .foregroundStyle(.secondary)
                }
            } else {
                ContentUnavailableView {
                    Label(unavailableTitle, systemImage: "tv.slash")
                } description: {
                    Text(model.errorMessage ?? "Try another channel from the guide.")
                } actions: {
                    Button("Retry") {
                        Task { await model.retry() }
                    }
                    .buttonStyle(.borderedProminent)
                }
            }
        }
    }

    private var unavailableTitle: String {
        #if os(tvOS)
        model.selectedChannel?.isWebPlaybackChannel == true ? "Available on iPhone and iPad" : "Channel unavailable"
        #else
        "Channel unavailable"
        #endif
    }

    private var nowPlaying: some View {
        VStack(alignment: .leading, spacing: 14) {
            VStack(alignment: .leading, spacing: 6) {
                Text(model.status.label)
                    .font(.caption.bold())
                    .tracking(1.2)
                    .foregroundStyle(.orange)
                Text(model.status.now)
                    .font(.title2.bold())
                if let source = model.currentSource {
                    Text([source.provider, source.license].compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " · "))
                        .font(.caption)
                        .foregroundStyle(.secondary)
                } else if model.webPlaybackURL != nil {
                    Text("Web playback")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }

            Divider()
                .overlay(.white.opacity(0.15))

            VStack(alignment: .leading, spacing: 6) {
                Text("UP NEXT")
                    .font(.caption.bold())
                    .tracking(1.2)
                    .foregroundStyle(.secondary)
                Text(model.status.next)
                    .font(.headline)
            }
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 20))
        .overlay {
            RoundedRectangle(cornerRadius: 20)
                .stroke(.white.opacity(0.12), lineWidth: 1)
        }
    }

    private var onNow: some View {
        VStack(alignment: .leading, spacing: 14) {
            SectionHeading(kicker: "ON NOW", title: "Currently playing")
            LazyVGrid(columns: [GridItem(.adaptive(minimum: 155), spacing: 12)], spacing: 12) {
                ForEach(Array(model.channels.prefix(8))) { channel in
                    Button {
                        Task { await model.tune(to: channel) }
                    } label: {
                        MiniNowCard(channel: channel, selected: model.selectedChannel == channel)
                    }
                    .buttonStyle(.plain)
                }
            }
        }
    }

    private var channelRail: some View {
        VStack(alignment: .leading, spacing: 14) {
            SectionHeading(kicker: "COMPLETE LINEUP", title: "All channels")
            ScrollView(.horizontal, showsIndicators: false) {
                LazyHStack(spacing: 14) {
                    ForEach(model.channels) { channel in
                        ChannelButton(
                            channel: channel,
                            selected: model.selectedChannel == channel
                        ) {
                            Task { await model.tune(to: channel) }
                        }
                    }
                }
            }
        }
    }
}

struct SectionHeading: View {
    let kicker: String
    let title: String

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(kicker)
                .font(.caption.bold())
                .tracking(1.6)
                .foregroundStyle(.orange)
            Text(title)
                .font(.title2.bold())
        }
    }
}

private struct MiniNowCard: View {
    let channel: Channel
    let selected: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("CH \(channel.displayNumber)")
                    .font(.caption2.monospacedDigit().bold())
                    .foregroundStyle(.orange.opacity(0.9))
                Spacer()
                Circle()
                    .fill(selected ? .green : .red)
                    .frame(width: 8, height: 8)
            }

            Image(systemName: channel.symbolName)
                .font(.title2.bold())
                .frame(width: 38, height: 38)
                .background(.black.opacity(0.20), in: RoundedRectangle(cornerRadius: 11))

            Text(channel.name)
                .font(.headline)
                .lineLimit(2)
            Text(channel.category)
                .font(.caption)
                .foregroundStyle(.white.opacity(0.72))
        }
        .frame(maxWidth: .infinity, minHeight: 142, alignment: .leading)
        .padding(14)
        .background(
            LinearGradient(colors: channel.gradientColors, startPoint: .topLeading, endPoint: .bottomTrailing),
            in: RoundedRectangle(cornerRadius: 18)
        )
        .overlay {
            RoundedRectangle(cornerRadius: 18)
                .stroke(selected ? .white.opacity(0.72) : .white.opacity(0.13), lineWidth: selected ? 2 : 1)
        }
        .foregroundStyle(.white)
    }
}
