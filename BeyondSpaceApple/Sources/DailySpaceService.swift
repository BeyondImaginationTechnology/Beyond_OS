import Foundation

struct DailySpaceEnvelope: Decodable {
    let date: String
    let fact: RemoteDailyFact?
}

struct RemoteDailyFact: Decodable {
    let number: Int
    let title: String
    let fact: String
    let lesson: String
    let sourceName: String?
    let sourceURL: URL?
    let academyURL: URL?
    let distribution: SpaceDistribution?

    enum CodingKeys: String, CodingKey {
        case number, title, fact, lesson
        case sourceName = "source_name"
        case sourceURL = "source_url"
        case academyURL = "academy_url"
        case distribution
    }
}

struct SpaceDistribution: Decodable {
    let spaceTVURL: URL?
    let youtubeURL: URL?

    enum CodingKeys: String, CodingKey {
        case spaceTVURL = "space_tv_url"
        case youtubeURL = "youtube_url"
    }
}

enum DailySpaceService {
    static let endpoint = URL(string: "https://beyondimagination.co.technology/beyond-space/api/daily-space-fact.php")!

    static func today() async throws -> (fact: RemoteDailyFact, date: String) {
        var request = URLRequest(url: endpoint)
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let response = response as? HTTPURLResponse, (200..<300).contains(response.statusCode) else {
            throw URLError(.badServerResponse)
        }
        let decoder = JSONDecoder()
        let envelope = try decoder.decode(DailySpaceEnvelope.self, from: data)
        guard let fact = envelope.fact,
              fact.sourceURL != nil,
              fact.academyURL != nil else {
            throw URLError(.cannotParseResponse)
        }
        return (fact, envelope.date)
    }
}
