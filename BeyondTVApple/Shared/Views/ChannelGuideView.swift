import SwiftUI

struct ChannelGuideView: View {
    @EnvironmentObject private var model: AppModel

    private var currentHour: Int {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(identifier: "America/Vancouver") ?? .current
        return calendar.component(.hour, from: Date())
    }

    private var rows: [GuideChannelRow] {
        model.channels.filter(\.isAvailableOnCurrentPlatform).map { channel in
            let guideItem = model.guideItems.first(where: { $0.channel == channel })
            let blocks = model.guideSchedule[channel.slug] ?? fallbackBlocks(for: channel, guideItem: guideItem)
            return GuideChannelRow(channel: channel, guideItem: guideItem, blocks: blocks)
        }
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 18) {
                    header

                    LazyVStack(spacing: 14) {
                        ForEach(rows) { row in
                            FullGuideRow(
                                row: row,
                                selected: model.selectedChannel == row.channel,
                                currentHour: currentHour
                            ) {
                                Task { await model.watch(channel: row.channel) }
                            }
                        }
                    }
                }
                .padding()
            }
            .background(BeyondTVBackground().ignoresSafeArea())
            .navigationTitle("Full Guide")
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
                if model.channels.isEmpty || model.isGuideLoading && model.guideItems.isEmpty && model.guideSchedule.isEmpty {
                    ProgressView("Loading full guide…")
                }
            }
            .task {
                if model.guideItems.isEmpty || model.guideSchedule.isEmpty {
                    await model.refreshGuide()
                }
            }
        }
    }

    private var header: some View {
        VStack(alignment: .leading, spacing: 16) {
            HStack(alignment: .top, spacing: 12) {
                Image(systemName: "rectangle.connected.to.line.below")
                    .font(.title2.bold())
                    .foregroundStyle(.white)
                    .frame(width: 46, height: 46)
                    .background(
                        LinearGradient(colors: [.cyan, .blue], startPoint: .topLeading, endPoint: .bottomTrailing),
                        in: RoundedRectangle(cornerRadius: 14)
                    )
                VStack(alignment: .leading, spacing: 4) {
                    Text("LIVE CONTROL ROOM")
                        .font(.caption.bold())
                        .tracking(1.5)
                        .foregroundStyle(.cyan)
                    Text("Today’s schedule")
                        .font(.title.bold())
                }
                Spacer()
                TimelineView(.periodic(from: .now, by: 60)) { _ in
                    Text(Date.now, format: .dateTime.hour().minute())
                        .font(.headline.monospacedDigit())
                        .foregroundStyle(.secondary)
                }
            }

            Text("Tap a channel to tune. Each row keeps the live program, what is next, and its timeline together.")
                .font(.subheadline)
                .foregroundStyle(.secondary)

            HStack(spacing: 9) {
                Label("\(rows.count) channels", systemImage: "play.tv.fill")
                Label("Vancouver", systemImage: "clock")
                if let loadedAt = model.guideItems.map(\.loadedAt).max() {
                    Label("Updated \(loadedAt.formatted(date: .omitted, time: .shortened))", systemImage: "arrow.clockwise")
                }
            }
            .font(.caption.bold())
            .foregroundStyle(.secondary)
            #if os(tvOS)
            Text("Web-player channels are omitted on Apple TV. This guide shows direct MP4/HLS playback only.")
                .font(.caption)
                .foregroundStyle(.cyan)
            #endif
        }
        .padding(18)
        .background(.regularMaterial, in: RoundedRectangle(cornerRadius: 22))
        .overlay {
            RoundedRectangle(cornerRadius: 22)
                .stroke(.cyan.opacity(0.24), lineWidth: 1)
        }
    }

    private func fallbackBlocks(for channel: Channel, guideItem: GuideItem?) -> [GuideBlock] {
        let now = guideItem?.status.now ?? channel.description
        let next = guideItem?.status.next ?? "More on \(channel.name)"
        return [
            GuideBlock(start: 0, end: 12, icon: guideItem?.currentIcon ?? channel.icon, title: now, lineup: guideItem?.currentLineup),
            GuideBlock(start: 12, end: 24, icon: channel.icon, title: next, lineup: guideItem?.nextLineup ?? channel.description)
        ]
    }
}

private struct GuideChannelRow: Identifiable {
    let channel: Channel
    let guideItem: GuideItem?
    let blocks: [GuideBlock]

    var id: String { channel.id }
}

private struct FullGuideRow: View {
    let row: GuideChannelRow
    let selected: Bool
    let currentHour: Int
    let tune: () -> Void
    @Environment(\.colorScheme) private var colorScheme

    private var channel: Channel { row.channel }
    private var currentBlock: GuideBlock? {
        row.blocks.first { $0.contains(hour: currentHour) } ?? row.blocks.first
    }
    private var nextBlock: GuideBlock? {
        guard let currentBlock,
              let index = row.blocks.firstIndex(of: currentBlock),
              row.blocks.count > 1 else { return nil }
        return row.blocks[(index + 1) % row.blocks.count]
    }
    private var rowColors: [Color] {
        let opacity = colorScheme == .light ? 0.18 : 0.34
        return channel.gradientColors.map { $0.opacity(opacity) } + [Color.primary.opacity(colorScheme == .light ? 0.025 : 0.08)]
    }

