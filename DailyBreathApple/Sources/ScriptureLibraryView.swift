import SwiftUI

struct ScriptureLibraryView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id
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
    private var lastReadMatches: Bool {
        UserDefaults.standard.string(forKey: ScriptureResumeKeys.tradition) == tradition.rawValue
            && UserDefaults.standard.string(forKey: ScriptureResumeKeys.edition) == edition.rawValue
    }
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

            Section("Daily Breath Chat") {
                Text("Ask your selected faith guide about Daily Breath or this sacred-text tradition.")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                scriptureGuideLink(.init(tradition: tradition))
            }

            if store.isBibleLoading {
                ProgressView("Loading sacred texts…")
                    .frame(maxWidth: .infinity)
            } else if library.books.isEmpty {
                ContentUnavailableView {
                    Label("Text unavailable", systemImage: "book.closed")
                } description: {
                    Text("The local text could not be loaded. Choose another edition or try loading it again.")
                } actions: {
                    Button("Try Again") {
                        Task { await store.reloadScriptureEdition(edition) }
                    }
                    .buttonStyle(.borderedProminent)
                }
            } else if searchText.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                if lastReadMatches, let code = UserDefaults.standard.string(forKey: ScriptureResumeKeys.book),
                   let chapterNumber = UserDefaults.standard.object(forKey: ScriptureResumeKeys.chapter) as? Int,
                   let chapter = library.chapter(bookCode: code, number: chapterNumber) {
                    Section("Pick up where you left off") {
                        NavigationLink {
                            SacredTextChapterView(chapter: chapter)
                        } label: {
                            Label("Continue · \(chapter.title) · \(library.translation)", systemImage: "bookmark.fill")
                                .lineLimit(2)
                        }
                        .accessibilityHint("Reopens the last chapter you read in this translation")
                    }
                }
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
                                } else {
                                    ContentUnavailableView("Passage unavailable", systemImage: "book.closed", description: Text("Return to search and choose another passage."))
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

    private func scriptureGuideLink(_ guide: JaguarScriptureChatView.ScriptureGuide) -> some View {
        NavigationLink(destination: JaguarScriptureChatView(guide: guide)) {
            Label(guide.name, systemImage: guide.icon)
                .font(.caption.bold())
                .frame(maxWidth: .infinity)
                .padding(.vertical, 9)
        }
        .buttonStyle(.borderedProminent)
        .accessibilityLabel("Chat with \(guide.name) in Daily Breath")
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
        "\(count) \(tradition.chapterUnitPlural)"
    }

    private var summaryText: String {
        if tradition == .quran {
            return "\(library.books.count) surahs · \(library.verseCount.formatted()) ayahs"
        }
        return "\(library.books.count) books · \(library.chapterCount) \(tradition.chapterUnitPlural) · \(library.verseCount.formatted()) \(tradition.passageUnitPlural)"
    }
}

private enum ScriptureResumeKeys {
    static let tradition = "scripture.lastRead.tradition"
    static let edition = "scripture.lastRead.edition"
    static let book = "scripture.lastRead.book"
    static let chapter = "scripture.lastRead.chapter"
}

struct ScriptureContinueReadingLink: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage(ScriptureResumeKeys.tradition) private var traditionID = ""
    @AppStorage(ScriptureResumeKeys.edition) private var editionID = ""
    @AppStorage(ScriptureResumeKeys.book) private var bookCode = ""
    @AppStorage(ScriptureResumeKeys.chapter) private var chapterNumber = 0

    private var destination: SacredTextChapter? {
        guard let tradition = FaithTradition(rawValue: traditionID),
              let edition = ScriptureEdition(rawValue: editionID), edition.tradition == tradition,
              !bookCode.isEmpty, chapterNumber > 0 else { return nil }
        return store.scriptureLibrary(for: tradition, edition: edition).chapter(bookCode: bookCode, number: chapterNumber)
    }

    var body: some View {
        if let destination {
            NavigationLink {
                SacredTextChapterView(chapter: destination)
            } label: {
                Label("Continue reading · \(destination.title)", systemImage: "bookmark.fill")
                    .font(.headline)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .padding()
                    .background(.background.opacity(0.88), in: RoundedRectangle(cornerRadius: 16))
            }
            .accessibilityHint("Continues your last chapter using the same translation")
        }
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
                    Text("\(book.chapters.count) \(book.tradition.chapterUnitPlural) · \(book.verseCount.formatted()) \(book.tradition.passageUnitPlural)")
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 8)
            }
            Section(book.tradition.chapterUnitPlural.capitalized) {
                ForEach(book.chapters) { chapter in
                    NavigationLink {
                        SacredTextChapterView(chapter: chapter)
                    } label: {
                        HStack {
                            Text(book.tradition == .quran ? book.name : "\(book.tradition.chapterUnitSingular.capitalized) \(chapter.number)")
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
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id
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
                            ShareLink(item: "\(verse.text) — \(verse.reference)") {
                                Label(shareLabel, systemImage: "square.and.arrow.up")
                            }
                        }
                    }
                }
            }
            .navigationTitle(chapter.title)
            .onAppear {
                if let highlightedVerseID { proxy.scrollTo(highlightedVerseID, anchor: .center) }
            }
        }
        .onAppear {
            UserDefaults.standard.set(chapter.tradition.rawValue, forKey: ScriptureResumeKeys.tradition)
            UserDefaults.standard.set(store.selectedEdition(for: chapter.tradition).rawValue, forKey: ScriptureResumeKeys.edition)
            UserDefaults.standard.set(chapter.bookCode, forKey: ScriptureResumeKeys.book)
            UserDefaults.standard.set(chapter.number, forKey: ScriptureResumeKeys.chapter)
        }
    }

    private var shareLabel: String {
        switch chapter.tradition {
        case .bible: "Share Verse"
        case .torah: "Share Passage"
        case .quran: "Share Ayah"
        }
    }

    private func isRightToLeft(_ text: String) -> Bool {
        text.unicodeScalars.contains { scalar in
            (0x0590...0x08FF).contains(Int(scalar.value))
        }
    }
}
