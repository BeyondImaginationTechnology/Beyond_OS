import SwiftUI

struct BibleView: View {
    private let books = ["Genesis", "Psalms", "Proverbs", "Matthew", "Mark", "Luke", "John", "Romans"]

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 12) {
                    Label("Continue Reading", systemImage: "bookmark.fill")
                        .font(.caption.bold())
                        .foregroundStyle(Color.dailyGold)
                    Text("Psalm 46")
                        .font(.largeTitle.weight(.bold))
                    Text("God is our refuge and strength, a very present help in trouble.")
                        .foregroundStyle(.secondary)
                    Button {} label: {
                        Label("Open Chapter", systemImage: "play.circle.fill")
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(Color.dailyGreen)
                }
                .padding(.vertical, 8)
            }
            Section("Books") {
                ForEach(books, id: \.self) { book in
                    Label(book, systemImage: "book")
                }
            }
        }
        .navigationTitle("Bible")
    }
}