    var body: some View {
        Button(action: tune) {
            VStack(alignment: .leading, spacing: 16) {
                HStack(spacing: 12) {
                    ZStack {
                        RoundedRectangle(cornerRadius: 14)
                            .fill(LinearGradient(colors: channel.gradientColors, startPoint: .topLeading, endPoint: .bottomTrailing))
                        Image(systemName: channel.symbolName)
                            .font(.title3.bold())
                    }
                    .frame(width: 54, height: 54)

                    VStack(alignment: .leading, spacing: 4) {
                        Text("CH \(channel.displayNumber) · \(channel.name)")
                            .font(.headline)
                            .lineLimit(1)
                        Text(row.guideItem?.status.label ?? channel.category)
                            .font(.caption.bold())
                            .foregroundStyle(.secondary)
                            .lineLimit(1)
                    }

                    Spacer()

                    Text(selected ? "ON AIR" : "TUNE")
                        .font(.caption2.bold())
                        .tracking(0.8)
                        .foregroundStyle(selected ? .white : .cyan)
                        .padding(.horizontal, 9)
                        .padding(.vertical, 6)
                        .background(selected ? Color.cyan : Color.cyan.opacity(0.13), in: Capsule())
                }

                if let currentBlock {
                    VStack(alignment: .leading, spacing: 5) {
                        Text("NOW")
                            .font(.caption2.bold())
                            .tracking(1.1)
                            .foregroundStyle(.cyan)
                        Text([currentBlock.icon, currentBlock.title].compactMap { $0 }.joined(separator: " "))
                            .font(.title3.bold())
                            .lineLimit(2)
                        if let lineup = currentBlock.lineup, !lineup.isEmpty {
                            Text(lineup)
                                .font(.caption)
                                .foregroundStyle(.secondary)
                                .lineLimit(2)
                        }
                        if let nextBlock {
                            Text("Next · \(nextBlock.title)")
                                .font(.caption.bold())
                                .foregroundStyle(.secondary)
                                .lineLimit(1)
                        }
                    }
                    .padding(14)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .background(Color.cyan.opacity(colorScheme == .light ? 0.12 : 0.10), in: RoundedRectangle(cornerRadius: 15))
                }

                ScrollView(.horizontal, showsIndicators: false) {
                    HStack(spacing: 10) {
                        ForEach(row.blocks) { block in
                            GuideBlockCard(block: block, isCurrent: block.contains(hour: currentHour))
                        }
                    }
                    .padding(.bottom, 2)
                }
            }
            .padding(14)
            .background(
                LinearGradient(colors: rowColors, startPoint: .topLeading, endPoint: .bottomTrailing),
                in: RoundedRectangle(cornerRadius: 20)
            )
            .overlay {
                RoundedRectangle(cornerRadius: 20)
                    .stroke(selected ? .cyan.opacity(0.88) : Color.primary.opacity(colorScheme == .light ? 0.12 : 0.16), lineWidth: selected ? 2 : 1)
            }
        }
        .buttonStyle(.plain)
    }
}

private struct GuideBlockCard: View {
    let block: GuideBlock
    let isCurrent: Bool
    @Environment(\.colorScheme) private var colorScheme

    var body: some View {
        VStack(alignment: .leading, spacing: 7) {
            HStack(spacing: 6) {
                Text(block.timeLabel)
                    .font(.caption2.monospacedDigit().bold())
                if isCurrent {
                    Text("NOW")
                        .font(.caption2.bold())
                        .foregroundStyle(.black)
                        .padding(.horizontal, 6)
                        .padding(.vertical, 3)
                        .background(.cyan, in: Capsule())
                }
            }
            .foregroundStyle(isCurrent ? .cyan : .secondary)

            Text([block.icon, block.title].compactMap { $0 }.joined(separator: " "))
                .font(.subheadline.bold())
                .lineLimit(2)
            Text(block.lineup ?? "Featured presentation")
                .font(.caption)
                .foregroundStyle(.secondary)
                .lineLimit(2)
        }
        .frame(width: 178, height: 112, alignment: .topLeading)
        .padding(11)
        .background(
            isCurrent ? Color.cyan.opacity(colorScheme == .light ? 0.17 : 0.14) : Color.primary.opacity(colorScheme == .light ? 0.055 : 0.07),
            in: RoundedRectangle(cornerRadius: 14)
        )
        .overlay {
            RoundedRectangle(cornerRadius: 14)
                .stroke(isCurrent ? .cyan.opacity(0.8) : Color.primary.opacity(colorScheme == .light ? 0.10 : 0.14), lineWidth: isCurrent ? 1.5 : 1)
        }
    }
}
