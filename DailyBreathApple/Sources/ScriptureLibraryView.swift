import SwiftUI

struct ScriptureLibraryView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.forest.id
    @AppStorage("scriptureEdition.bible") private var bibleEditionID = ScriptureEdition.bibleEnglish.id
    @AppStorage("scriptureEdition.torah") private var torahEditionID = ScriptureEdition.torahHebrew.id
    @AppStorage("scriptureEdition.quran") private var quranEditionID = ScriptureEdition.quranArabic.id
    @State private var searchText = ""
    @State private var searchResults: [SacredTextVerse] = []

    private var tradition: FaithTradition { FaithTradition(rawValue: traditionID) ?? .bible }
    private var theme: DailyBreathTheme { DailyBreathTheme(id: selectedThemeID) }
    private var edition: ScriptureEdition {
        let saved: String
        switch tradition {
        case .bible: saved = bibleEditionID
        case .torah: saved = torahEditionID
        case .quran: saved = quranEditionID
        }
        let value = ScriptureEdition(rawValue: saved) ?? ScriptureEdition.defaultEdition(for: tradition)
        return value.tradition == tradition ? value : ScriptureEdition.defaultEdition(for: tradition)
    }
    private var library: SacredTextLibrary { store.scriptureLibrary(for: tradition, edition: edition) }
    private var editionBinding: Binding<String> {
        Binding {
            edition.id
        } set: { value in
            switch tradition {
            case .bible: bibleEditionID = value
            case .torah: torahEditionID = value
            case .quran: quranEditionID = value
            }
        }
    }

    var body: some View {
        List {
            Section {
                Picker("Faith tradition", selection: $traditionID) {
                    ForEach(FaithTradition.allCases) { item in
                        Label(item.name, systemImage: item.symbolName).tag(item.id)
                    }
                }
                .pickerStyle(.segmented)

                Picker("Language & edition", selection: editionBinding) {
                    ForEach(ScriptureEdition.options(for: tradition)) { item in
                        Text(item.displayName).tag(item.id)
                    }
                }
                .pickerStyle(.menu)
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
        .searchable(text: $searchText, prompt: "Search \(tradition.name) \(tradition.passageUnitPlural)")
        .task(id: "\(traditionID)|\(edition.id)|\(searchText)") {
            await store.loadScriptureEdition(edition)
            store.publishSelectedFaithContent()
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
            store.publishSelectedFaithContent()
        }
        .onChange(of: edition.id) { _, _ in
            searchText = ""
            searchResults = []
            store.publishSelectedFaithContent()
        }
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: theme))
    }

    private var overview: some View {
        Section {
            HStack(spacing: 16) {
                FaithGuidePortrait(tradition: tradition, width: 92, height: 112, cornerRadius: 18)
                VStack(alignment: .leading, spacing: 6) {
                    Text("Explore with \(tradition.guideName)")
                        .font(.title2.weight(.black))
                    Text(summaryText)
                        .font(.caption.bold())
                        .foregroundStyle(theme.primary)
                    Text(library.translation)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Text(edition.attribution)
                        .font(.caption2)
                        .foregroundStyle(.tertiary)
                }
            }
            .padding(.vertical, 6)
        }
    }

    private func chapterLabel(_ count: Int) -> String {
        tradition == .quran ? "\(count) surah" : "\(count) chapters"
    }

    private var summaryText: String {
        if tradition == .quran {
            return "\(library.books.count) surahs · \(library.verseCount.formatted()) ayahs"
        }
        return "\(library.books.count) books · \(library.chapterCount) chapters · \(library.verseCount.formatted()) \(tradition.passageUnitPlural)"
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
                    Text("\(book.chapters.count) \(book.tradition == .quran ? "surah" : "chapters") · \(book.verseCount.formatted()) \(book.tradition.passageUnitPlural)")
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
                            Text("\(chapter.verses.count) \(book.tradition.passageUnitPlural)").font(.caption).foregroundStyle(.secondary)
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
                        Text("\(chapter.verses.count) \(chapter.tradition.passageUnitPlural)").foregroundStyle(.secondary)
                    }
                    .padding(.vertical, 8)
                }
                Section(chapter.tradition.passageUnitPlural.capitalized) {
                    ForEach(chapter.verses) { verse in
                        HStack(alignment: .firstTextBaseline, spacing: 10) {
                            Text("\(verse.verse)").font(.caption.bold()).foregroundStyle(theme.accent).frame(width: 30, alignment: .trailing)
                            Text(verse.text)
                                .font(.system(.body, design: .serif))
                                .frame(maxWidth: .infinity, alignment: isRightToLeft(verse.text) ? .trailing : .leading)
                                .multilineTextAlignment(isRightToLeft(verse.text) ? .trailing : .leading)
                                .textSelection(.enabled)
                        }
                        .padding(.vertical, 5)
                        .id(verse.id)
                        .listRowBackground(verse.id == highlightedVerseID ? theme.accent.opacity(0.18) : nil)
                        .contextMenu {
                            Button { store.speakText("\(verse.text) \(verse.reference)") } label: {
                                Label("Listen", systemImage: "speaker.wave.2")
                            }
                            ShareLink(item: "\(verse.text) — \(verse.reference)") {
                                Label(shareLabel, systemImage: "square.and.arrow.up")
                            }
                        }
                    }
                }
            }
            .navigationTitle(chapter.title)
            .toolbar {
                Button {
                    store.speakText(chapter.verses.map { "\(spokenUnit) \($0.verse). \($0.text)" }.joined(separator: " "))
                } label: {
                    Label(listenLabel, systemImage: "speaker.wave.2.fill")
                }
            }
            .onAppear {
                if let highlightedVerseID { proxy.scrollTo(highlightedVerseID, anchor: .center) }
            }
        }
    }

    private var shareLabel: String {
        switch chapter.tradition {
        case .bible: "Share Verse"
        case .torah: "Share Passage"
        case .quran: "Share Ayah"
        }
    }

    private var spokenUnit: String {
        switch chapter.tradition {
        case .bible: "Verse"
        case .torah: "Pasuk"
        case .quran: "Ayah"
        }
    }

    private var listenLabel: String {
        chapter.tradition == .quran ? "Listen to Surah" : "Listen to Chapter"
    }

    private func isRightToLeft(_ text: String) -> Bool {
        text.unicodeScalars.contains { scalar in
            (0x0590...0x08FF).contains(Int(scalar.value))
        }
    }
}
