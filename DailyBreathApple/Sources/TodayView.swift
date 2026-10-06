import AVFoundation
import AVKit
import CryptoKit
import SwiftUI
import UIKit

private enum DailyBreathTVProgramming: String, CaseIterable, Identifiable {
    case full
    case christian
    case muslim
    case judaism

    var id: String { rawValue }

    var title: String {
        switch self {
        case .full: "Full programming"
        case .christian: "Christian programming"
        case .muslim: "Muslim programming"
        case .judaism: "Judaism programming"
        }
    }

    var episodePrefix: String? {
        switch self {
        case .full: nil
        case .christian: "daily-bible-"
        case .muslim: "daily-quran-"
        case .judaism: "daily-torah-"
        }
    }
}

private struct DailyBreathTVRotation: Decodable {
    let episodes: [DailyBreathTVEpisode]
}

private struct DailyBreathTVEpisode: Decodable {
    let id: String
    let title: String
    let videoURL: String

    enum CodingKeys: String, CodingKey {
        case id, title
        case videoURL = "video_url"
    }
}

struct TodayView: View {
    var onNavigate: (DailyBreathTab) -> Void = { _ in }
    @EnvironmentObject private var store: DailyBreathStore
    @Environment(\.scenePhase) private var scenePhase
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id
    @AppStorage("dailyBreathLanguage") private var languageID = "en"
    @AppStorage("scriptureEdition.torah") private var torahEditionID = ScriptureEdition.torahHebrew.id
    @AppStorage("scriptureEdition.quran") private var quranEditionID = ScriptureEdition.quranArabic.id
    @State private var shareImage: DailyBreathShareImage?
    @State private var narrationPlayer: AVPlayer?
    @State private var narrationURL: URL?
    @State private var narrationPlaying = false
    @State private var narrationLoading = false
    @State private var narrationMessage: String?
    @State private var narrationTask: Task<Void, Never>?
    @State private var narrationRequestID = UUID()
    @AppStorage("dailyBreathTVProgramming") private var tvProgrammingID = DailyBreathTVProgramming.full.id
    @State private var tvPlayer = AVPlayer()
    @State private var tvPreviewTitle = "Loading Daily Breath TV…"
    @State private var tvPreviewError: String?
    @State private var tvMuted = true

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var selectedTradition: FaithTradition {
        FaithTradition(rawValue: traditionID) ?? .bible
    }

    private var todayVerse: Verse {
        store.dailyVerse(for: selectedTradition)
    }

    private var todayDevotional: Devotional {
        store.dailyDevotional(for: selectedTradition)
    }

    private var todayContentLocale: String {
        switch selectedTradition {
        case .bible:
            store.contentLocale(for: .bible)
        case .torah:
            store.contentLocale(for: .torah, editionID: torahEditionID)
        case .quran:
            store.contentLocale(for: .quran, editionID: quranEditionID)
        }
    }

    private var tvProgramming: DailyBreathTVProgramming {
        DailyBreathTVProgramming(rawValue: tvProgrammingID) ?? .full
    }

    private var todayShareURL: URL {
        if let approved = store.approvedContent,
           approved.date == todayKey,
           approved.tradition == selectedTradition,
           approved.locale == todayContentLocale {
            return approved.shareURL
        }
        var components = URLComponents(string: "https://beyondimagination.co.technology/dailybreath/daily.php")!
        components.queryItems = [
            URLQueryItem(name: "date", value: todayKey),
            URLQueryItem(name: "tradition", value: selectedTradition.id),
            URLQueryItem(name: "lang", value: todayContentLocale)
        ]
        return components.url!
    }

