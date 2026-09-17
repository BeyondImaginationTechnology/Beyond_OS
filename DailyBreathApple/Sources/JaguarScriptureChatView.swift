import SwiftUI

struct JaguarScriptureChatView: View {
    @StateObject private var auth = BeyondIDAuthManager()
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
    }

    var body: some View {
        VStack(spacing: 0) {
            if messages.isEmpty {
                ContentUnavailableView("Ask \(guide.name)", systemImage: guide.icon, description: Text("Ask a respectful question about the \(guide.tradition). Llama Jaguar will respond in this guide’s voice."))
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
                TextField("Ask \(guide.name)…", text: $prompt, axis: .vertical).textFieldStyle(.roundedBorder).lineLimit(1...5)
                Button { Task { await send() } } label: { Image(systemName: "arrow.up.circle.fill").font(.title2) }.disabled(isSending || prompt.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }.padding()
        }
        .navigationTitle("\(guide.name) · \(guide.tradition)")
        .task { if !auth.isSignedIn { auth.signIn() } }
    }

    private func send() async {
        let text = prompt.trimmingCharacters(in: .whitespacesAndNewlines); guard !text.isEmpty else { return }
        prompt = ""; messages.append(("user", text)); isSending = true; error = nil
        defer { isSending = false }
        guard let token = auth.accessToken else { error = "Sign in with Beyond ID to chat."; return }
        var request = URLRequest(url: URL(string: "https://ai.beyondimagination.co.technology/api/chat.php")!)
        request.httpMethod = "POST"; request.setValue("application/json", forHTTPHeaderField: "Content-Type"); request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization"); request.setValue("dailybreath", forHTTPHeaderField: "X-Beyond-App")
        let body: [String: Any] = ["mode": "core", "language": "en", "guide": guide.rawValue, "messages": messages.map { ["role": $0.role, "content": $0.text] }]
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else { throw URLError(.badServerResponse) }
            let json = try JSONSerialization.jsonObject(with: data) as? [String: Any]; guard let answer = json?["message"] as? String else { throw URLError(.cannotParseResponse) }
            messages.append(("assistant", answer))
        } catch { error = "Jaguar could not respond. Please try again." }
    }
}
