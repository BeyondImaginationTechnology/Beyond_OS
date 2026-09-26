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

/// Public Jaguar chat modes use the PHP catalog keys. The server maps `core`
/// to the runtime's `explain` mode; `build` is text-only software design help.
enum JaguarMode: String, Codable, CaseIterable, Identifiable, Sendable {
    case explain = "core"
    case build

    var id: String { rawValue }
    var title: String {
        switch self {
        case .explain: "Explain"
        case .build: "Build"
        }
    }

    var icon: String {
        switch self {
        case .explain: "sparkles"
        case .build: "hammer.fill"
        }
    }

    var guidance: String {
        switch self {
        case .explain: "Explain is the live fast lane. Beyond-1 can make mistakes; check important information."
        case .build: "Build helps with software ideas, design, and coding guidance. It cannot inspect or change repositories, and does not generate images or video."
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
    var mode: JaguarMode
    var messages: [JaguarMessage]
    var updatedAt: Date

    init(id: UUID = UUID(), title: String = "New conversation", language: JaguarLanguage = .english, mode: JaguarMode = .explain, messages: [JaguarMessage] = [], updatedAt: Date = Date()) {
        self.id = id
        self.title = title
        self.language = language
        self.mode = mode
        self.messages = messages
        self.updatedAt = updatedAt
    }

    private enum CodingKeys: String, CodingKey {
        case id, title, language, mode, messages, updatedAt
    }

    init(from decoder: Decoder) throws {
        let values = try decoder.container(keyedBy: CodingKeys.self)
        id = try values.decode(UUID.self, forKey: .id)
        title = try values.decode(String.self, forKey: .title)
        language = try values.decode(JaguarLanguage.self, forKey: .language)
        mode = try values.decodeIfPresent(JaguarMode.self, forKey: .mode) ?? .explain
        messages = try values.decode([JaguarMessage].self, forKey: .messages)
        updatedAt = try values.decode(Date.self, forKey: .updatedAt)
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
