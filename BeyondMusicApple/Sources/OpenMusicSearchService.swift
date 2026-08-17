import Foundation

struct OpenMusicSearchService {
    func search(query: String, page: Int) async throws -> MusicSearchPage {
        let providerResults = await withTaskGroup(of: (String, Result<[MusicTrack], Error>).self) { group in
            group.addTask { ("Internet Archive", await providerResult { try await searchInternetArchive(query: query, page: page) }) }
            group.addTask { ("Mixtapes", await providerResult { try await searchInternetArchiveMixtapes(query: query, page: page) }) }
            group.addTask { ("Audius", await providerResult { try await searchAudius(query: query, page: page) }) }
            group.addTask { ("ccMixter", await providerResult { try await searchCCMixter(query: query, page: page) }) }

            var results: [(String, Result<[MusicTrack], Error>)] = []
            for await result in group {
                results.append(result)
            }
            return results
        }

        var summaries: [String] = []
        let tracks = providerResults.flatMap { provider, result -> [MusicTrack] in
            switch result {
            case .success(let tracks):
                if !tracks.isEmpty {
                    summaries.append("\(provider) \(tracks.count)")
                }
                return tracks
            case .failure:
                return []
            }
        }

        let deduped = tracks.reduce(into: [MusicTrack]()) { partialResult, track in
            let duplicate = partialResult.contains { existing in
                existing.id == track.id || (existing.title == track.title && existing.artist == track.artist)
            }
            if !duplicate {
                partialResult.append(track)
            }
        }

        return MusicSearchPage(query: query, page: page, tracks: providerBalancedTracks(deduped), providerSummaries: summaries)
    }

    private func providerResult(_ operation: () async throws -> [MusicTrack]) async -> Result<[MusicTrack], Error> {
        do {
            return .success(try await operation())
        } catch {
            return .failure(error)
        }
    }

    private func searchInternetArchive(query: String, page: Int) async throws -> [MusicTrack] {
        let searchURL = try archiveSearchURL(query: query, page: page, collection: nil)
        let (data, _) = try await URLSession.shared.data(from: searchURL)
        let response = try JSONDecoder().decode(ArchiveSearchResponse.self, from: data)
        let documents = Array(response.response.docs.prefix(6))
        var tracks: [MusicTrack] = []

        for document in documents {
            let documentTracks = (try? await archiveTracks(from: document, providerName: "Internet Archive", fileLimit: 1)) ?? []
            tracks.append(contentsOf: documentTracks)
        }

        return tracks
    }

    private func searchInternetArchiveMixtapes(query: String, page: Int) async throws -> [MusicTrack] {
        let searchURL = try archiveSearchURL(query: query, page: page, collection: "hiphopmixtapes")
        let (data, _) = try await URLSession.shared.data(from: searchURL)
        let response = try JSONDecoder().decode(ArchiveSearchResponse.self, from: data)
        let documents = Array(response.response.docs.prefix(5))
        var tracks: [MusicTrack] = []

        for document in documents {
            let documentTracks = (try? await archiveTracks(from: document, providerName: "Mixtape Archive", fileLimit: 3)) ?? []
            tracks.append(contentsOf: documentTracks)
        }

        return Array(tracks.prefix(12))
    }

    private func archiveSearchURL(query: String, page: Int, collection: String?) throws -> URL {
        var components = URLComponents(string: "https://archive.org/advancedsearch.php")
        let collectionClause = collection.map { "collection:\($0)" } ?? "(licenseurl:* OR collection:opensource_audio OR subject:\"public domain\")"
        let archiveQuery = "mediatype:audio AND \(collectionClause) AND (title:(\(query)) OR creator:(\(query)) OR subject:(\(query)) OR description:(\(query)))"
        components?.queryItems = [
            URLQueryItem(name: "q", value: archiveQuery),
            URLQueryItem(name: "fl[]", value: "identifier"),
            URLQueryItem(name: "fl[]", value: "title"),
            URLQueryItem(name: "fl[]", value: "creator"),
            URLQueryItem(name: "fl[]", value: "licenseurl"),
            URLQueryItem(name: "sort[]", value: page.isMultiple(of: 2) ? "random" : "downloads desc"),
            URLQueryItem(name: "rows", value: "12"),
            URLQueryItem(name: "page", value: "\(max(1, page))"),
            URLQueryItem(name: "output", value: "json")
        ]
        guard let url = components?.url else { throw URLError(.badURL) }
        return url
    }

