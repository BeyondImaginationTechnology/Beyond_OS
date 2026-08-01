import Foundation

@MainActor
final class WalletStore: ObservableObject {
    @Published private(set) var rewards = RewardWallet.seed
    @Published private(set) var cashAccounts = CashAccount.seed
    @Published private(set) var transactions = WalletTransaction.seed
    @Published private(set) var cryptoAccounts = CryptoWatchAccount.seed
    @Published private(set) var marketAssets = MarketAsset.seed
    @Published private(set) var weeklyMicroIdeas = MicroInvestmentIdea.seed
    @Published private(set) var cardProgram = CardProgram.seed
    @Published private(set) var isRefreshing = false
    @Published private(set) var statusMessage = "Offline wallet preview"
    @Published private(set) var hasBeyondID = false
    @Published var usdCadRate: Decimal = 1.37
    @Published var selectedNetwork: CryptoNetwork = .ethereum
    @Published var walletLabel = ""
    @Published var publicAddress = ""

    let bitsPerUSD: Decimal = 100

    var usdEstimate: Decimal {
        rewards.balanceBits / bitsPerUSD
    }

    var cadEstimate: Decimal {
        usdEstimate * usdCadRate
    }

    var watchlistAssets: [MarketAsset] {
        marketAssets.filter(\.isWatchlisted)
    }

    var topMover: MarketAsset? {
        marketAssets.max {
            abs(($0.changePercent as NSDecimalNumber).doubleValue) < abs(($1.changePercent as NSDecimalNumber).doubleValue)
        }
    }

    var weeklyMicroBudgetRange: ClosedRange<Decimal> {
        5...10
    }

    var hasPremiumAccess: Bool {
        hasBeyondID
    }

    func load() async {
        await refresh()
    }

    func refresh() async {
        isRefreshing = true
        defer { isRefreshing = false }
        statusMessage = "Synced from local wallet seed"
    }

    func usdValue(forBits bits: Decimal) -> Decimal {
        bits / bitsPerUSD
    }

    func cadValue(forBits bits: Decimal) -> Decimal {
        usdValue(forBits: bits) * usdCadRate
    }

    func connectBeyondIDPreview() {
        hasBeyondID = true
        statusMessage = "Beyond ID connected"
    }

    func disconnectBeyondIDPreview() {
        hasBeyondID = false
        statusMessage = "Premium features locked"
    }

    func addWatchOnlyWallet() {
        guard hasPremiumAccess else { return }
        let trimmedAddress = publicAddress.trimmingCharacters(in: .whitespacesAndNewlines)
        guard isSupportedPublicAddress(trimmedAddress, network: selectedNetwork) else { return }
        let label = walletLabel.trimmingCharacters(in: .whitespacesAndNewlines)
        cryptoAccounts.insert(
            CryptoWatchAccount(
                id: UUID().uuidString,
                network: selectedNetwork,
                label: label.isEmpty ? "\(selectedNetwork.name) wallet" : label,
                publicAddress: trimmedAddress,
                displayBalance: "Watch-only"
            ),
            at: 0
        )
        walletLabel = ""
        publicAddress = ""
    }

    func removeWatchOnlyWallet(_ account: CryptoWatchAccount) {
        guard hasPremiumAccess else { return }
        cryptoAccounts.removeAll { $0.id == account.id }
    }

    func isSupportedPublicAddress(_ address: String, network: CryptoNetwork) -> Bool {
        switch network {
        case .bitcoin:
            return address.count >= 26 && address.count <= 90 && (address.hasPrefix("1") || address.hasPrefix("3") || address.lowercased().hasPrefix("bc1"))
        case .ethereum:
            return address.count == 42 && address.hasPrefix("0x")
        case .solana:
            return address.count >= 32 && address.count <= 44
        }
    }
}

extension Decimal {
    var wholeNumberText: String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .decimal
        formatter.maximumFractionDigits = 0
        return formatter.string(from: self as NSDecimalNumber) ?? "0"
    }

    func moneyText(currency: String) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = currency
        formatter.minimumFractionDigits = 2
        formatter.maximumFractionDigits = 2
        return formatter.string(from: self as NSDecimalNumber) ?? "\(currency) 0.00"
    }
}
