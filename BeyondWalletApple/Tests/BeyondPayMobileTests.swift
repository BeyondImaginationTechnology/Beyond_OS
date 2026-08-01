import XCTest
@testable import BeyondPayMobile

final class BeyondPayMobileTests: XCTestCase {
    @MainActor
    func testBitsConvertToUsdAndCad() {
        let store = WalletStore()
        store.usdCadRate = 1.35

        XCTAssertEqual(store.usdValue(forBits: 250), 2.5)
        XCTAssertEqual(store.cadValue(forBits: 250), 3.375)
    }

    @MainActor
    func testAddsOnlySupportedWatchOnlyWallets() {
        let store = WalletStore()
        let originalCount = store.cryptoAccounts.count

        store.selectedNetwork = .ethereum
        store.connectBeyondIDPreview()
        store.publicAddress = "not-a-wallet"
        store.addWatchOnlyWallet()
        XCTAssertEqual(store.cryptoAccounts.count, originalCount)

        store.walletLabel = "Vault"
        store.publicAddress = "0x742d35Cc6634C0532925a3b844Bc454e4438f44e"
        store.addWatchOnlyWallet()
        XCTAssertEqual(store.cryptoAccounts.count, originalCount + 1)
        XCTAssertEqual(store.cryptoAccounts.first?.label, "Vault")
    }

    @MainActor
    func testManagingWatchOnlyWalletsRequiresBeyondID() {
        let store = WalletStore()
        let originalCount = store.cryptoAccounts.count

        XCTAssertFalse(store.hasPremiumAccess)
        store.selectedNetwork = .ethereum
        store.publicAddress = "0x742d35Cc6634C0532925a3b844Bc454e4438f44e"
        store.addWatchOnlyWallet()
        XCTAssertEqual(store.cryptoAccounts.count, originalCount)

        store.connectBeyondIDPreview()
        XCTAssertTrue(store.hasPremiumAccess)
        store.addWatchOnlyWallet()
        XCTAssertEqual(store.cryptoAccounts.count, originalCount + 1)
    }

    @MainActor
    func testMarketViewingIsAvailableWithoutBeyondID() {
        let store = WalletStore()

        XCTAssertFalse(store.hasPremiumAccess)
        XCTAssertTrue(store.marketAssets.contains { $0.kind == .stock })
        XCTAssertTrue(store.marketAssets.contains { $0.kind == .crypto })
        XCTAssertFalse(store.watchlistAssets.isEmpty)
        XCTAssertNotNil(store.topMover)
        XCTAssertFalse(store.weeklyMicroIdeas.isEmpty)
    }

    @MainActor
    func testWeeklyMicroIdeasStayInsideSmallBudgetRange() {
        let store = WalletStore()

        XCTAssertTrue(store.weeklyMicroIdeas.allSatisfy { store.weeklyMicroBudgetRange.contains($0.weeklyAmount) })
    }

    func testShortenedAddressKeepsEndsVisible() {
        let account = CryptoWatchAccount.seed[0]
        XCTAssertEqual(account.shortenedAddress, "0x742d35...38f44e")
    }
}
