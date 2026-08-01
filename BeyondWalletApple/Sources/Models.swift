import Foundation

struct RewardWallet: Hashable {
    let balanceBits: Decimal
    let currencyCode: String
    let lifetimeEarnedBits: Decimal
    let lifetimeSpentBits: Decimal

    static let seed = RewardWallet(
        balanceBits: 1250,
        currencyCode: "BITS",
        lifetimeEarnedBits: 1850,
        lifetimeSpentBits: 600
    )
}

struct CashAccount: Identifiable, Hashable {
    let currency: String
    let availableBalance: Decimal
    let pendingBalance: Decimal
    let status: CashAccountStatus
    let provider: String?

    var id: String { currency }

    static let seed: [CashAccount] = [
        CashAccount(currency: "CAD", availableBalance: 0, pendingBalance: 0, status: .pendingProvider, provider: nil),
        CashAccount(currency: "USD", availableBalance: 0, pendingBalance: 0, status: .pendingProvider, provider: nil)
    ]
}

enum CashAccountStatus: String, Hashable {
    case active
    case pendingProvider = "pending_provider"

    var displayName: String {
        switch self {
        case .active: "Available"
        case .pendingProvider: "Provider pending"
        }
    }
}

struct WalletTransaction: Identifiable, Hashable {
    let id: String
    let appName: String
    let description: String
    let amountBits: Decimal
    let kind: TransactionKind
    let createdAt: Date

    static let seed: [WalletTransaction] = [
        WalletTransaction(id: "profile-complete", appName: "Beyond ID", description: "Completed profile reward", amountBits: 100, kind: .credit, createdAt: .now.addingTimeInterval(-86000)),
        WalletTransaction(id: "french-module", appName: "Beyond French", description: "Completed daily lesson", amountBits: 10, kind: .credit, createdAt: .now.addingTimeInterval(-42000)),
        WalletTransaction(id: "tattoo-stencil", appName: "Beyond Tattoo", description: "Unlocked stencil package", amountBits: 25, kind: .debit, createdAt: .now.addingTimeInterval(-12000))
    ]
}

enum TransactionKind: String, Hashable {
    case credit
    case debit
}

struct CryptoWatchAccount: Identifiable, Hashable {
    let id: String
    let network: CryptoNetwork
    let label: String
    let publicAddress: String
    let displayBalance: String

    var shortenedAddress: String {
        guard publicAddress.count > 16 else { return publicAddress }
        return "\(publicAddress.prefix(8))...\(publicAddress.suffix(6))"
    }

    static let seed: [CryptoWatchAccount] = [
        CryptoWatchAccount(id: "eth-demo", network: .ethereum, label: "Main wallet", publicAddress: "0x742d35Cc6634C0532925a3b844Bc454e4438f44e", displayBalance: "Watch-only"),
        CryptoWatchAccount(id: "sol-demo", network: .solana, label: "Solana vault", publicAddress: "7wDNw9vWHgoQBeF8awXqBQgT51FS7c4NT4eX7QkN8HHz", displayBalance: "Watch-only")
    ]
}

enum CryptoNetwork: String, CaseIterable, Identifiable, Hashable {
    case bitcoin = "BTC"
    case ethereum = "ETH"
    case solana = "SOL"

    var id: String { rawValue }

    var name: String {
        switch self {
        case .bitcoin: "Bitcoin"
        case .ethereum: "Ethereum"
        case .solana: "Solana"
        }
    }
}

struct MarketAsset: Identifiable, Hashable {
    let symbol: String
    let name: String
    let kind: MarketAssetKind
    let price: Decimal
    let changePercent: Decimal
    let isWatchlisted: Bool
    let note: String

    var id: String { symbol }

    static let seed: [MarketAsset] = [
        MarketAsset(symbol: "BTC", name: "Bitcoin", kind: .crypto, price: 68420.18, changePercent: 1.82, isWatchlisted: true, note: "Large-cap crypto"),
        MarketAsset(symbol: "ETH", name: "Ethereum", kind: .crypto, price: 3585.40, changePercent: -0.44, isWatchlisted: true, note: "Smart contract network"),
        MarketAsset(symbol: "SOL", name: "Solana", kind: .crypto, price: 172.91, changePercent: 2.16, isWatchlisted: false, note: "High-throughput chain"),
        MarketAsset(symbol: "AAPL", name: "Apple", kind: .stock, price: 224.75, changePercent: 0.62, isWatchlisted: true, note: "Consumer technology"),
        MarketAsset(symbol: "NVDA", name: "NVIDIA", kind: .stock, price: 119.34, changePercent: -1.14, isWatchlisted: true, note: "AI infrastructure"),
        MarketAsset(symbol: "TSLA", name: "Tesla", kind: .stock, price: 238.09, changePercent: 1.03, isWatchlisted: false, note: "EV and energy")
    ]
}

enum MarketAssetKind: String, CaseIterable, Identifiable, Hashable {
    case crypto = "Crypto"
    case stock = "Stocks"

    var id: String { rawValue }
}

struct MicroInvestmentIdea: Identifiable, Hashable {
    let id: String
    let assetSymbol: String
    let title: String
    let weeklyAmount: Decimal
    let riskLevel: RiskLevel
    let reason: String

    static let seed: [MicroInvestmentIdea] = [
        MicroInvestmentIdea(
            id: "weekly-btc-5",
            assetSymbol: "BTC",
            title: "$5 Bitcoin starter",
            weeklyAmount: 5,
            riskLevel: .high,
            reason: "Tiny recurring exposure to the largest crypto asset without pretending volatility is small."
        ),
        MicroInvestmentIdea(
            id: "weekly-aapl-10",
            assetSymbol: "AAPL",
            title: "$10 fractional stock idea",
            weeklyAmount: 10,
            riskLevel: .medium,
            reason: "A familiar large-cap stock can be easier for new investors to follow and learn from."
        ),
        MicroInvestmentIdea(
            id: "weekly-eth-5",
            assetSymbol: "ETH",
            title: "$5 smart-contract watch",
            weeklyAmount: 5,
            riskLevel: .high,
            reason: "A small amount keeps the learning cost low while tracking crypto network cycles."
        )
    ]
}

enum RiskLevel: String, Hashable {
    case low = "Low"
    case medium = "Medium"
    case high = "High"

    var label: String {
        "\(rawValue) risk"
    }
}

struct CardProgram: Hashable {
    let environment: CardEnvironment
    let cardholderStatus: String?
    let lastFour: String?
    let issuerNote: String

    static let seed = CardProgram(
        environment: .sandbox,
        cardholderStatus: "Pending activation",
        lastFour: nil,
        issuerNote: "Peoples Trust planned, subject to approval"
    )
}

enum CardEnvironment: String, Hashable {
    case off
    case sandbox
    case production

    var displayName: String {
        switch self {
        case .off: "Setup required"
        case .sandbox: "Marqeta sandbox"
        case .production: "Production"
        }
    }
}