    private var todayKey: String {
        Self.dayFormatter.string(from: Date())
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                todayIntro
                todayReading
                devotionalCard
                dailyBreathTVPlayer
            }
            .padding()
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle("Today")
        .navigationBarTitleDisplayMode(.inline)
        .navigationBarBackButtonHidden(true)
        .toolbar {
            ToolbarItem(placement: .topBarLeading) {
                Menu {
                    Button { onNavigate(.home) } label: { Label("Home", systemImage: "house.fill") }
                    Button { onNavigate(.scripture) } label: { Label("Scripture", systemImage: "book.closed.fill") }
                    Button { onNavigate(.chat) } label: { Label("Chat", systemImage: "bubble.left.and.bubble.right.fill") }
                    Button { onNavigate(.academy) } label: { Label("Academy", systemImage: "graduationcap.fill") }
                    Button { onNavigate(.trivia) } label: { Label("Trivia", systemImage: "questionmark.circle.fill") }
                    Button { onNavigate(.breathe) } label: { Label("Breathe", systemImage: "wind") }
                    Button { onNavigate(.journal) } label: { Label("Journal", systemImage: "square.and.pencil") }
                    Button { onNavigate(.settings) } label: { Label("Settings", systemImage: "gearshape.fill") }
                } label: {
                    Label("Menu", systemImage: "line.3.horizontal")
                }
                .accessibilityHint("Opens Daily Breath navigation")
            }
        }
        .refreshable { await store.refreshToday() }
        .onChange(of: selectedThemeID) { _, _ in
            store.publishSelectedFaithContent()
        }
        .onChange(of: scenePhase) { _, phase in
            guard phase == .active else {
                stopNarration()
                return
            }
            Task {
                await store.syncICloudNow()
                await store.refreshToday()
            }
        }
        .onDisappear { stopNarration() }
        .onReceive(NotificationCenter.default.publisher(for: .NSCalendarDayChanged)) { _ in
            Task { await store.refreshToday() }
        }
        .onChange(of: languageID) { _, _ in
            stopNarration()
            Task { await store.refreshToday() }
        }
        .onChange(of: torahEditionID) { _, _ in
            guard selectedTradition == .torah else { return }
            stopNarration()
            Task { await store.refreshToday() }
        }
        .onChange(of: quranEditionID) { _, _ in
            guard selectedTradition == .quran else { return }
            stopNarration()
            Task { await store.refreshToday() }
        }
        .onChange(of: store.approvedContent?.audioURL) { _, _ in stopNarration() }
        .onReceive(NotificationCenter.default.publisher(for: .AVPlayerItemDidPlayToEndTime)) { notification in
            if (notification.object as? AVPlayerItem) === narrationPlayer?.currentItem {
                stopNarration()
            }
        }
        .sheet(item: $shareImage) { item in
            DailyBreathImageShareSheet(image: item.image)
        }
    }

    @ViewBuilder
    private var todayIntro: some View {
        BrandHeader()
    }

    @ViewBuilder
    private var todayReading: some View {
        verseCard
    }

    private var dailyBreathTVPlayer: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Label("Daily Breath TV", systemImage: "play.tv.fill")
                    .font(.headline.weight(.bold))
                Spacer()
                Link("Open channel", destination: Self.dailyBreathTVChannelURL)
                    .font(.subheadline.weight(.semibold))
            }
            VideoPlayer(player: tvPlayer)
                .frame(minHeight: 190)
                .clipShape(RoundedRectangle(cornerRadius: 18))
                .accessibilityLabel("Daily Breath TV preview")
            if let tvPreviewError {
                Text(tvPreviewError)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            } else {
                Text(tvPreviewTitle)
                    .font(.caption.weight(.medium))
                    .foregroundStyle(.secondary)
            }
            Menu {
                ForEach(DailyBreathTVProgramming.allCases) { programming in
                    Button {
                        tvProgrammingID = programming.id
                    } label: {
                        Label(programming.title, systemImage: programming == tvProgramming ? "checkmark" : "circle")
                    }
                }
            } label: {
                Label(tvProgramming.title, systemImage: "checklist")
                    .font(.subheadline.weight(.semibold))
            }
            .accessibilityLabel("Daily Breath TV programming")
            .accessibilityHint("Choose full, Christian, Muslim, or Judaism programming.")
            Button {
                tvMuted.toggle()
                tvPlayer.isMuted = tvMuted
            } label: {
                Label(tvMuted ? "Turn on channel audio" : "Mute channel audio", systemImage: tvMuted ? "speaker.slash.fill" : "speaker.wave.2.fill")
            }
            .buttonStyle(.bordered)
        }
        .padding(16)
        .background(.regularMaterial, in: RoundedRectangle(cornerRadius: 24))
        .task(id: tvProgrammingID) { await loadDailyBreathTVPreview() }
        .onDisappear { tvPlayer.pause() }
    }

    @MainActor
    private func loadDailyBreathTVPreview() async {
        tvPlayer.pause()
        tvPreviewError = nil
        tvPreviewTitle = "Loading \(tvProgramming.title)…"
        do {
            let (data, response) = try await URLSession.shared.data(from: Self.dailyBreathTVRotationURL)
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
                throw DailyBreathAPIError.badResponse
            }
            let episodes = try JSONDecoder().decode(DailyBreathTVRotation.self, from: data).episodes
            let episode = episodes.first { episode in
                guard let prefix = tvProgramming.episodePrefix else { return true }
                return episode.id.hasPrefix(prefix)
            }
            guard let episode,
                  let url = URL(string: episode.videoURL, relativeTo: Self.dailyBreathTVBaseURL) else {
                throw DailyBreathAPIError.badResponse
            }
            tvPlayer.replaceCurrentItem(with: AVPlayerItem(url: url))
            tvPlayer.isMuted = tvMuted
            tvPlayer.play()
            tvPreviewTitle = episode.title
        } catch {
            tvPreviewError = "The selected channel preview is unavailable. Open the channel to try again."
        }
    }


    private var verseCard: some View {
        VStack(alignment: .leading, spacing: 18) {
            Label("\(selectedTradition.dailyReadingName) of the Day", systemImage: selectedTradition.symbolName)
                .font(.caption.bold())
                .tracking(1.4)
                .foregroundStyle(selectedTheme.accent)
            Text(Date(), format: .dateTime.weekday(.wide).month(.wide).day().year())
                .font(.subheadline.weight(.semibold))
                .foregroundStyle(.black.opacity(0.66))
            if let approved = store.approvedContent, approved.tradition == selectedTradition {
                Text("Approved reading · updated \(String(approved.updatedAt.prefix(16)))")
                    .font(.caption)
                    .foregroundStyle(.black.opacity(0.62))
            }
            Text(todayVerse.text)
                .font(.system(.largeTitle, design: .serif, weight: .semibold))
                .foregroundStyle(.black)
                .fixedSize(horizontal: false, vertical: true)
                .multilineTextAlignment(passageIsRightToLeft ? .trailing : .leading)
                .environment(\.layoutDirection, passageIsRightToLeft ? .rightToLeft : .leftToRight)
                .textSelection(.enabled)
            Text(todayVerse.reference)
                .font(.headline.weight(.black))
                .foregroundStyle(selectedTheme.accent)
            HStack(spacing: 10) {
                NavigationLink {
                    VerseDetailView(verse: todayVerse, tradition: selectedTradition)
                } label: {
                    Image(systemName: "book.fill")
                        .frame(maxWidth: .infinity, minHeight: 44)
                        .contentShape(Rectangle())
                }
                .buttonStyle(.plain)
                .foregroundStyle(.black)
                .accessibilityLabel("Open reading")
                ShareLink(item: todayShareURL) {
                    Image(systemName: "square.and.arrow.up")
                        .frame(maxWidth: .infinity, minHeight: 44)
                        .contentShape(Rectangle())
                }
                .buttonStyle(.plain)
                .foregroundStyle(.black)
                .accessibilityLabel("Share reading")
                Button {
                    exportShareImage()
                } label: {
                    Image(systemName: "arrow.down.to.line")
                        .frame(maxWidth: .infinity, minHeight: 44)
                        .contentShape(Rectangle())
                }
                .buttonStyle(.plain)
                .foregroundStyle(.black)
                .accessibilityLabel("Share or save image")
                .accessibilityHint("Opens options to share or save the reading image.")
            }
            .font(.title2.weight(.semibold))
            .controlSize(.large)
            Button {
                handleNarrationTap()
            } label: {
                Label(
                    narrationLoading ? "Preparing narration…" : narrationPlaying ? "Pause narration" : "Listen to today’s reading",
                    systemImage: narrationLoading ? "hourglass" : narrationPlaying ? "pause.fill" : "play.fill"
                )
            }
            .buttonStyle(.borderedProminent)
            .tint(selectedTheme.accent)
            .frame(maxWidth: .infinity, minHeight: 46)
            .disabled(narrationLoading)
            .accessibilityHint("Prepares and saves today’s narration on this device for offline replay.")
            if let narrationMessage {
                Text(narrationMessage)
                    .font(.footnote.weight(.medium))
                    .foregroundStyle(.black.opacity(0.62))
            }
        }
        .padding(24)
        .background(
            RoundedRectangle(cornerRadius: 26)
                .fill(.white.opacity(selectedTheme.artworkName == nil ? 0.94 : 0.88))
        )
    }

    private var passageIsRightToLeft: Bool {
        todayVerse.text.unicodeScalars.contains { scalar in
            (0x0590...0x05FF).contains(scalar.value) || (0x0600...0x06FF).contains(scalar.value)
        }
    }

    private func handleNarrationTap() {
        if narrationPlaying {
            narrationPlayer?.pause()
            narrationPlaying = false
            return
        }
        if let narrationURL, narrationURL.isFileURL {
            playNarration(from: narrationURL)
            return
        }
        guard !narrationLoading else { return }
        narrationLoading = true
        narrationMessage = nil
        let requestID = UUID()
        narrationRequestID = requestID
        narrationTask = Task { await prepareNarration(requestID: requestID) }
    }

    @MainActor
    private func prepareNarration(requestID: UUID) async {
        defer {
            if requestID == narrationRequestID {
                narrationLoading = false
                narrationTask = nil
            }
        }
        let requestedDate = todayKey
        let requestedTradition = selectedTradition.id
        let requestedLocale = todayContentLocale
        let offlineVerse = todayVerse
        do {
            if let cachedURL = try Self.existingCachedNarration(
                date: requestedDate,
                tradition: requestedTradition,
                locale: requestedLocale,
                passage: offlineVerse.text,
                reference: offlineVerse.reference
            ) {
                guard requestID == narrationRequestID, !Task.isCancelled,
                      scenePhase == .active, requestedDate == todayKey,
                      requestedTradition == selectedTradition.id,
                      requestedLocale == todayContentLocale else { return }
                playNarration(from: cachedURL)
                narrationMessage = "Saved on this device for offline listening."
                return
            }

            await store.refreshToday()
            guard requestID == narrationRequestID, !Task.isCancelled,
                  scenePhase == .active, requestedDate == todayKey,
                  requestedTradition == selectedTradition.id,
                  requestedLocale == todayContentLocale,
                  store.dailyContentAvailability == .current else {
                throw DailyBreathAPIError.badResponse
            }
            let syncedVerse = todayVerse
            let fileURL = try await Self.cachedNarration(
                date: requestedDate,
                tradition: requestedTradition,
                locale: requestedLocale,
                passage: syncedVerse.text,
                reference: syncedVerse.reference
            )
            guard requestID == narrationRequestID, !Task.isCancelled,
                  scenePhase == .active,
                  requestedDate == todayKey,
                  requestedTradition == selectedTradition.id,
                  requestedLocale == todayContentLocale else { return }
            playNarration(from: fileURL)
            narrationMessage = "Saved on this device for offline listening."
        } catch {
            guard requestID == narrationRequestID, !Task.isCancelled else { return }
            narrationMessage = store.dailyContentAvailability == .offline
                ? "Connect to refresh today’s reading before preparing narration."
                : "Narration could not be prepared. Check your connection and try again."
        }
    }

    private func playNarration(from url: URL) {
        narrationPlayer = AVPlayer(url: url)
        narrationURL = url
        narrationPlayer?.play()
        narrationPlaying = true
    }

    private static func cachedNarration(date: String, tradition: String, locale: String, passage: String, reference: String) async throws -> URL {
        let fileManager = FileManager.default
        let fileURL = try narrationFileURL(date: date, tradition: tradition, locale: locale, passage: passage, reference: reference)
        if fileManager.fileExists(atPath: fileURL.path) { return fileURL }
        let script = passage.trimmingCharacters(in: .whitespacesAndNewlines) + "\n\n" + reference.trimmingCharacters(in: .whitespacesAndNewlines)
        let contentHash = SHA256.hash(data: Data(script.utf8)).map { String(format: "%02x", $0) }.joined()

        let endpoint = URL(string: "https://beyondimagination.co.technology/dailybreath/api/narration.php")!
        var request = URLRequest(url: endpoint)
        request.httpMethod = "POST"
        request.timeoutInterval = 45
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.httpBody = try JSONSerialization.data(withJSONObject: [
            "date": date,
            "tradition": tradition,
            "locale": locale,
            "content_hash": contentHash
        ])
        let (responseData, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode),
              let payload = try JSONDecoder().decode(DailyBreathNarrationResponse.self, from: responseData).audioURL else {
            throw DailyBreathAPIError.badResponse
        }

        var downloadRequest = URLRequest(url: payload)
        downloadRequest.timeoutInterval = 90
        let (audioData, audioResponse) = try await URLSession.shared.data(for: downloadRequest)
        let audioMimeType = (audioResponse as? HTTPURLResponse)?.mimeType ?? ""
        guard let audioHTTP = audioResponse as? HTTPURLResponse,
              (200..<300).contains(audioHTTP.statusCode),
              (audioMimeType.hasPrefix("audio/") || audioMimeType == "application/octet-stream"),
              !audioData.isEmpty else {
            throw DailyBreathAPIError.badResponse
        }
        try audioData.write(to: fileURL, options: .atomic)
        return fileURL
    }

    private static func existingCachedNarration(date: String, tradition: String, locale: String, passage: String, reference: String) throws -> URL? {
        let fileURL = try narrationFileURL(date: date, tradition: tradition, locale: locale, passage: passage, reference: reference)
        return FileManager.default.fileExists(atPath: fileURL.path) ? fileURL : nil
    }

    private static func narrationFileURL(date: String, tradition: String, locale: String, passage: String, reference: String) throws -> URL {
        let fileManager = FileManager.default
        let directory = try fileManager.url(for: .applicationSupportDirectory, in: .userDomainMask, appropriateFor: nil, create: true)
            .appendingPathComponent("DailyBreathNarration", isDirectory: true)
        try fileManager.createDirectory(at: directory, withIntermediateDirectories: true, attributes: nil)
        var directoryURL = directory
        var resourceValues = URLResourceValues()
        resourceValues.isExcludedFromBackup = true
        try? directoryURL.setResourceValues(resourceValues)
        let script = passage.trimmingCharacters(in: .whitespacesAndNewlines) + "\n\n" + reference.trimmingCharacters(in: .whitespacesAndNewlines)
        let contentHash = SHA256.hash(data: Data(script.utf8)).map { String(format: "%02x", $0) }.joined()
        return directory.appendingPathComponent("\(date)-\(tradition)-\(locale)-\(contentHash.prefix(16)).mp3")
    }

    private func stopNarration() {
        narrationRequestID = UUID()
        narrationTask?.cancel()
        narrationTask = nil
        narrationLoading = false
        narrationPlayer?.pause()
        narrationPlayer = nil
        narrationURL = nil
        narrationPlaying = false
    }

    private static let dailyBreathTVChannelURL = URL(string: "https://beyondimagination.co.technology/beyond-tv/channel.php?slug=mrbeast-tv")!
    private static let dailyBreathTVBaseURL = URL(string: "https://beyondimagination.co.technology")!
    private static let dailyBreathTVRotationURL = URL(string: "https://beyondimagination.co.technology/dailybreath/assets/videos/daily-breath-tv/rotation.json")!

    private func exportShareImage() {
        let card = DailyBreathExportCard(
            verse: todayVerse,
            tradition: selectedTradition,
            theme: selectedTheme,
            date: Date()
        )
        let renderer = ImageRenderer(content: card)
        renderer.proposedSize = ProposedViewSize(width: 1200, height: 675)
        renderer.scale = 1
        if let image = renderer.uiImage {
            shareImage = DailyBreathShareImage(image: image)
        }
    }

    private var devotionalCard: some View {
        NavigationLink {
            DevotionalDetailView(devotional: todayDevotional, tradition: selectedTradition)
        } label: {
            HStack(alignment: .top, spacing: 14) {
                VStack(alignment: .leading, spacing: 10) {
                    Text("DAILY \(selectedTradition.devotionalName.uppercased())")
                        .font(.caption.bold())
                        .tracking(1.6)
                        .foregroundStyle(selectedTheme.primary)
                    Text(todayDevotional.title)
                        .font(.title2.weight(.bold))
                    Text(todayDevotional.excerpt)
                        .foregroundStyle(.secondary)
                    Label("\(todayDevotional.scripture) · \(todayDevotional.minutes) minute read", systemImage: "clock.fill")
                        .font(.caption.bold())
                        .foregroundStyle(selectedTheme.accent)
                }
                Spacer(minLength: 8)
                VStack(spacing: 8) {
                    FaithGuidePortrait(tradition: selectedTradition, width: 54, height: 64, cornerRadius: 11)
                    Image(systemName: "chevron.right")
                        .font(.footnote.weight(.bold))
                        .foregroundStyle(.tertiary)
                }
            }
            .padding(20)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(.background, in: RoundedRectangle(cornerRadius: 22))
        }
        .buttonStyle(.plain)
    }

    private var journalCard: some View {
        NavigationLink {
            JournalView()
        } label: {
            HStack(spacing: 14) {
                Image(systemName: "square.and.pencil")
                    .font(.title2)
                    .foregroundStyle(selectedTheme.accent)
                    .frame(width: 34)
                VStack(alignment: .leading, spacing: 5) {
                    Text("Reflection Journal")
                        .font(.headline)
                    Text(store.entries.first?.text ?? DailyBreathStore.promptOfTheDay())
                        .font(.caption)
                        .foregroundStyle(.secondary)
                        .lineLimit(2)
                }
                Spacer()
                Image(systemName: "chevron.right")
                    .font(.footnote.weight(.bold))
                    .foregroundStyle(.tertiary)
            }
            .padding(16)
            .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
        }
        .buttonStyle(.plain)
    }

    private var recoveryNewsletterCard: some View {
        NavigationLink {
            RecoveryNewsletterView()
        } label: {
            HStack(alignment: .top, spacing: 14) {
                Image(systemName: "newspaper.fill")
                    .font(.title2)
                    .foregroundStyle(selectedTheme.accent)
                    .frame(width: 44, height: 44)
                    .background(selectedTheme.accent.opacity(0.12), in: RoundedRectangle(cornerRadius: 8))
                VStack(alignment: .leading, spacing: 6) {
                    Text("RECOVERY NEWSLETTER")
                        .font(.caption.bold())
                        .tracking(1.4)
                        .foregroundStyle(selectedTheme.primary)
                    Text("One verse, one reflection, and this week’s recovery practice.")
                        .font(.headline)
                    Text("Automatically refreshed with today’s Daily Breath content.")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Spacer()
                Image(systemName: "chevron.right")
                    .foregroundStyle(.tertiary)
            }
            .padding(16)
            .background(.background.opacity(0.9), in: RoundedRectangle(cornerRadius: 8))
        }
        .buttonStyle(.plain)
    }

    private var quickActions: some View {
        LazyVGrid(columns: [.init(.flexible()), .init(.flexible())], spacing: 12) {
            NavigationLink {
                ScriptureLibraryView()
            } label: {
                QuickAction(title: selectedTradition.libraryName, subtitle: "Read and reflect", systemImage: "book.closed.fill")
            }
            NavigationLink {
                BreatheView()
            } label: {
                QuickAction(title: "Breath of the Day", subtitle: BreathPattern.breathOfTheDay().title, systemImage: "wind")
            }
            NavigationLink {
                PrayerPracticesView(tradition: selectedTradition)
            } label: {
                QuickAction(title: selectedTradition.prayerCollectionName, subtitle: "Guidance and healing", systemImage: "hands.sparkles.fill")
            }
            NavigationLink {
                WeeklyChallengeView()
            } label: {
                QuickAction(title: "Weekly Challenge", subtitle: store.challenge?.title ?? "Faith in action", systemImage: "calendar.badge.checkmark")
            }
            NavigationLink {
                AcademyView()
            } label: {
                QuickAction(
                    title: selectedTradition.academyName,
                    subtitle: "Learn with \(selectedTradition.guideName)",
                    systemImage: "graduationcap.fill",
                    guide: selectedTradition
                )
            }
            NavigationLink {
                JournalView()
            } label: {
                QuickAction(title: "One Sentence", subtitle: "Reflect today", systemImage: "pencil.and.list.clipboard")
            }
        }
        .buttonStyle(.plain)
    }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

private struct DailyBreathNarrationResponse: Decodable {
    let audioURL: URL?

    enum CodingKeys: String, CodingKey {
        case audioURL = "audio_url"
    }
}

private struct DailyBreathShareImage: Identifiable {
    let id = UUID()
    let image: UIImage
}

private struct DailyBreathImageShareSheet: UIViewControllerRepresentable {
    let image: UIImage

    func makeUIViewController(context: Context) -> UIActivityViewController {
        UIActivityViewController(activityItems: [image], applicationActivities: nil)
    }

    func updateUIViewController(_ controller: UIActivityViewController, context: Context) {}
}

private struct DailyBreathExportCard: View {
    let verse: Verse
    let tradition: FaithTradition
    let theme: DailyBreathTheme
    let date: Date

    var body: some View {
        ZStack {
            LinearGradient(colors: [theme.primary, theme.secondary], startPoint: .topLeading, endPoint: .bottomTrailing)
            if let artwork = theme.shareArtworkName {
                Image(artwork)
                    .resizable()
                    .scaledToFill()
                    .frame(width: 1200, height: 675)
                    .clipped()
            }
            LinearGradient(colors: [.black.opacity(0.46), .black.opacity(0.62)], startPoint: .top, endPoint: .bottom)
            VStack(spacing: 20) {
                Text("\(tradition.dailyReadingName) of the Day · \(date.formatted(date: .long, time: .omitted))")
                    .font(.system(size: 25, weight: .semibold, design: .serif))
                    .foregroundStyle(theme.accent)
                Spacer(minLength: 0)
                Text(verse.text)
                    .font(.system(size: 52, weight: .semibold, design: .serif))
                    .minimumScaleFactor(0.55)
                    .lineLimit(5)
                    .multilineTextAlignment(.center)
                    .environment(\.layoutDirection, passageIsRightToLeft ? .rightToLeft : .leftToRight)
                    .foregroundStyle(.white)
                Text(verse.reference)
                    .font(.system(size: 31, weight: .bold, design: .serif))
                    .foregroundStyle(theme.accent)
                if !verse.reflection.isEmpty {
                    Text(verse.reflection)
                        .font(.system(size: 23, weight: .medium, design: .serif))
                        .lineLimit(2)
                        .multilineTextAlignment(.center)
                        .foregroundStyle(.white.opacity(0.92))
                }
                Spacer(minLength: 0)
                Text("@thedaybreath · Faith · Recovery · Hope")
                    .font(.system(size: 20, weight: .semibold))
                    .foregroundStyle(theme.accent)
            }
            .padding(.horizontal, 110)
            .padding(.vertical, 65)
        }
        .frame(width: 1200, height: 675)
    }

    private var passageIsRightToLeft: Bool {
        verse.text.unicodeScalars.contains { scalar in
            (0x0590...0x05FF).contains(scalar.value) || (0x0600...0x06FF).contains(scalar.value)
        }
    }
}

private struct RhythmPill: View {
    let title: String
    let isComplete: Bool
    let theme: DailyBreathTheme

    var body: some View {
        VStack(spacing: 5) {
            Image(systemName: isComplete ? "checkmark.circle.fill" : "circle")
                .font(.subheadline)
            Text(title)
                .font(.caption2.bold())
                .lineLimit(1)
                .minimumScaleFactor(0.75)
        }
        .foregroundStyle(isComplete ? theme.primary : .secondary)
            .frame(maxWidth: .infinity)
            .padding(.vertical, 10)
            .background(theme.primary.opacity(isComplete ? 0.13 : 0.05), in: RoundedRectangle(cornerRadius: 8))
            .accessibilityLabel("\(title) \(isComplete ? "complete" : "not complete")")
    }
}

private struct QuickAction: View {
    let title: String
    let subtitle: String
    let systemImage: String
    var guide: FaithTradition? = nil
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 9) {
            if let guide {
                FaithGuidePortrait(tradition: guide, width: 44, height: 50, cornerRadius: 9)
            } else {
                Image(systemName: systemImage)
                    .font(.title2)
                    .foregroundStyle(selectedTheme.accent)
            }
            Text(title)
                .font(.headline)
            Text(subtitle)
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .padding()
        .frame(maxWidth: .infinity, minHeight: 126, alignment: .topLeading)
        .background(.background, in: RoundedRectangle(cornerRadius: 20))
    }
}

