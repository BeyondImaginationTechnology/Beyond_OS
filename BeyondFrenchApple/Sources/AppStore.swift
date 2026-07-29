import AVFoundation
import Combine
import Foundation

@MainActor
final class AppStore: ObservableObject {
    @Published private(set) var lesson = FrenchLesson.fallback
    @Published private(set) var dictionary: [DictionaryWord] = []
    @Published private(set) var isRefreshing = false
    @Published private(set) var statusMessage = "Free daily lesson"
    @Published var hasBeyondID = false

    private let endpoint = URL(string: "https://beyondimagination.co.technology/beyond-french/api/today.php")!
    private let speaker = AVSpeechSynthesizer()

    func load() async {
        loadDictionary()
        await refreshLesson()
    }

    func refreshLesson() async {
        isRefreshing = true
        defer { isRefreshing = false }
        do {
            let (data, response) = try await URLSession.shared.data(from: endpoint)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200 else { throw URLError(.badServerResponse) }
            lesson = try JSONDecoder().decode(TodayResponse.self, from: data).lesson
            statusMessage = "Updated from Daily Studio"
        } catch {
            statusMessage = "Offline lesson"
        }
    }

    func speak(_ text: String, language: String = "fr-FR") {
        speaker.stopSpeaking(at: .immediate)
        let utterance = AVSpeechUtterance(string: text)
        utterance.voice = AVSpeechSynthesisVoice(language: language)
        utterance.rate = 0.43
        speaker.speak(utterance)
    }

    private func loadDictionary() {
        guard let url = Bundle.main.url(forResource: "dictionary", withExtension: "json"),
              let data = try? Data(contentsOf: url),
              let words = try? JSONDecoder().decode([DictionaryWord].self, from: data) else { return }
        dictionary = words
    }
}
