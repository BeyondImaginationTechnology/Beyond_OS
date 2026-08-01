import AVKit
import SwiftUI

struct WatchView: View {
    @EnvironmentObject private var model: AppModel

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 24) {
                header
                player
                nowPlaying
                channelRail
            }
            .padding()
        }
        .background(background.ignoresSafeArea())
    }

    private var header: some View {
        HStack(alignment: .center, spacing: 14) {
            Image("BeyondTVLogo")
                .resizable()
                .scaledToFit()
                .frame(width: 52, height: 52)
                .clipShape(RoundedRectangle(cornerRadius: 12))
                .accessibilityHidden(true)

            VStack(alignment: .leading, spacing: 4) {
                Text("BEYOND TV")
                    .font(.caption.bold())
                    .tracking(2)
                    .foregroundStyle(.purple)
                Text(model.selectedChannel?.name ?? "Live television")
                    .font(.largeTitle.bold())
            }
            Spacer()
            Label("LIVE", systemImage: "dot.radiowaves.left.and.right")
                .font(.caption.bold())
                .padding(.horizontal, 12)
                .padding(.vertical, 8)
                .background(.red.opacity(0.18), in: Capsule())
                .foregroundStyle(.red)
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
            RoundedRectangle(cornerRadius: 22)
                .stroke(.white.opacity(0.12), lineWidth: 1)
        }
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
        HStack(alignment: .top, spacing: 24) {
            VStack(alignment: .leading, spacing: 6) {
                Text(model.status.label)
                    .font(.caption.bold())
                    .foregroundStyle(.secondary)
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
            Spacer()
            VStack(alignment: .trailing, spacing: 6) {
                Text("UP NEXT")
                    .font(.caption.bold())
                    .foregroundStyle(.secondary)
                Text(model.status.next)
                    .multilineTextAlignment(.trailing)
            }
        }
        .padding(20)
        .background(.thinMaterial, in: RoundedRectangle(cornerRadius: 18))
    }

    private var channelRail: some View {
        VStack(alignment: .leading, spacing: 14) {
            Text("Channels")
                .font(.title2.bold())
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

    private var background: some View {
        LinearGradient(
            colors: [Color(red: 0.03, green: 0.04, blue: 0.09), Color(red: 0.10, green: 0.04, blue: 0.16)],
            startPoint: .top,
            endPoint: .bottom
        )
    }
}
