import Foundation

struct OpenMusicSearchService {
    func search(query: String, page: Int) async throws -> MusicSearchPage {
        let providerResults = await withTaskGroup(of: (String, Result<[MusicTrack], Error>).self) { group in
            group.addTask { ("Internet Archive", await providerResult { try await searchInternetArchive(query: query, page: page) }) }
            group.addTask { ("YouTube", await providerResult { try await searchYouTube(query: query, page: page) }) }

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

        return MusicSearchPage(query: query, page: page, tracks: deduped.shuffled(), providerSummaries: summaries)
    }

    private func providerResult(_ operation: () async throws -> [MusicTrack]) async -> Result<[MusicTrack], Error> {
        do {
            return .success(try await operation())
        } catch {
            return .failure(error)
        }
    }

    private func searchInternetArchive(query: String, page: Int) async throws -> [MusicTrack] {
        let searchURL = try archiveSearchURL(query: query, page: page)
        let (data, _) = try await URLSession.shared.data(from: searchURL)
        let response = try JSONDecoder().decode(ArchiveSearchResponse.self, from: data)
        let documents = Array(response.response.docs.prefix(8))
        var tracks: [MusicTrack] = []

        for document in documents {
            guard let track = try? await archiveTrack(from: document) else { continue }
            tracks.append(track)
        }

        return tracks
    }

    private func archiveSearchURL(query: String, page: Int) throws -> URL {
        var components = URLComponents(string: "https://archive.org/advancedsearch.php")
        let archiveQuery = "mediatype:audio AND (licenseurl:* OR collection:opensource_audio OR subject:\"public domain\") AND (title:(\(query)) OR creator:(\(query)) OR subject:(\(query)))"
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

    private func archiveTrack(from document: ArchiveDocument) async throws -> MusicTrack? {
        let metadataURL = URL(string: "https://archive.org/metadata/\(document.identifier)")!
        let (data, _) = try await URLSession.shared.data(from: metadataURL)
        let metadata = try JSONDecoder().decode(ArchiveMetadata.self, from: data)
        guard let file = metadata.files.first(where: { $0.isPlayableAudio }) else { return nil }
        let fileURL = archiveDownloadURL(identifier: document.identifier, fileName: file.name)
        let creator = document.creatorText.isEmpty ? "Open Archive" : document.creatorText
        return MusicTrack(
            id: "\(document.identifier)-\(file.name)".stableMusicID,
            title: document.title ?? file.name.removingAudioExtension,
            artist: creator,
            album: metadata.metadata?.title,
            durationSeconds: file.lengthSeconds,
            mood: .focus,
            streamURL: fileURL,
            downloadURL: fileURL,
            artworkURL: URL(string: "https://archive.org/services/img/\(document.identifier)"),
            sourceURL: URL(string: "https://archive.org/details/\(document.identifier)"),
            licenseNote: document.licenseurl ?? "Review source license",
            providerName: "Internet Archive",
            localFileName: nil,
            originalFileName: nil,
            importedAt: nil
        )
    }

    private func archiveDownloadURL(identifier: String, fileName: String) -> URL? {
        var components = URLComponents()
        components.scheme = "https"
        components.host = "archive.org"
        components.path = "/download/\(identifier)/\(fileName)"
        return components.url
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
            URLQueryItem(name: "maxResults", value: "10"),
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
