import SwiftUI

struct ChannelGuideView: View {
    @EnvironmentObject private var model: AppModel

    private var currentHour: Int {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(identifier: "America/Vancouver") ?? .current
        return calendar.component(.hour, from: Date())
    }

    private var rows: [GuideChannelRow] {
        model.channels.map { channel in
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
                                Task { await model.tune(to: row.channel) }
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
        VStack(alignment: .leading, spacing: 8) {
            SectionHeading(kicker: "FULL GUIDE · AMERICA/VANCOUVER", title: "Today’s schedule")
            Text("Every channel with its full schedule blocks. Tap a channel row to tune.")
                .font(.subheadline)
                .foregroundStyle(.secondary)
            if let loadedAt = model.guideItems.map(\.loadedAt).max() {
                Text("Updated \(loadedAt.formatted(date: .omitted, time: .shortened))")
                    .font(.caption.bold())
                    .foregroundStyle(.orange)
            }
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

    private var channel: Channel { row.channel }

    var body: some View {
        Button(action: tune) {
            VStack(alignment: .leading, spacing: 14) {
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

                    Image(systemName: selected ? "waveform.circle.fill" : "play.circle.fill")
                        .font(.title2)
                        .foregroundStyle(selected ? .orange : .secondary)
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
            .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 18))
            .overlay {
                RoundedRectangle(cornerRadius: 18)
                    .stroke(selected ? .orange.opacity(0.65) : .white.opacity(0.11), lineWidth: selected ? 2 : 1)
            }
        }
        .buttonStyle(.plain)
    }
}

private struct GuideBlockCard: View {
    let block: GuideBlock
    let isCurrent: Bool

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
                        .background(.orange, in: Capsule())
                }
            }
            .foregroundStyle(isCurrent ? .orange : .secondary)

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
        .background(isCurrent ? .orange.opacity(0.14) : .white.opacity(0.07), in: RoundedRectangle(cornerRadius: 14))
        .overlay {
            RoundedRectangle(cornerRadius: 14)
                .stroke(isCurrent ? .orange.opacity(0.8) : .white.opacity(0.10), lineWidth: isCurrent ? 1.5 : 1)
        }
    }
}