private struct VerseDetailView: View {
    let verse: Verse
    let tradition: FaithTradition
    @AppStorage("dailyReadingDayKeys") private var dailyReadingDayKeys = ""
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                HStack(spacing: 12) {
                    FaithGuidePortrait(tradition: tradition, width: 56, height: 68, cornerRadius: 12)
                    VStack(alignment: .leading, spacing: 4) {
                        Label("\(tradition.dailyReadingName) of the Day", systemImage: tradition.symbolName)
                            .font(.caption.bold())
                            .tracking(1.4)
                            .foregroundStyle(selectedTheme.accent)
                        Text("Read with \(tradition.guideName)")
                            .font(.headline)
                    }
                }
                Text("\"\(verse.text)\"")
                    .font(.system(size: 38, weight: .semibold, design: .serif))
                    .fixedSize(horizontal: false, vertical: true)
                Text(verse.reference)
                    .font(.title3.weight(.black))
                    .foregroundStyle(selectedTheme.primary)
                Divider()
                Text(verse.reflection)
                    .font(.body)
                    .foregroundStyle(.secondary)
                NavigationLink {
                    ScriptureLibraryView()
                } label: {
                    Label("Open \(tradition.libraryName)", systemImage: "book.closed.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(selectedTheme.primary)
                .controlSize(.large)
            }
            .padding()
        }
        .background(DailyBreathThemeBackground(theme: selectedTheme))
        .navigationTitle(verse.reference)
        .navigationBarTitleDisplayMode(.inline)
        .onAppear {
            markRead()
        }
    }

    private func markRead() {
        let todayKey = Self.dayFormatter.string(from: Date())
        var keys = dailyReadingDayKeys
            .split(separator: ",")
            .map(String.init)
            .filter { !$0.isEmpty }
        if !keys.contains(todayKey) {
            keys.append(todayKey)
        }
        dailyReadingDayKeys = keys.suffix(366).joined(separator: ",")
    }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

