import Combine
import Foundation
import SwiftUI

@MainActor
final class JaguarChatStore: ObservableObject {
    @Published private(set) var conversations: [JaguarConversation] = []
    @Published var selectedID: UUID?
    @Published var draft = ""
    @Published private(set) var isThinking = false
    @Published private(set) var thinkingStage = ""
    @Published private(set) var elapsedSeconds = 0
    @Published var errorMessage: String?

    private let client: JaguarAPIClient
    private let fileURL: URL
    private var timerTask: Task<Void, Never>?

    var selectedConversation: JaguarConversation? {
        guard let selectedID else { return nil }
        return conversations.first(where: { $0.id == selectedID })
    }

    init(client: JaguarAPIClient = .live, fileManager: FileManager = .default) {
        self.client = client
        let root = fileManager.urls(for: .applicationSupportDirectory, in: .userDomainMask).first!
        let folder = root.appendingPathComponent("Jaguar", isDirectory: true)
        try? fileManager.createDirectory(at: folder, withIntermediateDirectories: true)
        fileURL = folder.appendingPathComponent("conversations.json")
        load()
        if conversations.isEmpty { newConversation() }
        selectedID = conversations.first?.id
    }

    func newConversation() {
        let conversation = JaguarConversation()
        conversations.insert(conversation, at: 0)
        selectedID = conversation.id
        errorMessage = nil
        save()
    }

    func select(_ conversation: JaguarConversation) {
        selectedID = conversation.id
        errorMessage = nil
    }

    func delete(at offsets: IndexSet) {
        let selectedWasDeleted = offsets.contains { conversations[$0].id == selectedID }
        conversations.remove(atOffsets: offsets)
        if conversations.isEmpty { newConversation() }
        else if selectedWasDeleted { selectedID = conversations.first?.id }
        save()
    }

    func setLanguage(_ language: JaguarLanguage) {
        updateSelected { $0.language = language }
    }

    func send(accessToken: String) async -> Bool {
        let text = draft.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !text.isEmpty, !isThinking, let selectedID else { return false }
        draft = ""
        errorMessage = nil
        let outgoing = JaguarMessage(role: .user, content: String(text.prefix(8000)))
        update(id: selectedID) { conversation in
            conversation.messages.append(outgoing)
            if conversation.messages.count == 1 {
                conversation.title = String(text.prefix(46))
            }
        }
        guard let conversation = conversations.first(where: { $0.id == selectedID }) else { return false }
        startThinking()
        do {
            let response = try await client.send(messages: conversation.messages, language: conversation.language, accessToken: accessToken)
            update(id: selectedID) { $0.messages.append(JaguarMessage(role: .assistant, content: response.message)) }
            stopThinking()
            return true
        } catch {
            errorMessage = (error as? LocalizedError)?.errorDescription ?? "Beyond-1 could not complete that request."
            stopThinking()
            return false
        }
    }

    func sendDemo() async -> Bool {
        let text = draft.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !text.isEmpty, !isThinking, let selectedID else { return false }
        draft = ""
        errorMessage = nil
        let outgoing = JaguarMessage(role: .user, content: String(text.prefix(8000)))
        update(id: selectedID) { conversation in
            conversation.messages.append(outgoing)
            if conversation.messages.count == 1 { conversation.title = String(text.prefix(46)) }
        }
        guard let conversation = conversations.first(where: { $0.id == selectedID }) else { return false }
        startThinking()
        let response = await client.sendDemo(messages: conversation.messages, language: conversation.language)
        update(id: selectedID) { $0.messages.append(JaguarMessage(role: .assistant, content: response.message)) }
        stopThinking()
        return true
    }

    private func startThinking() {
        isThinking = true
        elapsedSeconds = 0
        thinkingStage = "Understanding"
        timerTask?.cancel()
        timerTask = Task { [weak self] in
            while !Task.isCancelled {
                try? await Task.sleep(for: .seconds(1))
                guard !Task.isCancelled, let self else { return }
                self.elapsedSeconds += 1
                self.thinkingStage = self.elapsedSeconds < 4 ? "Understanding" : self.elapsedSeconds < 9 ? "Preparing" : "Checking result"
            }
        }
    }

    private func stopThinking() {
        isThinking = false
        timerTask?.cancel()
        timerTask = nil
        thinkingStage = ""
        elapsedSeconds = 0
    }

    private func updateSelected(_ transform: (inout JaguarConversation) -> Void) {
        guard let selectedID else { return }
        update(id: selectedID, transform)
    }

    private func update(id: UUID, _ transform: (inout JaguarConversation) -> Void) {
        guard let index = conversations.firstIndex(where: { $0.id == id }) else { return }
        transform(&conversations[index])
        conversations[index].updatedAt = Date()
        save()
    }

    private func load() {
        guard let data = try? Data(contentsOf: fileURL),
              let stored = try? JSONDecoder().decode([JaguarConversation].self, from: data) else { return }
        conversations = stored.sorted { $0.updatedAt > $1.updatedAt }
    }

    private func save() {
        guard let data = try? JSONEncoder().encode(conversations) else { return }
        try? data.write(to: fileURL, options: .atomic)
    }
}
