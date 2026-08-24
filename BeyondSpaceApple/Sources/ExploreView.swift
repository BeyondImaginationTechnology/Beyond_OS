import SwiftUI

struct ExploreView: View {
    private let topics = [
        ("Planets", "Discover worlds in our solar system and beyond.", "globe.americas.fill"),
        ("Missions", "Follow spacecraft that expand what humanity knows.", "paperplane.fill"),
        ("Galaxies", "Meet the immense structures that fill the universe.", "hurricane"),
        ("Space Tech", "See the instruments and ideas behind discovery.", "antenna.radiowaves.left.and.right")
    ]

    var body: some View {
        ZStack {
            SpaceBackground()
            ScrollView {
                LazyVStack(spacing: 14) {
                    ForEach(topics, id: \.0) { topic in
                        SpaceCard {
                            HStack(alignment: .top, spacing: 16) {
                                Image(systemName: topic.2)
                                    .font(.title2)
                                    .foregroundStyle(SpaceTheme.cyan)
                                    .frame(width: 44, height: 44)
                                    .accessibilityHidden(true)
                                VStack(alignment: .leading, spacing: 6) {
                                    Text(topic.0).font(.title3.bold())
                                    Text(topic.1).foregroundStyle(SpaceTheme.secondaryText)
                                }
                            }
                            .accessibilityElement(children: .combine)
                        }
                    }
                }
                .padding()
            }
        }
        .navigationTitle("Explore")
    }
}