private struct DevotionalDetailView: View {
    @EnvironmentObject private var store: DailyBreathStore
    let devotional: Devotional
    let tradition: FaithTradition
    @AppStorage("devotionalReadDayKeys") private var devotionalReadDayKeys = ""
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var todayKey: String {
        Self.dayFormatter.string(from: Date())
    }

    private var isReadToday: Bool {
        devotionalReadDayKeys.split(separator: ",").contains(Substring(todayKey))
    }

    var body: some View {
        List {
            Section {
                HStack(alignment: .top, spacing: 14) {
                    FaithGuidePortrait(tradition: tradition, width: 76, height: 94, cornerRadius: 15)
                    VStack(alignment: .leading, spacing: 8) {
                        Text("WITH \(tradition.guideName.uppercased()) · \(devotional.scripture)")
                            .font(.caption2.bold())
                            .foregroundStyle(selectedTheme.accent)
                        Text(devotional.title)
                            .font(.largeTitle.weight(.black))
                            .minimumScaleFactor(0.75)
                        Label("\(devotional.minutes) minute read", systemImage: "clock.fill")
                            .font(.caption.bold())
                            .foregroundStyle(.secondary)
                    }
                }
                .padding(.vertical, 8)
            }

            Section("Reflection") {
                Text(devotional.body)
                    .font(.body)
            }

            Section(tradition.prayerName) {
                Text(devotional.prayer)
                    .font(.body)
                Button {
                    markRead()
                    store.prepareJournalReflection(
                        prompt: "\(tradition.prayerName) from \(devotional.title)",
                        text: devotional.prayer,
                        mood: "Hopeful"
                    )
                } label: {
                    Label("Save This \(tradition.prayerName)", systemImage: "hands.sparkles.fill")
                }
            }

            Section("Practice") {
                Text(devotional.practice)
                    .font(.body)
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Reflect in Journal", systemImage: "square.and.pencil")
                }
            }

            Section {
                Button {
                    markRead()
                } label: {
                    Label(isReadToday ? "Read Today" : "Mark as Read", systemImage: isReadToday ? "checkmark.circle.fill" : "circle")
                }
                .foregroundStyle(selectedTheme.primary)
            }
        }
        .navigationTitle(tradition.devotionalName)
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }

    private func markRead() {
        var keys = devotionalReadDayKeys
            .split(separator: ",")
            .map(String.init)
            .filter { !$0.isEmpty }
        if !keys.contains(todayKey) {
            keys.append(todayKey)
        }
        devotionalReadDayKeys = keys.suffix(366).joined(separator: ",")
    }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

