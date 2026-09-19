import Foundation

enum JaguarLanguage: String, Codable, CaseIterable, Identifiable, Sendable {
    case english = "en"
    case french = "fr"
    case spanish = "es"

    var id: String { rawValue }
    var title: String {
        switch self {
        case .english: "English"
        case .french: "Français"
        case .spanish: "Español"
        }
    }
}

enum JaguarRole: String, Codable, Sendable {
    case user
    case assistant
}

struct JaguarMessage: Codable, Identifiable, Equatable, Sendable {
    let id: UUID
    let role: JaguarRole
    let content: String
    let createdAt: Date

    init(id: UUID = UUID(), role: JaguarRole, content: String, createdAt: Date = Date()) {
        self.id = id
        self.role = role
        self.content = content
        self.createdAt = createdAt
    }
}

struct JaguarConversation: Codable, Identifiable, Equatable, Sendable {
    let id: UUID
    var title: String
    var language: JaguarLanguage
    var messages: [JaguarMessage]
    var updatedAt: Date

    init(id: UUID = UUID(), title: String = "New conversation", language: JaguarLanguage = .english, messages: [JaguarMessage] = [], updatedAt: Date = Date()) {
        self.id = id
        self.title = title
        self.language = language
        self.messages = messages
        self.updatedAt = updatedAt
    }
}

struct JaguarWireMessage: Codable, Equatable, Sendable {
    let role: String
    let content: String
}

struct JaguarChatRequest: Codable, Equatable, Sendable {
    let mode: String
    let language: String
    let messages: [JaguarWireMessage]
}

struct JaguarChatResponse: Codable, Equatable, Sendable {
    let model: String
    let adapter: String
    let mode: String
    let message: String
}

struct JaguarAPIErrorResponse: Codable, Sendable {
    let error: String
}
