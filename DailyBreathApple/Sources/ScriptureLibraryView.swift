import SwiftUI

struct ScriptureLibraryView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @State private var searchText = ""
    @State private var searchResults: [SacredTextVerse] = []

    private var tradition: FaithTradition { FaithTradition(rawValue: traditionID) ?? .bible }
    private var theme: DailyBreathTheme { DailyBreathTheme(id: selectedThemeID) }
    private var library: SacredTextLibrary { store.scriptureLibrary(for: tradition) }

    var body: some View {
        List {
            Section {
                Picker("Faith tradition", selection: $traditionID) {
                    ForEach(FaithTradition.allCases) { item in
                        Label(item.name, systemImage: item.symbolName).tag(item.id)
                    }
                }
                .pickerStyle(.segmented)
            }

            if store.isBibleLoading {
                ProgressView("Loading sacred texts…")
                    .frame(maxWidth: .infinity)
            } else if library.books.isEmpty {
                ContentUnavailableView("Text unavailable", systemImage: "book.closed", description: Text("The local text could not be loaded."))
            } else if searchText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                overview
                ForEach(library.books) { book in
                    NavigationLink {
                        SacredTextBookView(book: book)
                    } label: {
                        HStack(spacing: 12) {
                            Image(systemName: tradition.symbolName)
                                .foregroundStyle(theme.accent)
                                .frame(width: 24)
                            VStack(alignment: .leading, spacing: 3) {
                                Text(book.name).font(.headline)
                                Text(book.subtitle + " · " + chapterLabel(book.chapters.count))
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                }
            } else {
                Section("\(searchResults.count) Results") {
                    if searchResults.isEmpty {
                        ContentUnavailableView.search(text: searchText)
                    } else {
                        ForEach(searchResults) { verse in
                            NavigationLink {
                                if let chapter = library.chapter(bookCode: verse.bookCode, number: verse.chapter) {
                                    SacredTextChapterView(chapter: chapter, highlightedVerseID: verse.id)
                                }
                            } label: {
                                VStack(alignment: .leading, spacing: 5) {
                                    Text(verse.reference).font(.headline).foregroundStyle(theme.primary)
                                    Text(verse.text).font(.caption).foregroundStyle(.secondary).lineLimit(3)
                                }
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle(tradition.libraryName)
        .searchable(text: $searchText, prompt: "Search \(tradition.name) verses")
        .task(id: "\(traditionID)|\(searchText)") {
            let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
            guard !query.isEmpty else { searchResults = []; return }
            try? await Task.sleep(for: .milliseconds(220))
            guard !Task.isCancelled else { return }
            let snapshot = library
            searchResults = await Task.detached(priority: .userInitiated) { snapshot.search(query) }.value
        }
        .onChange(of: traditionID) { _, value in
            searchText = ""
            searchResults = []
            let tradition = FaithTradition(rawValue: value) ?? .bible
            selectedThemeID = DailyBreathTheme.recommended(for: tradition).id
        }
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: theme))
    }

    private var overview: some View {
        Section {
            HStack(spacing: 16) {
                Image(tradition.guideAssetName)
                    .resizable()
                    .scaledToFill()
                    .frame(width: 92, height: 112)
                    .clipShape(RoundedRectangle(cornerRadius: 18))
                VStack(alignment: .leading, spacing: 6) {
                    Text("Explore with \(tradition.guideName)")
                        .font(.title2.weight(.black))
                    Text("\(library.books.count) \(tradition == .quran ? "surahs" : "books") · \(library.chapterCount) chapters · \(library.verseCount.formatted()) verses")
                        .font(.caption.bold())
                        .foregroundStyle(theme.primary)
                    Text(library.translation)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }
            .padding(.vertical, 6)
        }
    }

    private func chapterLabel(_ count: Int) -> String {
        tradition == .quran ? "\(count) surah" : "\(count) chapters"
    }
}

private struct SacredTextBookView: View {
    let book: SacredTextBook

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 7) {
                    Label(book.subtitle, systemImage: book.tradition.symbolName).font(.caption.bold())
                    Text(book.name).font(.largeTitle.weight(.black))
                    Text("\(book.chapters.count) \(book.tradition == .quran ? "surah" : "chapters") · \(book.verseCount.formatted()) verses")
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }
            Section(book.tradition == .quran ? "Surah" : "Chapters") {
                ForEach(book.chapters) { chapter in
                    NavigationLink {
                        SacredTextChapterView(chapter: chapter)
                    } label: {
                        HStack {
                            Text(book.tradition == .quran ? book.name : "Chapter \(chapter.number)")
                            Spacer()
                            Text("\(chapter.verses.count) verses").font(.caption).foregroundStyle(.secondary)
                        }
                    }
                }
            }
        }
        .navigationTitle(book.name)
    }
}

private struct SacredTextChapterView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    let chapter: SacredTextChapter
    var highlightedVerseID: SacredTextVerse.ID?

    private var theme: DailyBreathTheme { DailyBreathTheme(id: selectedThemeID) }

    var body: some View {
        ScrollViewReader { proxy in
            List {
                Section {
                    VStack(alignment: .leading, spacing: 7) {
                        Label(chapter.tradition.libraryName, systemImage: chapter.tradition.symbolName).font(.caption.bold())
                        Text(chapter.title).font(.largeTitle.weight(.black))
                        Text("\(chapter.verses.count) verses").foregroundStyle(.secondary)
                    }
                    .padding(.vertical, 8)
                }
                Section("Verses") {
                    ForEach(chapter.verses) { verse in
                        HStack(alignment: .firstTextBaseline, spacing: 10) {
                            Text("\(verse.verse)").font(.caption.bold()).foregroundStyle(theme.accent).frame(width: 30, alignment: .trailing)
                            Text(verse.text).font(.system(.body, design: .serif)).textSelection(.enabled)
                        }
                        .padding(.vertical, 5)
                        .id(verse.id)
                        .listRowBackground(verse.id == highlightedVerseID ? theme.accent.opacity(0.18) : nil)
                        .contextMenu {
                            Button { store.speakText("\(verse.text) \(verse.reference)") } label: {
                                Label("Listen", systemImage: "speaker.wave.2")
                            }
                            ShareLink(item: "\(verse.text) — \(verse.reference)") {
                                Label("Share Verse", systemImage: "square.and.arrow.up")
                            }
                        }
                    }
                }
            }
            .navigationTitle(chapter.title)
            .toolbar {
                Button {
                    store.speakText(chapter.verses.map { "Verse \($0.verse). \($0.text)" }.joined(separator: " "))
                } label: {
                    Label("Listen to Chapter", systemImage: "speaker.wave.2.fill")
                }
            }
            .onAppear {
                if let highlightedVerseID { proxy.scrollTo(highlightedVerseID, anchor: .center) }
            }
        }
    }
}
