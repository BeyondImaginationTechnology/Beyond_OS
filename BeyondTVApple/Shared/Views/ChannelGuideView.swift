import SwiftUI

struct ChannelGuideView: View {
    @EnvironmentObject private var model: AppModel

    private var rows: [GuideItem] {
        if !model.guideItems.isEmpty { return model.guideItems }
        return model.channels.map {
            GuideItem(
                channel: $0,
                status: ChannelStatus(now: "Loading live schedule…", next: "Up next", label: "LIVE · VANCOUVER", sourceKey: ""),
                currentIcon: $0.icon,
                currentLineup: $0.description,
                nextLineup: nil,
                loadedAt: Date()
            )
        }
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 18) {
                    header

                    LazyVStack(spacing: 12) {
                        ForEach(rows) { item in
                            Button {
                                Task { await model.tune(to: item.channel) }
                            } label: {
                                ChannelGuideRow(item: item, selected: model.selectedChannel == item.channel)
                            }
                            .buttonStyle(.plain)
                        }
                    }
                }
                .padding()
            }
            .background(BeyondTVBackground().ignoresSafeArea())
            .navigationTitle("Live Guide")
            .toolbar {
                ToolbarItem(placement: .primaryAction) {
                    HStack {
                        Button {
                            Task { await model.refreshGuide() }
                        } label: {
                            Image(systemName: "arrow.clockwise")
                        }
                        .disabled(model.isGuideLoading)

                        ThemeToggleButton()
                    }
                }
            }
            .overlay {
                if model.channels.isEmpty || model.isGuideLoading && model.guideItems.isEmpty {
                    ProgressView("Loading live guide…")
                }
            }
            .task {
                if model.guideItems.isEmpty {
                    await model.refreshGuide()
                }
            }
        }
    }

    private var header: some View {
        VStack(alignment: .leading, spacing: 8) {
            SectionHeading(kicker: "REAL GUIDE · AMERICA/VANCOUVER", title: "What’s on now")
            Text("Live program data from each channel endpoint, including current and up-next blocks.")
                .font(.subheadline)
                .foregroundStyle(.secondary)
            if let loadedAt = model.guideItems.map(\.loadedAt).max() {
                Text("Updated \(loadedAt.formatted(date: .omitted, time: .shortened))")
                    .font(.caption.bold())
                    .foregroundStyle(.orange)
            }
        }
    }
}

private struct ChannelGuideRow: View {
    let item: GuideItem
    let selected: Bool

    private var channel: Channel { item.channel }

    var body: some View {
        VStack(alignment: .leading, spacing: 14) {
            HStack(spacing: 14) {
                ZStack {
                    RoundedRectangle(cornerRadius: 16)
                        .fill(LinearGradient(colors: channel.gradientColors, startPoint: .topLeading, endPoint: .bottomTrailing))
                    Image(systemName: channel.symbolName)
                        .font(.title2.bold())
                }
                .frame(width: 62, height: 62)

                VStack(alignment: .leading, spacing: 5) {
                    HStack(spacing: 8) {
                        Text("CH \(channel.displayNumber)")
                            .font(.caption.bold())
                            .foregroundStyle(.orange)
                        Text(item.status.label)
                            .font(.caption2.bold())
                            .lineLimit(1)
                            .padding(.horizontal, 7)
                            .padding(.vertical, 4)
                            .background(.white.opacity(0.11), in: Capsule())
                    }
                    Text(channel.name)
                        .font(.headline)
                    Text(channel.category)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }

                Spacer()

                Image(systemName: selected ? "waveform.circle.fill" : "play.circle.fill")
                    .font(.title2)
                    .foregroundStyle(selected ? .orange : .secondary)
            }

            VStack(alignment: .leading, spacing: 7) {
                Label {
                    Text([item.currentIcon, item.status.now].compactMap { $0 }.joined(separator: " "))
                        .font(.headline)
                        .lineLimit(2)
                } icon: {
                    Text("NOW")
                        .font(.caption2.bold())
                        .foregroundStyle(.black)
                        .padding(.horizontal, 7)
                        .padding(.vertical, 4)
                        .background(.orange, in: Capsule())
                }

                if let lineup = item.currentLineup, !lineup.isEmpty {
                    Text(lineup)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                        .lineLimit(2)
                }
            }

            Divider()
                .overlay(.white.opacity(0.13))

            VStack(alignment: .leading, spacing: 5) {
                Text("UP NEXT")
                    .font(.caption.bold())
                    .tracking(1)
                    .foregroundStyle(.secondary)
                Text(item.status.next)
                    .font(.subheadline.bold())
                    .lineLimit(2)
                if let nextLineup = item.nextLineup, !nextLineup.isEmpty {
                    Text(nextLineup)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                        .lineLimit(2)
                }
            }
        }
        .padding(14)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 20))
        .overlay {
            RoundedRectangle(cornerRadius: 20)
                .stroke(selected ? .orange.opacity(0.65) : .white.opacity(0.11), lineWidth: selected ? 2 : 1)
        }
    }
}
