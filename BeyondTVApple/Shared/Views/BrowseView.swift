import SwiftUI

struct BrowseView: View {
    @EnvironmentObject private var model: AppModel
    @State private var selectedCategory = "All"

    private var categories: [String] {
        ["All"] + Array(Set(model.channels.map(\.category))).sorted()
    }

    private var filteredChannels: [Channel] {
        guard selectedCategory != "All" else { return model.channels }
        return model.channels.filter { $0.category == selectedCategory }
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 24) {
                    hero
                    categoryPicker
                    channelGrid
                }
                .padding()
            }
            .background(BeyondTVBackground().ignoresSafeArea())
            .navigationTitle("Browse")
        }
    }

    private var hero: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("BROWSE LIBRARY")
                .font(.caption.bold())
                .tracking(2)
                .foregroundStyle(.orange)
            Text("Cartoons, movies, anime, learning, wellness, sports, and mystery in one lineup.")
                .font(.title.bold())
                .lineLimit(3)
            Text("\(model.channels.count) free channels · schedule synced to Vancouver time")
                .font(.subheadline)
                .foregroundStyle(.secondary)
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 24))
        .overlay {
            RoundedRectangle(cornerRadius: 24)
                .stroke(.white.opacity(0.12), lineWidth: 1)
        }
    }

    private var categoryPicker: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 10) {
                ForEach(categories, id: \.self) { category in
                    Button {
                        selectedCategory = category
                    } label: {
                        Text(category.uppercased())
                            .font(.caption.bold())
                            .tracking(0.8)
                            .padding(.horizontal, 14)
                            .padding(.vertical, 10)
                            .background(
                                selectedCategory == category
                                    ? AnyShapeStyle(LinearGradient(colors: [.orange, .pink, .purple], startPoint: .topLeading, endPoint: .bottomTrailing))
                                    : AnyShapeStyle(.white.opacity(0.10)),
                                in: Capsule()
                            )
                    }
                    .buttonStyle(.plain)
                }
            }
        }
    }

    private var channelGrid: some View {
        LazyVGrid(columns: [GridItem(.adaptive(minimum: 165), spacing: 14)], spacing: 14) {
            ForEach(filteredChannels) { channel in
                Button {
                    Task { await model.tune(to: channel) }
                } label: {
                    BrowseChannelCard(channel: channel, selected: model.selectedChannel == channel)
                }
                .buttonStyle(.plain)
            }
        }
    }
}

private struct BrowseChannelCard: View {
    let channel: Channel
    let selected: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Image(systemName: channel.symbolName)
                    .font(.title2.bold())
                    .frame(width: 42, height: 42)
                    .background(.black.opacity(0.22), in: RoundedRectangle(cornerRadius: 12))
                Spacer()
                Text("CH \(channel.displayNumber)")
                    .font(.caption2.monospacedDigit().bold())
                    .padding(.horizontal, 8)
                    .padding(.vertical, 6)
                    .background(.black.opacity(0.28), in: Capsule())
            }

            Spacer(minLength: 18)

            Text(channel.category.uppercased())
                .font(.caption2.bold())
                .tracking(1)
                .foregroundStyle(.orange.opacity(0.9))
            Text(channel.name)
                .font(.headline)
                .lineLimit(2)
            Text(channel.description)
                .font(.caption)
                .foregroundStyle(.white.opacity(0.76))
                .lineLimit(3)
        }
        .frame(maxWidth: .infinity, minHeight: 210, alignment: .leading)
        .padding(16)
        .background(
            LinearGradient(colors: channel.gradientColors, startPoint: .topLeading, endPoint: .bottomTrailing),
            in: RoundedRectangle(cornerRadius: 22)
        )
        .overlay(alignment: .topTrailing) {
            if selected {
                Image(systemName: "waveform.circle.fill")
                    .font(.title2)
                    .foregroundStyle(.white)
                    .padding(12)
            }
        }
        .overlay {
            RoundedRectangle(cornerRadius: 22)
                .stroke(selected ? .white.opacity(0.75) : .white.opacity(0.14), lineWidth: selected ? 2 : 1)
        }
        .foregroundStyle(.white)
    }
}