private struct RecoveryNewsletterView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    private var selectedTradition: FaithTradition {
        FaithTradition(rawValue: traditionID) ?? .bible
    }

    private var dailyVerse: Verse { store.dailyVerse(for: selectedTradition) }
    private var dailyDevotional: Devotional {
        store.weeklyDevotional(for: selectedTradition)
    }
    private var dailyChallenge: RecoveryChallenge? { store.dailyChallenge(for: selectedTradition) }

    var body: some View {
        List {
            Section {
                HStack(alignment: .top, spacing: 14) {
                    FaithGuidePortrait(tradition: selectedTradition, width: 76, height: 94, cornerRadius: 15)
                    VStack(alignment: .leading, spacing: 8) {
                        Label("Recovery Newsletter with \(selectedTradition.guideName)", systemImage: "newspaper.fill")
                            .font(.caption.bold())
                            .foregroundStyle(selectedTheme.accent)
                        Text(selectedTradition.newsletterTagline)
                            .font(.title.weight(.black))
                        Text(Date(), format: .dateTime.weekday(.wide).month(.wide).day().year())
                            .foregroundStyle(.secondary)
                    }
                }
                .padding(.vertical, 8)
            }

            Section("Today’s \(selectedTradition.dailyReadingName)") {
                Text("“\(dailyVerse.text)”")
                    .font(.system(.title3, design: .serif).weight(.semibold))
                Text(dailyVerse.reference)
                    .font(.headline)
                    .foregroundStyle(selectedTheme.primary)
                Text(dailyVerse.reflection)
                    .foregroundStyle(.secondary)
            }

            Section(dailyDevotional.title) {
                Text(dailyDevotional.body)
                Label(dailyDevotional.scripture, systemImage: "book.closed.fill")
                    .foregroundStyle(selectedTheme.primary)
            }

            Section(selectedTradition.prayerName) {
                Text(dailyDevotional.prayer)
            }

            Section("Practice") {
                Text(dailyDevotional.practice)
            }

            if let challenge = dailyChallenge {
                Section("This Week: \(challenge.title)") {
                    Text(challenge.description)
                    ForEach(challenge.steps, id: \.self) { step in
                        Label(step, systemImage: "checkmark.circle")
                    }
                    ProgressView(
                        value: Double(store.challengeProgressCount),
                        total: Double(max(challenge.targetCount, 1))
                    )
                    Text("\(store.challengeProgressCount) of \(challenge.targetCount) days complete")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }

            Section("Support") {
                NavigationLink { RecoverySupportView() } label: {
                    Label("Professional and crisis resources", systemImage: "lifepreserver")
                }
                Text("Daily Breath is not medical care or emergency support.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Section {
                ShareLink(item: store.recoveryNewsletterShareText) {
                    Label("Share Recovery Newsletter", systemImage: "square.and.arrow.up")
                }
            }
        }
        .navigationTitle("Recovery Newsletter")
        .navigationBarTitleDisplayMode(.inline)
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }
}

private struct PrayerPracticesView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id
    let tradition: FaithTradition

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                HStack(spacing: 14) {
                    FaithGuidePortrait(tradition: tradition, width: 64, height: 78, cornerRadius: 13)
                    VStack(alignment: .leading, spacing: 4) {
                        Text("Practice with \(tradition.guideName)")
                            .font(.headline)
                        Text("Choose a guided \(tradition.prayerName.lowercased()) for this moment.")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                }
            }
            Section {
                ForEach(store.practices.filter { $0.title != "Peace Breath" && $0.title != "Weekly Challenge" }) { practice in
                    NavigationLink {
                        PrayerPracticeDetailView(practice: practice, tradition: tradition)
                    } label: {
                        Label {
                            VStack(alignment: .leading, spacing: 3) {
                                Text(practice.title)
                                Text(practice.subtitle)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                        } icon: {
                            Image(systemName: practice.systemImage)
                                .foregroundStyle(selectedTheme.accent)
                        }
                    }
                }
            }
        }
        .navigationTitle(tradition.prayerCollectionName)
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }
}