    private func archiveTracks(from document: ArchiveDocument, providerName: String, fileLimit: Int) async throws -> [MusicTrack] {
        let metadataURL = URL(string: "https://archive.org/metadata/\(document.identifier)")!
        let (data, _) = try await URLSession.shared.data(from: metadataURL)
        let metadata = try JSONDecoder().decode(ArchiveMetadata.self, from: data)
        let playableFiles = Array(metadata.files.filter(\.isPlayableAudio).prefix(max(1, fileLimit)))
        guard !playableFiles.isEmpty else { return [] }
        let creator = document.creatorText.isEmpty ? "Open Archive" : document.creatorText
        return playableFiles.map { file in
            let fileURL = archiveDownloadURL(identifier: document.identifier, fileName: file.name)
            return MusicTrack(
                id: "\(document.identifier)-\(file.name)".stableMusicID,
                title: playableFiles.count == 1 ? (document.title ?? file.name.removingAudioExtension) : file.name.removingAudioExtension,
                artist: creator,
                album: metadata.metadata?.title ?? document.title,
                durationSeconds: file.lengthSeconds,
                mood: .focus,
                streamURL: fileURL,
                downloadURL: fileURL,
                artworkURL: URL(string: "https://archive.org/services/img/\(document.identifier)"),
                sourceURL: URL(string: "https://archive.org/details/\(document.identifier)"),
                licenseNote: document.licenseurl ?? "Review source license",
                providerName: providerName,
                localFileName: nil,
                originalFileName: nil,
                importedAt: nil
            )
        }
    }

    private func archiveDownloadURL(identifier: String, fileName: String) -> URL? {
        var components = URLComponents()
        components.scheme = "https"
        components.host = "archive.org"
        components.path = "/download/\(identifier)/\(fileName)"
        return components.url
    }

    private func searchAudius(query: String, page: Int) async throws -> [MusicTrack] {
        var components = URLComponents(string: "https://api.audius.co/v1/tracks/search")
        components?.queryItems = [
            URLQueryItem(name: "query", value: query),
            URLQueryItem(name: "limit", value: "12"),
            URLQueryItem(name: "offset", value: "\(max(0, page - 1) * 12)"),
            URLQueryItem(name: "has_downloads", value: "true")
        ]
        guard let url = components?.url else { throw URLError(.badURL) }
        let (data, response) = try await URLSession.shared.data(from: url)
        try validateHTTPResponse(response)
        let payload = try JSONDecoder().decode(AudiusSearchResponse.self, from: data)
        return payload.data.compactMap(audiusTrack)
    }

    private func audiusTrack(from item: AudiusTrackItem) -> MusicTrack? {
        let sourceURL = URL(string: "https://audius.co\(item.permalink)")
        let streamURL = item.isStreamable == true ? URL(string: "https://api.audius.co/v1/tracks/\(item.id)/stream") : nil
        let downloadURL = item.isDownloadable == true && item.isDownloadGated != true ? URL(string: "https://api.audius.co/v1/tracks/\(item.id)/download") : nil
        guard streamURL != nil || downloadURL != nil else { return nil }
        return MusicTrack(
            id: "audius-\(item.id)".stableMusicID,
            title: item.title.decodingHTMLEntities,
            artist: item.user?.displayName,
            album: item.genre,
            durationSeconds: item.duration,
            mood: .focus,
            streamURL: streamURL,
            downloadURL: downloadURL,
            artworkURL: item.artwork?.largestURL,
            sourceURL: sourceURL,
            licenseNote: item.license ?? (downloadURL == nil ? "Streaming via Audius" : "Downloadable via Audius"),
            providerName: "Audius",
            localFileName: nil,
            originalFileName: nil,
            importedAt: nil
        )
    }

    private func searchCCMixter(query: String, page: Int) async throws -> [MusicTrack] {
        var components = URLComponents(string: "https://ccmixter.org/api/query")
        components?.queryItems = [
            URLQueryItem(name: "search", value: query),
            URLQueryItem(name: "f", value: "json"),
            URLQueryItem(name: "limit", value: "10"),
            URLQueryItem(name: "offset", value: "\(max(0, page - 1) * 10)")
        ]
        guard let url = components?.url else { throw URLError(.badURL) }
        let (data, response) = try await URLSession.shared.data(from: url)
        try validateHTTPResponse(response)
        let payload = try JSONDecoder().decode([CCMixterUpload].self, from: data)
        return payload.compactMap(ccMixterTrack)
    }

