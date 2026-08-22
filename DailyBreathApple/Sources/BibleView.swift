import SwiftUI
import UIKit

struct BibleView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("bibleLastBookCode") private var lastBookCode = "GEN"
    @AppStorage("bibleLastChapter") private var lastChapter = 1
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @State private var searchText = ""
    @State private var searchResults: [BibleVerse] = []

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            if store.isBibleLoading {
                HStack {
                    Spacer()
                    ProgressView("Loading Bible…")
                    Spacer()
                }
            } else if store.bibleLibrary.books.isEmpty {
                ContentUnavailableView(
                    "Bible Unavailable",
                    systemImage: "book.closed",
                    description: Text("The local Bible text could not be loaded.")
                )
            } else if searchText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                continueReadingSection
                favoritesSection
                testamentSection("Old Testament")
                testamentSection("New Testament")
            } else {
                searchSection
            }
        }
        .navigationTitle("Bible")
        .searchable(text: $searchText, placement: .navigationBarDrawer(displayMode: .always), prompt: "Search verses")
        .task(id: searchText) {
            let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
            guard !query.isEmpty else {
                searchResults = []
                return
            }
            try? await Task.sleep(for: .milliseconds(250))
            guard !Task.isCancelled else { return }
            let library = store.bibleLibrary
            let results = await Task.detached(priority: .userInitiated) {
                library.search(query)
            }.value
            guard !Task.isCancelled else { return }
            searchResults = results
        }
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
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
                            .foregroundStyle(selectedTheme.accent)
                        Text(chapter.title)
                            .font(.largeTitle.weight(.bold))
                        Text(firstVerse.text)
                            .font(.body)
                            .foregroundStyle(.secondary)
                            .lineLimit(3)
                        Text("\(store.bibleLibrary.translation) • \(store.bibleLibrary.books.count) books • \(store.bibleLibrary.verseCount.formatted()) verses")
                            .font(.caption.bold())
                            .foregroundStyle(selectedTheme.primary)
                    }
                    .padding(.vertical, 8)
                }
            }
        }
    }

    private var favoriteVerses: [BibleVerse] {
        let ids = Set(store.bibleAnnotations.values.filter(\.isFavorite).map(\.verseID))
        guard !ids.isEmpty else { return [] }
        return store.bibleLibrary.books.flatMap { book in
            book.chapters.flatMap { chapter in
                chapter.verses.filter { ids.contains($0.id) }
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
                            .foregroundStyle(selectedTheme.accent)
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

    private var favoritesSection: some View {
        Section("Favorite Verses") {
            if favoriteVerses.isEmpty {
                Label("Tap and hold a verse to favorite it.", systemImage: "star")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(favoriteVerses.prefix(5)) { verse in
                    NavigationLink {
                        if let chapter = store.bibleLibrary.chapter(bookCode: verse.bookCode, number: verse.chapter) {
                            BibleChapterView(chapter: chapter, highlightedVerseID: verse.id)
                        }
                    } label: {
                        VStack(alignment: .leading, spacing: 5) {
                            Text(verse.reference)
                                .font(.headline)
                                .foregroundStyle(selectedTheme.primary)
                            Text(verse.text)
                                .font(.caption)
                                .foregroundStyle(.secondary)
                                .lineLimit(2)
                        }
                    }
                }
                ForEach(store.favoriteCollections, id: \.self) { collection in
                    NavigationLink {
                        FavoriteCollectionView(name: collection)
                    } label: {
                        Label(collection, systemImage: "folder.fill")
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
                                .foregroundStyle(selectedTheme.primary)
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

private struct FavoriteCollectionView: View {
    @EnvironmentObject private var store: DailyBreathStore
    let name: String

    private var verses: [BibleVerse] {
        let ids = Set(store.bibleAnnotations.values.filter { $0.collections.contains(name) }.map(\.verseID))
        return store.bibleLibrary.books.flatMap { book in
            book.chapters.flatMap { chapter in chapter.verses.filter { ids.contains($0.id) } }
        }
    }

    var body: some View {
        List(verses) { verse in
            NavigationLink {
                if let chapter = store.bibleLibrary.chapter(bookCode: verse.bookCode, number: verse.chapter) {
                    BibleChapterView(chapter: chapter, highlightedVerseID: verse.id)
                }
            } label: {
                VStack(alignment: .leading, spacing: 5) {
                    Text(verse.reference).font(.headline)
                    Text(verse.text).font(.caption).foregroundStyle(.secondary).lineLimit(3)
                }
            }
        }
        .navigationTitle(name)
    }
}

private struct BibleBookView: View {
    let book: BibleBook
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 8) {
                    Text(book.testament)
                        .font(.caption.bold())
                        .foregroundStyle(selectedTheme.accent)
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
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }
}

private struct BibleChapterView: View {
    @EnvironmentObject private var store: DailyBreathStore
    let chapter: BibleChapter
    var highlightedVerseID: BibleVerse.ID?
    @AppStorage("bibleLastBookCode") private var lastBookCode = "GEN"
    @AppStorage("bibleLastChapter") private var lastChapter = 1
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @State private var noteVerse: BibleVerse?
    @State private var noteDraft = ""
    @State private var collectionVerse: BibleVerse?
    @State private var newCollectionName = ""

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        ScrollViewReader { proxy in
            List {
                Section {
                    VStack(alignment: .leading, spacing: 8) {
                        Text(chapter.bookName)
                            .font(.caption.bold())
                            .foregroundStyle(selectedTheme.accent)
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
                                .foregroundStyle(selectedTheme.accent)
                                .frame(width: 28, alignment: .trailing)
                            Text(verse.text)
                                .font(.system(.body, design: .serif))
                                .textSelection(.enabled)
                            Spacer(minLength: 8)
                            if isFavorite(verse) {
                                Image(systemName: "star.fill")
                                    .font(.caption)
                                    .foregroundStyle(selectedTheme.accent)
                            }
                            if !(store.annotation(for: verse)?.note.isEmpty ?? true) {
                                Image(systemName: "note.text")
                                    .font(.caption)
                                    .foregroundStyle(selectedTheme.primary)
                            }
                        }
                        .padding(.vertical, 5)
                        .listRowBackground(rowBackground(for: verse))
                        .id(verse.id)
                        .contextMenu {
                            Button {
                                store.speakBibleVerse(verse)
                            } label: {
                                Label("Listen to Verse", systemImage: "speaker.wave.2")
                            }
                            Button {
                                store.toggleFavorite(verse)
                            } label: {
                                Label(isFavorite(verse) ? "Remove Favorite" : "Favorite Verse", systemImage: isFavorite(verse) ? "star.slash" : "star")
                            }
                            Menu("Highlight", systemImage: "highlighter") {
                                Button("Sunlight") { store.setHighlight("yellow", for: verse) }
                                Button("Sage") { store.setHighlight("green", for: verse) }
                                Button("Sky") { store.setHighlight("blue", for: verse) }
                                Button("Rose") { store.setHighlight("pink", for: verse) }
                                Button("Remove Highlight", role: .destructive) { store.setHighlight(nil, for: verse) }
                            }
                            Menu("Collections", systemImage: "folder") {
                                ForEach(store.favoriteCollections, id: \.self) { collection in
                                    Button(collection) { store.toggleVerse(verse, inCollection: collection) }
                                }
                                Button("New Collection…") {
                                    collectionVerse = verse
                                }
                            }
                            Button {
                                noteDraft = store.annotation(for: verse)?.note ?? ""
                                noteVerse = verse
                            } label: {
                                Label("Private Note", systemImage: "note.text")
                            }
                            Button {
                                UIPasteboard.general.string = shareText(for: verse)
                            } label: {
                                Label("Copy Verse", systemImage: "doc.on.doc")
                            }
                            ShareLink(item: shareText(for: verse)) {
                                Label("Share Verse", systemImage: "square.and.arrow.up")
                            }
                        }
                    }
                }
            }
            .navigationTitle(chapter.title)
            .toolbar {
                ToolbarItemGroup(placement: .topBarTrailing) {
                    Button { store.speakBibleChapter(chapter) } label: {
                        Label("Listen to Chapter", systemImage: "speaker.wave.2.fill")
                    }
                    Button { store.stopNarration() } label: {
                        Label("Stop Narration", systemImage: "stop.fill")
                    }
                }
            }
            .scrollContentBackground(.hidden)
            .background(DailyBreathThemeBackground(theme: selectedTheme))
            .onAppear {
                lastBookCode = chapter.bookCode
                lastChapter = chapter.number
                guard let highlightedVerseID else { return }
                proxy.scrollTo(highlightedVerseID, anchor: .center)
            }
            .sheet(item: $noteVerse) { verse in
                NavigationStack {
                    Form {
                        Section(verse.reference) { Text(verse.text).font(.system(.body, design: .serif)) }
                        Section("Private Note") { TextEditor(text: $noteDraft).frame(minHeight: 180) }
                    }
                    .navigationTitle("Verse Note")
                    .toolbar {
                        ToolbarItem(placement: .cancellationAction) { Button("Cancel") { noteVerse = nil } }
                        ToolbarItem(placement: .confirmationAction) {
                            Button("Save") {
                                store.setNote(noteDraft, for: verse)
                                noteVerse = nil
                            }
                        }
                    }
                }
            }
            .alert("New Favorite Collection", isPresented: Binding(
                get: { collectionVerse != nil },
                set: { if !$0 { collectionVerse = nil } }
            )) {
                TextField("Collection name", text: $newCollectionName)
                Button("Cancel", role: .cancel) { collectionVerse = nil }
                Button("Add") {
                    if let verse = collectionVerse { store.toggleVerse(verse, inCollection: newCollectionName) }
                    newCollectionName = ""
                    collectionVerse = nil
                }
            } message: {
                Text("Collections and notes stay private in your protected Daily Breath data.")
            }
        }
    }

    private func isFavorite(_ verse: BibleVerse) -> Bool {
        store.annotation(for: verse)?.isFavorite == true
    }

    private func rowBackground(for verse: BibleVerse) -> Color? {
        if verse.id == highlightedVerseID { return selectedTheme.accent.opacity(0.18) }
        switch store.annotation(for: verse)?.highlightColor {
        case "yellow": return Color.yellow.opacity(0.20)
        case "green": return Color.green.opacity(0.16)
        case "blue": return Color.blue.opacity(0.14)
        case "pink": return Color.pink.opacity(0.16)
        default: return nil
        }
    }

    private func shareText(for verse: BibleVerse) -> String {
        "\(verse.text) \(verse.reference)"
    }
}
