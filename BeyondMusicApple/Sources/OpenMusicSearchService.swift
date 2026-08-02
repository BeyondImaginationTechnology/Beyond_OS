import Foundation

struct OpenMusicSearchService {
    private let fallbackQueries = [
        "public domain piano",
        "creative commons jazz",
        "classical guitar",
        "ambient instrumental",
        "lofi beat",
        "synthwave",
        "folk acoustic",
        "orchestral public domain",
        "study music",
        "electronic instrumental"
    ]

    func randomDiscoveryQuery() -> String {
        fallbackQueries.randomElement() ?? "creative commons music"
    }

    func search(query: String, page: Int) async throws -> MusicSearchPage {
        let providerResults = await withTaskGroup(of: (String, Result<[MusicTrack], Error>).self) { group in
            group.addTask { ("Internet Archive", await providerResult { try await searchInternetArchive(query: query, page: page) }) }
            group.addTask { ("Wikimedia Commons", await providerResult { try await searchWikimediaCommons(query: query, page: page) }) }
            group.addTask { ("Jamendo", await providerResult { try await searchJamendo(query: query, page: page) }) }
            group.addTask { ("Freesound", await providerResult { try await searchFreesound(query: query, page: page) }) }

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

        if deduped.isEmpty && page == 1 {
            let fallback = fallbackQueries.shuffled().prefix(2)
            var fallbackTracks: [MusicTrack] = []
            for fallbackQuery in fallback {
                fallbackTracks += (try? await searchInternetArchive(query: fallbackQuery, page: Int.random(in: 1...3))) ?? []
                fallbackTracks += (try? await searchWikimediaCommons(query: fallbackQuery, page: Int.random(in: 1...3))) ?? []
            }
            let randomTracks = fallbackTracks.shuffled().prefix(18)
            return MusicSearchPage(query: query, page: page, tracks: Array(randomTracks), providerSummaries: ["Random open-audio fallback \(randomTracks.count)"])
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

    private func searchWikimediaCommons(query: String, page: Int) async throws -> [MusicTrack] {
        var components = URLComponents(string: "https://commons.wikimedia.org/w/api.php")
        components?.queryItems = [
            URLQueryItem(name: "action", value: "query"),
            URLQueryItem(name: "format", value: "json"),
            URLQueryItem(name: "generator", value: "search"),
            URLQueryItem(name: "gsrnamespace", value: "6"),
            URLQueryItem(name: "gsrsearch", value: "\(query) filetype:audio"),
            URLQueryItem(name: "gsrlimit", value: "12"),
            URLQueryItem(name: "gsroffset", value: "\(max(0, (page - 1) * 12))"),
            URLQueryItem(name: "prop", value: "imageinfo"),
            URLQueryItem(name: "iiprop", value: "url|mime|extmetadata|size"),
            URLQueryItem(name: "iiurlwidth", value: "300")
        ]
        guard let url = components?.url else { throw URLError(.badURL) }
        let (data, _) = try await URLSession.shared.data(from: url)
        let response = try JSONDecoder().decode(CommonsSearchResponse.self, from: data)

        return response.query?.pages.values.compactMap { page in
            guard let info = page.imageinfo?.first,
                  info.mime?.hasPrefix("audio/") == true,
                  let streamURL = URL(string: info.url)
            else { return nil }

            let cleanTitle = page.title.replacingOccurrences(of: "File:", with: "").removingAudioExtension
            let artist = info.extmetadata?.artist?.value.strippingHTML ?? "Wikimedia Commons"
            let license = info.extmetadata?.licenseShortName?.value.strippingHTML ?? "Free license"
            return MusicTrack(
                id: "commons-\(page.pageid)-\(cleanTitle)".stableMusicID,
                title: cleanTitle,
                artist: artist.isEmpty ? "Wikimedia Commons" : artist,
                album: nil,
                durationSeconds: nil,
                mood: .focus,
                streamURL: streamURL,
                downloadURL: streamURL,
                artworkURL: info.thumburl.flatMap(URL.init(string:)),
                sourceURL: URL(string: info.descriptionurl),
                licenseNote: license,
                providerName: "Wikimedia Commons",
                localFileName: nil,
                originalFileName: nil,
                importedAt: nil
            )
        } ?? []
    }

    private func searchJamendo(query: String, page: Int) async throws -> [MusicTrack] {
        guard let clientID = Bundle.main.object(forInfoDictionaryKey: "JamendoClientID") as? String,
              !clientID.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
        else { return [] }

        var components = URLComponents(string: "https://api.jamendo.com/v3.0/tracks/")
        components?.queryItems = [
            URLQueryItem(name: "client_id", value: clientID),
            URLQueryItem(name: "format", value: "json"),
            URLQueryItem(name: "limit", value: "15"),
            URLQueryItem(name: "offset", value: "\(max(0, (page - 1) * 15))"),
            URLQueryItem(name: "search", value: query),
            URLQueryItem(name: "include", value: "licenses+musicinfo"),
            URLQueryItem(name: "audioformat", value: "mp32"),
            URLQueryItem(name: "order", value: page.isMultiple(of: 2) ? "popularity_month" : "relevance")
        ]
        guard let url = components?.url else { throw URLError(.badURL) }
        let (data, _) = try await URLSession.shared.data(from: url)
        let response = try JSONDecoder().decode(JamendoResponse.self, from: data)

        return response.results.compactMap { item in
            guard let streamURL = URL(string: item.audio) else { return nil }
            let downloadURL = item.audiodownloadAllowed == true ? URL(string: item.audiodownload ?? "") : nil
            return MusicTrack(
                id: "jamendo-\(item.id)".stableMusicID,
                title: item.name,
                artist: item.artistName,
                album: item.albumName,
                durationSeconds: item.duration,
                mood: .focus,
                streamURL: streamURL,
                downloadURL: downloadURL ?? streamURL,
                artworkURL: URL(string: item.albumImage ?? ""),
                sourceURL: URL(string: item.shareurl ?? ""),
                licenseNote: item.licenseCCURL ?? "Jamendo license",
                providerName: "Jamendo",
                localFileName: nil,
                originalFileName: nil,
                importedAt: nil
            )
        }
    }

    private func searchFreesound(query: String, page: Int) async throws -> [MusicTrack] {
        guard let token = Bundle.main.object(forInfoDictionaryKey: "FreesoundAPIToken") as? String,
              !token.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
        else { return [] }

        var components = URLComponents(string: "https://freesound.org/apiv2/search/")
        components?.queryItems = [
            URLQueryItem(name: "query", value: query),
            URLQueryItem(name: "page", value: "\(max(1, page))"),
            URLQueryItem(name: "page_size", value: "15"),
            URLQueryItem(name: "fields", value: "id,name,username,license,duration,url,previews,type"),
            URLQueryItem(name: "filter", value: "license:\"Creative Commons 0\" OR license:\"Attribution\"")
        ]
        guard let url = components?.url else { throw URLError(.badURL) }
        var request = URLRequest(url: url)
        request.setValue("Token \(token)", forHTTPHeaderField: "Authorization")
        let (data, _) = try await URLSession.shared.data(for: request)
        let response = try JSONDecoder().decode(FreesoundResponse.self, from: data)

        return response.results.compactMap { sound in
            guard let previewURL = sound.previews.previewURL else { return nil }
            return MusicTrack(
                id: "freesound-\(sound.id)".stableMusicID,
                title: sound.name.removingAudioExtension,
                artist: sound.username,
                album: nil,
                durationSeconds: Int(sound.duration.rounded()),
                mood: .focus,
                streamURL: previewURL,
                downloadURL: previewURL,
                artworkURL: nil,
                sourceURL: URL(string: sound.url),
                licenseNote: sound.license,
                providerName: "Freesound",
                localFileName: nil,
                originalFileName: nil,
                importedAt: nil
            )
        }
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

private struct CommonsSearchResponse: Decodable {
    let query: CommonsQuery?
}

private struct CommonsQuery: Decodable {
    let pages: [String: CommonsPage]
}

private struct CommonsPage: Decodable {
    let pageid: Int
    let title: String
    let imageinfo: [CommonsImageInfo]?
}

private struct CommonsImageInfo: Decodable {
    let url: String
    let descriptionurl: String
    let thumburl: String?
    let mime: String?
    let extmetadata: CommonsMetadata?
}

private struct CommonsMetadata: Decodable {
    let artist: CommonsMetadataValue?
    let licenseShortName: CommonsMetadataValue?

    enum CodingKeys: String, CodingKey {
        case artist = "Artist"
        case licenseShortName = "LicenseShortName"
    }
}

private struct CommonsMetadataValue: Decodable {
    let value: String
}

private struct JamendoResponse: Decodable {
    let results: [JamendoTrack]
}

private struct JamendoTrack: Decodable {
    let id: String
    let name: String
    let duration: Int
    let artistName: String
    let albumName: String
    let albumImage: String?
    let audio: String
    let audiodownload: String?
    let audiodownloadAllowed: Bool?
    let licenseCCURL: String?
    let shareurl: String?

    enum CodingKeys: String, CodingKey {
        case id
        case name
        case duration
        case artistName = "artist_name"
        case albumName = "album_name"
        case albumImage = "album_image"
        case audio
        case audiodownload
        case audiodownloadAllowed = "audiodownload_allowed"
        case licenseCCURL = "license_ccurl"
        case shareurl
    }
}

private struct FreesoundResponse: Decodable {
    let results: [FreesoundSound]
}

private struct FreesoundSound: Decodable {
    let id: Int
    let name: String
    let username: String
    let license: String
    let duration: Double
    let url: String
    let previews: FreesoundPreviews
}

private struct FreesoundPreviews: Decodable {
    let highQualityMP3: String?
    let lowQualityMP3: String?

    var previewURL: URL? {
        URL(string: highQualityMP3 ?? lowQualityMP3 ?? "")
    }

    enum CodingKeys: String, CodingKey {
        case highQualityMP3 = "preview-hq-mp3"
        case lowQualityMP3 = "preview-lq-mp3"
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

    var strippingHTML: String {
        replacingOccurrences(of: "<[^>]+>", with: "", options: .regularExpression)
            .replacingOccurrences(of: "&amp;", with: "&")
            .replacingOccurrences(of: "&quot;", with: "\"")
            .trimmingCharacters(in: .whitespacesAndNewlines)
    }
}