    private func ccMixterTrack(from upload: CCMixterUpload) -> MusicTrack? {
        guard let file = upload.files.first(where: { $0.downloadURL != nil && $0.isPlayableAudio }),
              let downloadURL = file.downloadURL
        else { return nil }
        let artist = upload.userRealName?.isEmpty == false ? upload.userRealName : upload.userName
        return MusicTrack(
            id: "ccmixter-\(upload.uploadID)-\(file.fileID)".stableMusicID,
            title: upload.uploadName.decodingHTMLEntities,
            artist: artist,
            album: nil,
            durationSeconds: file.durationSeconds,
            mood: .focus,
            streamURL: downloadURL,
            downloadURL: downloadURL,
            artworkURL: nil,
            sourceURL: upload.filePageURL,
            licenseNote: upload.licenseName ?? upload.licenseURL?.absoluteString ?? "Creative Commons",
            providerName: "ccMixter",
            localFileName: nil,
            originalFileName: nil,
            importedAt: nil
        )
    }

    private func validateHTTPResponse(_ response: URLResponse) throws {
        guard let httpResponse = response as? HTTPURLResponse,
              !(200...299).contains(httpResponse.statusCode)
        else { return }
        throw URLError(.badServerResponse)
    }

    private func searchYouTube(query: String, page: Int) async throws -> [MusicTrack] {
        if let youtubeURL = youtubeURL(from: query) {
            return [youtubeTrack(title: "YouTube \(youtubeVideoID(from: youtubeURL) ?? "audio")", url: youtubeURL)]
        }

        guard page == 1 else { return [] }
        if let proxyURL = youtubeProxyURL(query: query),
           let tracks = try? await fetchYouTubeTracks(from: proxyURL) {
            return tracks
        }

        guard let directURL = directYouTubeSearchURL(query: query) else { return [] }
        return try await fetchYouTubeTracks(from: directURL)
    }

    private func youtubeProxyURL(query: String) -> URL? {
        let rawBaseURL = Bundle.main.object(forInfoDictionaryKey: "BeyondMusicAPIBaseURL") as? String
        guard let baseURL = URL(string: rawBaseURL?.trimmingCharacters(in: .whitespacesAndNewlines) ?? "") else { return nil }
        var components = URLComponents(url: baseURL.appending(path: "/beyond-media/api/youtube-search.php"), resolvingAgainstBaseURL: false)
        components?.queryItems = [URLQueryItem(name: "q", value: query)]
        return components?.url
    }

    private func directYouTubeSearchURL(query: String) -> URL? {
        guard let key = Bundle.main.object(forInfoDictionaryKey: "YouTubeDataAPIKey") as? String,
              !key.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
        else { return nil }
        var components = URLComponents(string: "https://www.googleapis.com/youtube/v3/search")
        components?.queryItems = [
            URLQueryItem(name: "part", value: "snippet"),
            URLQueryItem(name: "type", value: "video"),
            URLQueryItem(name: "videoCategoryId", value: "10"),
            URLQueryItem(name: "maxResults", value: "25"),
            URLQueryItem(name: "q", value: query),
            URLQueryItem(name: "key", value: key)
        ]
        return components?.url
    }

    private func fetchYouTubeTracks(from url: URL) async throws -> [MusicTrack] {
        let (data, response) = try await URLSession.shared.data(from: url)
        if let httpResponse = response as? HTTPURLResponse,
           !(200...299).contains(httpResponse.statusCode) {
            throw URLError(.badServerResponse)
        }
        let payload = try JSONDecoder().decode(YouTubeSearchResponse.self, from: data)
        return payload.items.compactMap { item in
            guard let videoID = item.id.videoID,
                  let sourceURL = URL(string: "https://www.youtube.com/watch?v=\(videoID)")
            else { return nil }
            return youtubeTrack(
                id: "youtube-\(videoID)".stableMusicID,
                title: item.snippet.title.decodingHTMLEntities,
                artist: item.snippet.channelTitle,
                artworkURL: item.snippet.thumbnails.high?.url ?? item.snippet.thumbnails.medium?.url ?? item.snippet.thumbnails.default?.url,
                url: sourceURL
            )
        }
    }

