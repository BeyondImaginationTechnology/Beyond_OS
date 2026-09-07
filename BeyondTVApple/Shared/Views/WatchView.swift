import AVKit
import SwiftUI

struct WatchView: View {
    @EnvironmentObject private var model: AppModel
    private let playerAnchor = "watch-player"

    private var availableChannels: [Channel] {
        model.channels.filter(\.isAvailableOnCurrentPlatform)
    }

    var body: some View {
        NavigationStack {
            ScrollViewReader { scrollProxy in
                ScrollView {
                    VStack(alignment: .leading, spacing: 24) {
                        hero
                        player
                            .id(playerAnchor)
                        nowPlaying
                        channelRail
                    }
                    .padding()
                }
                .onChange(of: model.watchPlayerRequest) { _, _ in
                    centerPlayer(using: scrollProxy)
                }
                .onChange(of: model.selectedTab) { _, tab in
                    guard tab == .watch else { return }
                    centerPlayer(using: scrollProxy)
                }
                .task(id: model.watchPlayerRequest) {
                    // Also runs when Watch is recreated after a tab change.
                    centerPlayer(using: scrollProxy)
                }
            }
            .background(BeyondTVBackground().ignoresSafeArea())
            .toolbar {
                ToolbarItem(placement: .primaryAction) {
                    ThemeToggleButton()
                }
            }
        }
    }

    private func centerPlayer(using scrollProxy: ScrollViewProxy) {
        withAnimation(.easeInOut(duration: 0.28)) {
            scrollProxy.scrollTo(playerAnchor, anchor: .center)
        }
    }

    private var hero: some View {
        VStack(alignment: .leading, spacing: 18) {
            HStack(alignment: .center, spacing: 14) {
                header
            }

            VStack(alignment: .leading, spacing: 8) {
                Text(lineupLabel)
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

    private var lineupLabel: String {
        #if os(tvOS)
        "\(availableChannels.count) DIRECT-PLAYBACK CHANNELS · APPLE TV"
        #else
        "\(availableChannels.count) CHANNELS · INTERNAL PLAYBACK REVIEW"
        #endif
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

    private var channelRail: some View {
        VStack(alignment: .leading, spacing: 14) {
            SectionHeading(kicker: "COMPLETE LINEUP", title: "All channels")
            ScrollView(.horizontal, showsIndicators: false) {
                LazyHStack(spacing: 14) {
                    ForEach(availableChannels) { channel in
                        ChannelButton(
                            channel: channel,
                            selected: model.selectedChannel == channel
                        ) {
                            Task { await model.watch(channel: channel) }
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