private struct PrayerPracticeDetailView: View {
    let practice: PrayerPractice
    let tradition: FaithTradition
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                HStack(alignment: .top, spacing: 14) {
                    FaithGuidePortrait(tradition: tradition, width: 76, height: 94, cornerRadius: 15)
                    VStack(alignment: .leading, spacing: 8) {
                        Label("With \(tradition.guideName)", systemImage: practice.systemImage)
                            .font(.caption.bold())
                            .foregroundStyle(selectedTheme.accent)
                        Text(practice.title)
                            .font(.largeTitle.weight(.black))
                            .minimumScaleFactor(0.75)
                        Text(practice.subtitle)
                            .foregroundStyle(.secondary)
                    }
                }
                .padding(.vertical, 8)
            }

            Section(tradition.prayerName) {
                Text(prayerText)
            }

            Section("Next Step") {
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Write a Reflection", systemImage: "square.and.pencil")
                }
            }
        }
        .navigationTitle(practice.title)
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }

    private var prayerText: String {
        switch (tradition, practice.title) {
        case (.bible, "Guidance Prayer"):
            "Lord, give me wisdom for the decision in front of me. Help me listen before I move and choose what brings peace, truth, and love."
        case (.bible, "Gratitude Reset"):
            "Lord, open my eyes to what is good. Teach me to receive today with humility and respond with generosity."
        case (.torah, "Guidance Prayer"):
            "Source of wisdom, help me listen honestly and choose the path of truth, responsibility, and life. Guide my next step and help me seek wise counsel."
        case (.torah, "Gratitude Reset"):
            "Holy One, help me notice the gifts, people, and responsibilities entrusted to me today. May gratitude lead me toward generosity and acts of lovingkindness."
        case (.quran, "Guidance Prayer"):
            "Allah, guide me to what is right, grant me clear judgment, and keep me away from what causes harm. Help me trust You and seek wise counsel. Amin."
        case (.quran, "Gratitude Reset"):
            "Alhamdulillah for every blessing I recognize and every blessing I overlook. Allah, make me grateful in heart, word, and action. Amin."
        case (.bible, _):
            "Lord, meet me in this practice and shape my next step with grace."
        case (.torah, _):
            "Holy One, meet me in this practice and guide my next step toward truth, repair, and peace."
        case (.quran, _):
            "Allah, meet me with mercy, guide my next step, and strengthen me to do what is right. Amin."
        }
    }
}