    private func youtubeTrack(id: String? = nil, title: String, artist: String? = nil, artworkURL: URL? = nil, url: URL) -> MusicTrack {
        let videoID = youtubeVideoID(from: url) ?? url.absoluteString.stableMusicID
        return MusicTrack(
            id: id ?? "youtube-\(videoID)".stableMusicID,
            title: title,
            artist: artist,
            album: nil,
            durationSeconds: nil,
            mood: .focus,
            streamURL: nil,
            downloadURL: nil,
            artworkURL: artworkURL,
            sourceURL: url,
            licenseNote: "YouTube",
            providerName: "YouTube",
            localFileName: nil,
            originalFileName: nil,
            importedAt: nil
        )
    }

    private func youtubeURL(from value: String) -> URL? {
        guard let url = URL(string: value.trimmingCharacters(in: .whitespacesAndNewlines)),
              let host = url.host(percentEncoded: false)?.lowercased(),
              host == "youtu.be" || host.hasSuffix(".youtube.com") || host == "youtube.com",
              youtubeVideoID(from: url) != nil
        else { return nil }
        return url
    }

    private func youtubeVideoID(from url: URL) -> String? {
        let host = url.host(percentEncoded: false)?.lowercased() ?? ""
        if host == "youtu.be" {
            return url.pathComponents.dropFirst().first
        }
        if url.pathComponents.contains("shorts"),
           let index = url.pathComponents.firstIndex(of: "shorts"),
           url.pathComponents.indices.contains(index + 1) {
            return url.pathComponents[index + 1]
        }
        return URLComponents(url: url, resolvingAgainstBaseURL: false)?
            .queryItems?
            .first(where: { $0.name == "v" })?
            .value
    }

    private func providerBalancedTracks(_ tracks: [MusicTrack]) -> [MusicTrack] {
        var mixtapes = tracks.filter { $0.providerName == "Mixtape Archive" }
        var audius = tracks.filter { $0.providerName == "Audius" }
        var ccMixter = tracks.filter { $0.providerName == "ccMixter" }
        var archive = tracks.filter { $0.providerName == "Internet Archive" }
        var other = tracks.filter { $0.providerName != "YouTube" && $0.providerName != "Mixtape Archive" && $0.providerName != "Audius" && $0.providerName != "ccMixter" && $0.providerName != "Internet Archive" }
        var balanced: [MusicTrack] = []

        while !mixtapes.isEmpty || !audius.isEmpty || !ccMixter.isEmpty || !archive.isEmpty || !other.isEmpty {
            if !mixtapes.isEmpty {
                balanced.append(mixtapes.removeFirst())
            }
            if !audius.isEmpty {
                balanced.append(audius.removeFirst())
            }
            if !ccMixter.isEmpty {
                balanced.append(ccMixter.removeFirst())
            }
            if !archive.isEmpty {
                balanced.append(archive.removeFirst())
            }
            if !other.isEmpty {
                balanced.append(other.removeFirst())
            }
        }

        return balanced
    }
}

private struct ArchiveSearchResponse: Decodable {
    let response: ArchiveSearchBody
}

private struct ArchiveSearchBody: Decodable {
    let docs: [ArchiveDocument]
}

private struct ArchiveDocument: Decodable {
    let identifier: String
    let title: String?
    let creator: ArchiveCreator?
    let licenseurl: String?

    var creatorText: String {
        switch creator {
        case .string(let value): value
        case .strings(let values): values.joined(separator: ", ")
        case .none: ""
        }
    }
}

private enum ArchiveCreator: Decodable {
    case string(String)
    case strings([String])

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()
        if let value = try? container.decode(String.self) {
            self = .string(value)
        } else {
            self = .strings((try? container.decode([String].self)) ?? [])
        }
    }
}

private struct ArchiveMetadata: Decodable {
    let metadata: ArchiveItemMetadata?
    let files: [ArchiveFile]
}

private struct ArchiveItemMetadata: Decodable {
    let title: String?
}

private struct ArchiveFile: Decodable {
    let name: String
    let format: String?
    let length: String?

    var isPlayableAudio: Bool {
        let supportedExtensions = ["mp3", "m4a", "aac", "wav"]
        return supportedExtensions.contains(URL(filePath: name).pathExtension.lowercased())
    }

    var lengthSeconds: Int? {
        guard let length, let value = Double(length) else { return nil }
        return Int(value.rounded())
    }
}

