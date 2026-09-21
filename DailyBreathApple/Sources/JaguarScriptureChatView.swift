import CryptoKit
import SwiftUI

struct JaguarScriptureChatView: View {
    @EnvironmentObject private var auth: BeyondIDAuthManager
    @AppStorage("dailyBreathLanguage") private var languageID = DailyBreathLanguage.english.rawValue
    let guide: ScriptureGuide
    @State private var prompt = ""
    @State private var messages: [(role: String, text: String)] = []
    @State private var isSending = false
    @State private var error: String?

    enum ScriptureGuide: String, CaseIterable, Identifiable {
        case chris, dovi, moe
        var id: String { rawValue }
        var name: String { ["chris": "Chris", "dovi": "Dovi", "moe": "Moe"][rawValue]! }
        var tradition: String { ["chris": "Bible", "dovi": "Tanakh", "moe": "Quran"][rawValue]! }
        var icon: String { ["chris": "book.closed.fill", "dovi": "text.book.closed.fill", "moe": "moon.stars.fill"][rawValue]! }
        var assetName: String { ["chris": "ChrisGuide", "dovi": "DoviGuide", "moe": "MoeGuide"][rawValue]! }

        init(tradition: FaithTradition) {
            switch tradition {
            case .bible: self = .chris
            case .torah: self = .dovi
            case .quran: self = .moe
            }

        }
    }

    var body: some View {
        VStack(spacing: 0) {
            HStack(spacing: 14) {
                Image(guide.assetName)
                    .resizable()
                    .scaledToFill()
                    .frame(width: 72, height: 82)
                    .clipShape(RoundedRectangle(cornerRadius: 16))
                    .accessibilityLabel("\(guide.name), Daily Breath guide")
                VStack(alignment: .leading, spacing: 4) {
                    Text(guide.name).font(.title2.bold())
                    Text("Daily Breath \(guide.tradition) guide · no GPU")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Text("App help and sacred-text questions only")
                        .font(.caption2.weight(.semibold))
                        .foregroundStyle(.secondary)
                }
                Spacer()
            }
            .padding()

            Divider()

            if messages.isEmpty {
                ContentUnavailableView("Ask \(guide.name)", systemImage: guide.icon, description: Text("Ask about Daily Breath or the \(guide.tradition). Answers stay inside this app and use Daily Breath’s grounded guide service."))
            } else {
                ScrollView {
                    LazyVStack(alignment: .leading, spacing: 14) {
                        ForEach(Array(messages.enumerated()), id: \.offset) { _, item in
                            HStack(alignment: .top) {
                                Text(item.role == "user" ? "You" : guide.name)
                                    .font(.caption.bold())
                                    .frame(width: 52, alignment: .leading)
                                Text(item.text).frame(maxWidth: .infinity, alignment: .leading)
                            }
                            .padding(12)
                            .background(item.role == "user" ? Color.secondary.opacity(0.12) : Color.accentColor.opacity(0.12), in: RoundedRectangle(cornerRadius: 14))
                        }
                    }.padding()
                }
            }
            if let error { Text(error).font(.caption).foregroundStyle(.red).padding(.horizontal) }
            HStack(alignment: .bottom, spacing: 8) {
                    TextField("Ask \(guide.name)…", text: $prompt, axis: .vertical)
                        .textFieldStyle(.roundedBorder)
                        .lineLimit(1...5)
                        .submitLabel(.send)
                        .onSubmit { Task { await send() } }
                    Button { Task { await send() } } label: {
                        if isSending { ProgressView() } else { Image(systemName: "arrow.up.circle.fill").font(.title2) }
                    }
                    .disabled(isSending || prompt.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                    .accessibilityLabel("Send message")
                }
            .padding()
        }
        .navigationTitle("\(guide.name) · \(guide.tradition)")
    }

    private func send() async {
        let text = prompt.trimmingCharacters(in: .whitespacesAndNewlines); guard !text.isEmpty else { return }
        if messages.count >= 23 { messages.removeFirst(messages.count - 22) }
        prompt = ""; messages.append(("user", text)); isSending = true; error = nil
        defer { isSending = false }
        var request = URLRequest(url: URL(string: "https://beyondimagination.co.technology/dailybreath/api/jaguar-chat.php")!)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        if let token = auth.accessToken {
            request.setValue("Bearer \\(token)", forHTTPHeaderField: "Authorization")
        }
        let language = DailyBreathLanguage(rawValue: languageID)?.rawValue ?? "en"
        var body: [String: Any] = ["mode": "core", "language": language, "guide": guide.rawValue, "messages": messages.map { ["role": $0.role, "content": $0.text] }]
        if auth.accessToken == nil {
            do {
                body["proof"] = try await guestProof()
            } catch {
                self.error = "Daily Breath chat could not connect. Please try again."
                return
            }
        }
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            let json = try JSONSerialization.jsonObject(with: data) as? [String: Any]
            guard let http = response as? HTTPURLResponse else {
                throw URLError(.badServerResponse)
            }
            if http.statusCode == 401, auth.isSignedIn {
                auth.handleAuthenticationExpired()
                error = json?["error"] as? String ?? "Your Beyond-ID session expired. Sign in again to continue."
                return
            }
            guard (200..<300).contains(http.statusCode) else {
                error = json?["error"] as? String ?? "Daily Breath chat could not respond."
                return
            }
            guard let answer = json?["message"] as? String else { throw URLError(.cannotParseResponse) }
            messages.append(("assistant", answer))
        } catch { self.error = "Daily Breath chat could not respond. Please try again." }
    }

    private func guestProof() async throws -> [String: String] {
        let challengeURL = URL(string: "https://beyondimagination.co.technology/ai/api/challenge.php")!
        let (data, response) = try await URLSession.shared.data(from: challengeURL)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode),
              let payload = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let challenge = payload["challenge"] as? String,
              let difficulty = payload["difficulty"] as? Int else {
            throw URLError(.badServerResponse)
        }
        let requiredZeros = Int(ceil(Double(difficulty) / 4.0))
        for counter in 0..<1_000_000_000 {
            let digest = SHA256.hash(data: Data("\(challenge):\(counter)".utf8))
            let bytes = Array(digest)
            var valid = true
            for index in 0..<requiredZeros {
                let nibble = index.isMultiple(of: 2) ? bytes[index / 2] >> 4 : bytes[index / 2] & 0x0f
                if nibble != 0 {
                    valid = false
                    break
                }
            }
            if valid { return ["challenge": challenge, "counter": String(counter)] }
            if counter.isMultiple(of: 500) { await Task.yield() }
        }
        throw URLError(.cannotDecodeContentData)
    }
}
