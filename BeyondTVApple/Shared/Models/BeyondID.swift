import Foundation

struct BeyondIDSession: Decodable, Sendable {
    let ok: Bool
    let authenticated: Bool
    let user: BeyondIDUser?
    let wallet: BeyondIDWallet?
    let error: String?
}

struct BeyondIDUser: Decodable, Identifiable, Equatable, Sendable {
    let id: Int
    let name: String?
    let firstName: String?
    let lastName: String?
    let email: String
    let role: String?
    let displayName: String?
    let avatar: String?

    var preferredName: String {
        [
            displayName,
            name,
            [firstName, lastName].compactMap { $0 }.joined(separator: " "),
            email
        ]
        .compactMap { $0?.trimmingCharacters(in: .whitespacesAndNewlines) }
        .first { !$0.isEmpty } ?? "Beyond ID"
    }

    enum CodingKeys: String, CodingKey {
        case id, name, email, role, avatar
        case firstName = "first_name"
        case lastName = "last_name"
        case displayName = "display_name"
    }

    init(
        id: Int,
        name: String?,
        firstName: String?,
        lastName: String?,
        email: String,
        role: String?,
        displayName: String?,
        avatar: String?
    ) {
        self.id = id
        self.name = name
        self.firstName = firstName
        self.lastName = lastName
        self.email = email
        self.role = role
        self.displayName = displayName
        self.avatar = avatar
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        id = try container.decodeFlexibleInt(forKey: .id)
        name = try container.decodeIfPresent(String.self, forKey: .name)
        firstName = try container.decodeIfPresent(String.self, forKey: .firstName)
        lastName = try container.decodeIfPresent(String.self, forKey: .lastName)
        email = try container.decodeIfPresent(String.self, forKey: .email) ?? ""
        role = try container.decodeIfPresent(String.self, forKey: .role)
        displayName = try container.decodeIfPresent(String.self, forKey: .displayName)
        avatar = try container.decodeIfPresent(String.self, forKey: .avatar)
    }
}

struct BeyondIDWallet: Decodable, Equatable, Sendable {
    let balance: Double
    let currency: String
    let status: String?

    var balanceText: String {
        let formatted = balance == floor(balance) ? String(format: "%.0f", balance) : String(format: "%.2f", balance)
        return "\(formatted) \(currency)"
    }

    enum CodingKeys: String, CodingKey {
        case balance, currency, status
    }

    init(balance: Double, currency: String, status: String?) {
        self.balance = balance
        self.currency = currency
        self.status = status
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        balance = try container.decodeFlexibleDouble(forKey: .balance)
        currency = try container.decodeIfPresent(String.self, forKey: .currency) ?? "BITS"
        status = try container.decodeIfPresent(String.self, forKey: .status)
    }
}

private extension KeyedDecodingContainer {
    func decodeFlexibleInt(forKey key: Key) throws -> Int {
        if let value = try? decode(Int.self, forKey: key) { return value }
        if let value = try? decode(String.self, forKey: key), let intValue = Int(value) { return intValue }
        return 0
    }

    func decodeFlexibleDouble(forKey key: Key) throws -> Double {
        if let value = try? decode(Double.self, forKey: key) { return value }
        if let value = try? decode(Int.self, forKey: key) { return Double(value) }
        if let value = try? decode(String.self, forKey: key), let doubleValue = Double(value) { return doubleValue }
        return 0
    }
}