private struct YouTubeSearchResponse: Decodable {
    let items: [YouTubeSearchItem]
}

private struct YouTubeSearchItem: Decodable {
    let id: YouTubeSearchID
    let snippet: YouTubeSnippet
}

private struct YouTubeSearchID: Decodable {
    let videoID: String?

    enum CodingKeys: String, CodingKey {
        case videoID = "videoId"
    }
}

private struct YouTubeSnippet: Decodable {
    let title: String
    let channelTitle: String?
    let thumbnails: YouTubeThumbnails
}

private struct YouTubeThumbnails: Decodable {
    let `default`: YouTubeThumbnail?
    let medium: YouTubeThumbnail?
    let high: YouTubeThumbnail?
}

private struct YouTubeThumbnail: Decodable {
    let url: URL?
}

private struct AudiusSearchResponse: Decodable {
    let data: [AudiusTrackItem]
}

private struct AudiusTrackItem: Decodable {
    let id: String
    let title: String
    let user: AudiusUser?
    let genre: String?
    let duration: Int?
    let permalink: String
    let artwork: AudiusArtwork?
    let license: String?
    let isDownloadable: Bool?
    let isDownloadGated: Bool?
    let isStreamable: Bool?

    enum CodingKeys: String, CodingKey {
        case id
        case title
        case user
        case genre
        case duration
        case permalink
        case artwork
        case license
        case isDownloadable = "is_downloadable"
        case isDownloadGated = "is_download_gated"
        case isStreamable = "is_streamable"
    }
}

private struct AudiusUser: Decodable {
    let name: String?
    let handle: String?

    var displayName: String {
        if let name, !name.isEmpty { return name }
        return handle ?? "Audius Artist"
    }
}

private struct AudiusArtwork: Decodable {
    let small: URL?
    let medium: URL?
    let large: URL?

    enum CodingKeys: String, CodingKey {
        case small = "150x150"
        case medium = "480x480"
        case large = "1000x1000"
    }

    var largestURL: URL? {
        large ?? medium ?? small
    }
}

private struct CCMixterUpload: Decodable {
    let uploadID: Int
    let uploadName: String
    let userName: String?
    let userRealName: String?
    let filePageURL: URL?
    let licenseURL: URL?
    let licenseName: String?
    let files: [CCMixterFile]

    enum CodingKeys: String, CodingKey {
        case uploadID = "upload_id"
        case uploadName = "upload_name"
        case userName = "user_name"
        case userRealName = "user_real_name"
        case filePageURL = "file_page_url"
        case licenseURL = "license_url"
        case licenseName = "license_name"
        case files
    }
}

private struct CCMixterFile: Decodable {
    let fileID: Int
    let fileName: String
    let downloadURL: URL?
    let formatInfo: CCMixterFileFormat?

    enum CodingKeys: String, CodingKey {
        case fileID = "file_id"
        case fileName = "file_name"
        case downloadURL = "download_url"
        case formatInfo = "file_format_info"
    }

    var isPlayableAudio: Bool {
        let supportedExtensions = ["mp3", "m4a", "aac", "wav", "flac"]
        return supportedExtensions.contains(URL(filePath: fileName).pathExtension.lowercased())
    }

    var durationSeconds: Int? {
        guard let parts = formatInfo?.durationText?.split(separator: ":").compactMap({ Int($0) }),
              parts.count == 2
        else { return nil }
        return parts[0] * 60 + parts[1]
    }
}

private struct CCMixterFileFormat: Decodable {
    let durationText: String?

    enum CodingKeys: String, CodingKey {
        case durationText = "ps"
    }
}

private extension String {
    var removingAudioExtension: String {
        URL(filePath: self).deletingPathExtension().lastPathComponent
    }

    var stableMusicID: String {
        lowercased()
            .map { character in character.isLetter || character.isNumber ? character : "-" }
            .reduce(into: "") { result, character in
                if character != "-" || result.last != "-" {
                    result.append(character)
                }
            }
            .trimmingCharacters(in: CharacterSet(charactersIn: "-"))
    }

    var decodingHTMLEntities: String {
        guard let data = data(using: .utf8),
              let decoded = try? NSAttributedString(
                data: data,
                options: [.documentType: NSAttributedString.DocumentType.html, .characterEncoding: String.Encoding.utf8.rawValue],
                documentAttributes: nil
              ).string
        else { return self }
        return decoded
    }
}
