import SwiftUI

struct BibleView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("bibleLastBookCode") private var lastBookCode = "GEN"
    @AppStorage("bibleLastChapter") private var lastChapter = 1
    @State private var searchText = ""

    private var searchResults: [BibleVerse] {
        store.bibleLibrary.search(searchText)
    }

    var body: some View {
        List {
            if store.bibleLibrary.books.isEmpty {
                ContentUnavailableView(
                    "Bible Unavailable",
                    systemImage: "book.closed",
                    description: Text("The local Bible text could not be loaded.")
                )
            } else if searchText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                continueReadingSection
                testamentSection("Old Testament")
                testamentSection("New Testament")
            } else {
                searchSection
            }
        }
        .navigationTitle("Bible")
        .searchable(text: $searchText, placement: .navigationBarDrawer(displayMode: .always), prompt: "Search verses")
    }

    private var continueReadingSection: some View {
        Section {
            if let chapter = store.bibleLibrary.chapter(bookCode: lastBookCode, number: lastChapter)
                ?? store.bibleLibrary.chapter(bookCode: "GEN", number: 1),
               let firstVerse = chapter.verses.first {
                NavigationLink {
                    BibleChapterView(chapter: chapter)
                } label: {
                    VStack(alignment: .leading, spacing: 10) {
                        Label(chapter.bookCode == "GEN" && chapter.number == 1 ? "Start Reading" : "Continue Reading", systemImage: "bookmark.fill")
                            .font(.caption.bold())
                            .foregroundStyle(Color.dailyGold)
                        Text(chapter.title)
                            .font(.largeTitle.weight(.bold))
                        Text(firstVerse.text)
                            .font(.body)
                            .foregroundStyle(.secondary)
                            .lineLimit(3)
                        Text("\(store.bibleLibrary.translation) • \(store.bibleLibrary.books.count) books • \(store.bibleLibrary.verseCount.formatted()) verses")
                            .font(.caption.bold())
                            .foregroundStyle(Color.dailyGreen)
                    }
                    .padding(.vertical, 8)
                }
            }
        }
    }

    private func testamentSection(_ testament: String) -> some View {
        Section(testament) {
            ForEach(store.bibleLibrary.books.filter { $0.testament == testament }) { book in
                NavigationLink {
                    BibleBookView(book: book)
                } label: {
                    HStack(spacing: 12) {
                        Image(systemName: "book.closed.fill")
                            .foregroundStyle(Color.dailyGold)
                            .frame(width: 24)
                        VStack(alignment: .leading, spacing: 3) {
                            Text(book.name)
                                .font(.headline)
                            Text("\(book.chapters.count) chapters")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                    }
                }
            }
        }
    }

    private var searchSection: some View {
        Section {
            if searchResults.isEmpty {
                ContentUnavailableView.search(text: searchText)
            } else {
                ForEach(searchResults) { verse in
                    NavigationLink {
                        if let chapter = store.bibleLibrary.chapter(bookCode: verse.bookCode, number: verse.chapter) {
                            BibleChapterView(chapter: chapter, highlightedVerseID: verse.id)
                        }
                    } label: {
                        VStack(alignment: .leading, spacing: 6) {
                            Text(verse.reference)
                                .font(.headline)
                                .foregroundStyle(Color.dailyGreen)
                            Text(verse.text)
                                .font(.body)
                                .foregroundStyle(.primary)
                                .lineLimit(3)
                        }
                        .padding(.vertical, 4)
                    }
                }
            }
        } header: {
            Text("\(searchResults.count) Results")
        } footer: {
            if searchResults.count == 80 {
                Text("Showing the first 80 matches.")
            }
        }
    }
}

private struct BibleBookView: View {
    let book: BibleBook

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 8) {
                    Text(book.testament)
                        .font(.caption.bold())
                        .foregroundStyle(Color.dailyGold)
                    Text(book.name)
                        .font(.largeTitle.weight(.bold))
                    Text("\(book.chapters.count) chapters • \(book.verseCount.formatted()) verses")
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }

            Section("Chapters") {
                ForEach(book.chapters) { chapter in
                    NavigationLink {
                        BibleChapterView(chapter: chapter)
                    } label: {
                        HStack {
                            Text("Chapter \(chapter.number)")
                            Spacer()
                            Text("\(chapter.verses.count) verses")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                    }
                }
            }
        }
        .navigationTitle(book.name)
    }
}

private struct BibleChapterView: View {
    let chapter: BibleChapter
    var highlightedVerseID: BibleVerse.ID?
    @AppStorage("bibleLastBookCode") private var lastBookCode = "GEN"
    @AppStorage("bibleLastChapter") private var lastChapter = 1

    var body: some View {
        ScrollViewReader { proxy in
            List {
                Section {
                    VStack(alignment: .leading, spacing: 8) {
                        Text(chapter.bookName)
                            .font(.caption.bold())
                            .foregroundStyle(Color.dailyGold)
                        Text("Chapter \(chapter.number)")
                            .font(.largeTitle.weight(.bold))
                        Text("\(chapter.verses.count) verses")
                            .foregroundStyle(.secondary)
                    }
                    .padding(.vertical, 8)
                }

                Section("Verses") {
                    ForEach(chapter.verses) { verse in
                        HStack(alignment: .firstTextBaseline, spacing: 10) {
                            Text("\(verse.verse)")
                                .font(.caption.bold())
                                .foregroundStyle(Color.dailyGold)
                                .frame(width: 28, alignment: .trailing)
                            Text(verse.text)
                                .font(.system(.body, design: .serif))
                                .textSelection(.enabled)
                        }
                        .padding(.vertical, 5)
                        .listRowBackground(verse.id == highlightedVerseID ? Color.dailyGold.opacity(0.16) : nil)
                        .id(verse.id)
                    }
                }
            }
            .navigationTitle(chapter.title)
            .onAppear {
                lastBookCode = chapter.bookCode
                lastChapter = chapter.number
                guard let highlightedVerseID else { return }
                proxy.scrollTo(highlightedVerseID, anchor: .center)
            }
        }
    }
}