private struct WeeklyChallengeView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @AppStorage("dailyBreathTheme") private var selectedThemeID = DailyBreathTheme.seasonal.id
    @AppStorage("selectedFaithTradition") private var traditionID = FaithTradition.bible.id

    private var selectedTradition: FaithTradition {
        FaithTradition(rawValue: traditionID) ?? .bible
    }

    private var challenge: RecoveryChallenge? { store.dailyChallenge(for: selectedTradition) }

    private var selectedTheme: DailyBreathTheme {
        DailyBreathTheme(id: selectedThemeID)
    }

    var body: some View {
        List {
            Section {
                HStack(alignment: .top, spacing: 14) {
                    FaithGuidePortrait(tradition: selectedTradition, width: 76, height: 94, cornerRadius: 15)
                    VStack(alignment: .leading, spacing: 8) {
                        Label("Weekly challenge with \(selectedTradition.guideName)", systemImage: "calendar.badge.checkmark")
                            .font(.caption.bold())
                            .foregroundStyle(selectedTheme.accent)
                        Text(challenge?.title ?? "Faith in Action")
                            .font(.largeTitle.weight(.black))
                            .minimumScaleFactor(0.75)
                        Text(challenge?.description ?? "Choose one quiet act of faith this week and make it concrete.")
                            .foregroundStyle(.secondary)
                        if let challenge {
                            Text(challenge.scriptureReference)
                                .font(.caption.bold())
                                .foregroundStyle(selectedTheme.accent)
                        }
                    }
                }
                .padding(.vertical, 8)
            }

            Section("This Week") {
                if let challenge {
                    ForEach(challenge.steps, id: \.self) { step in
                        Label(step, systemImage: "checkmark.circle")
                    }
                } else {
                    Label("Encourage someone who needs courage.", systemImage: "message.fill")
                    Label("Give without needing credit.", systemImage: "gift.fill")
                    Label("Return to stillness before reacting.", systemImage: "pause.circle.fill")
                }
            }

            Section("Track It") {
                if let challenge {
                    ProgressView(
                        value: Double(store.challengeProgressCount),
                        total: Double(max(challenge.targetCount, 1))
                    )
                    Text("\(store.challengeProgressCount) of \(challenge.targetCount) days complete")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Button {
                        store.completeChallengeToday()
                    } label: {
                        Label(
                            store.isChallengeCompleteToday ? "Completed Today" : "Mark Today Complete",
                            systemImage: store.isChallengeCompleteToday ? "checkmark.circle.fill" : "circle"
                        )
                    }
                    .disabled(store.isChallengeCompleteToday)
                }
                NavigationLink { RecoverySupportView() } label: {
                    Label("Recovery Support Resources", systemImage: "lifepreserver")
                }
                NavigationLink {
                    JournalView()
                } label: {
                    Label("Record Your Challenge", systemImage: "square.and.pencil")
                }
            }
        }
        .navigationTitle("Weekly Challenge")
        .scrollContentBackground(.hidden)
        .background(DailyBreathThemeBackground(theme: selectedTheme))
    }
}
