import SwiftUI

private enum TranslateMode: String, CaseIterable, Identifiable {
    case translate
    case ask

    var id: String { rawValue }
    var title: String { self == .translate ? "Translate" : "Ask" }
}

private enum SourceLanguage: String, CaseIterable, Identifiable {
    case english, spanish, kreyol, patois, french

    var id: String { rawValue }
    var title: String {
        switch self {
        case .english: "English"
        case .spanish: "Spanish"
        case .kreyol: "Haitian Kreyòl"
        case .patois: "Jamaican Patois"
        case .french: "French"
        }
    }
}

struct TranslateView: View {
    @EnvironmentObject private var store: AppStore
    @State private var mode: TranslateMode = .translate
    @State private var source: SourceLanguage = .english
    @State private var input = ""
    @State private var output = ""
    @State private var pronunciation = ""
    @State private var isLoading = false

    private let endpoint = URL(string: "https://beyondimagination.co.technology/beyond-french/api/jaguar.php")!

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                Text("French is the destination")
                    .font(.largeTitle.weight(.black))
                Text("Translate a phrase into French or ask for a French language tip.")
                    .foregroundStyle(.secondary)

                Picker("Mode", selection: $mode) {
                    ForEach(TranslateMode.allCases) { option in
                        Text(option.title).tag(option)
                    }
                }
                .pickerStyle(.segmented)

                if mode == .translate {
                    HStack {
                        Picker("From", selection: $source) {
                            ForEach(SourceLanguage.allCases) { language in
                                Text(language.title).tag(language)
                            }
                        }
                        Spacer()
                        Label("French", systemImage: "arrow.right")
                            .font(.subheadline.weight(.bold))
                            .foregroundStyle(store.appTheme.accent)
                    }
                    .padding(12)
                    .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 14))
                }

                Text(mode == .translate ? "YOUR PHRASE" : "ASK ABOUT FRENCH")
                    .font(.caption.weight(.black))
                    .tracking(1.4)
                    .foregroundStyle(store.appTheme.accent)
                TextEditor(text: $input)
                    .frame(minHeight: 140)
                    .scrollContentBackground(.hidden)
                    .padding(12)
                    .background(Color.white.opacity(0.08), in: RoundedRectangle(cornerRadius: 16))
                    .accessibilityLabel(mode == .translate ? "Phrase to translate" : "French language question")

                Button {
                    Task { await submit() }
                } label: {
                    HStack {
                        if isLoading { SwiftUI.ProgressView() }
                        Text(mode == .translate ? "Translate into French" : "Ask")
                    }
                    .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .controlSize(.large)
                .disabled(isLoading || input.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)

                if !output.isEmpty {
                    VStack(alignment: .leading, spacing: 10) {
                        Text(mode == .translate ? "FRENCH" : "ANSWER")
                            .font(.caption.weight(.black))
                            .foregroundStyle(store.appTheme.accent)
                        Text(output)
                            .font(.title3.weight(.bold))
                            .textSelection(.enabled)
                        if !pronunciation.isEmpty {
                            Text(pronunciation).foregroundStyle(.secondary)
                        }
                        if mode == .translate {
                            Button { store.speak(output) } label: {
                                Label("Listen in French", systemImage: "speaker.wave.2.fill")
                            }
                        }
                    }
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .padding(18)
                    .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
                }
            }
            .padding(20)
        }
        .background(store.appTheme.appBackground.ignoresSafeArea())
        .navigationTitle("Translate")
        .navigationBarTitleDisplayMode(.inline)
    }

    private func submit() async {
        let text = input.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !text.isEmpty else { return }
        isLoading = true
        defer { isLoading = false }

        var request = URLRequest(url: endpoint)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try? JSONSerialization.data(withJSONObject: [
            "input": text,
            "from": mode == .ask ? "french" : source.rawValue,
            "to": "french",
            "mode": mode == .ask ? "question" : "translate"
        ])
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            let json = try JSONSerialization.jsonObject(with: data) as? [String: Any]
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
                output = json?["message"] as? String ?? "Translation is unavailable. Try again later."
                pronunciation = ""
                return
            }
            let model = json?["response"] as? [String: Any]
            output = (json?["translation"] as? String)
                ?? (model?["translation"] as? String)
                ?? (model?["message"] as? String)
                ?? (json?["message"] as? String)
                ?? "No French translation was returned."
            pronunciation = (json?["pronunciation"] as? String)
                ?? (model?["pronunciation"] as? String)
                ?? ""
        } catch {
            output = offlineTranslation(for: text) ?? "Translation is unavailable offline. Try a word from the Dictionary."
            pronunciation = ""
        }
    }

    private func offlineTranslation(for text: String) -> String? {
        let key = text.trimmingCharacters(in: .whitespacesAndNewlines)
        return store.dictionary.first { word in
            [word.english, word.french, word.spanish, word.kreyol, word.patois]
                .contains { $0.compare(key, options: [.caseInsensitive, .diacriticInsensitive]) == .orderedSame }
        }?.french
    }
}
