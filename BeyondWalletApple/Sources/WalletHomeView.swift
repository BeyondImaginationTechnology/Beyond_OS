import SwiftUI

struct WalletHomeView: View {
    @EnvironmentObject private var store: WalletStore

    var body: some View {
        WalletScreen(title: "Wallet") {
            VStack(alignment: .leading, spacing: 16) {
                Eyebrow(text: "Beyond Bits")
                Text("\(store.rewards.balanceBits.wholeNumberText) bit$")
                    .font(.system(size: 56, weight: .black, design: .rounded))
                    .foregroundStyle(.white)
                    .minimumScaleFactor(0.65)
                Text("Closed-loop rewards travel with your Beyond ID across connected apps.")
                    .foregroundStyle(.secondary)
                HStack(spacing: 10) {
                    MetricTile(title: "Earned", value: "\(store.rewards.lifetimeEarnedBits.wholeNumberText) bit$")
                    MetricTile(title: "Spent", value: "\(store.rewards.lifetimeSpentBits.wholeNumberText) bit$")
                }
            }
            .padding(22)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(
                .linearGradient(colors: [Color.walletBlue, Color.walletMint.opacity(0.72)], startPoint: .topLeading, endPoint: .bottomTrailing),
                in: RoundedRectangle(cornerRadius: 8)
            )

            WalletPanel {
                HStack {
                    VStack(alignment: .leading, spacing: 4) {
                        Eyebrow(text: "Value comparison")
                        Text("100 bit$ = US$1.00")
                            .font(.title3.weight(.bold))
                            .foregroundStyle(.white)
                    }
                    Spacer()
                    Image(systemName: "arrow.left.arrow.right")
                        .font(.title2)
                        .foregroundStyle(Color.walletGold)
                }

                HStack(spacing: 10) {
                    MetricTile(title: "USD estimate", value: store.usdEstimate.moneyText(currency: "USD"))
                    MetricTile(title: "CAD estimate", value: store.cadEstimate.moneyText(currency: "CAD"))
                }

                Text("bit$ are ecosystem rewards, not cryptocurrency, legal tender, or a guaranteed cash-out balance.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            WalletPanel {
                Eyebrow(text: "Free market tools")
                Text("Watchlist, movers, crypto prices, and wallet balance viewing")
                    .font(.title3.weight(.bold))
                    .foregroundStyle(.white)
                HStack(spacing: 10) {
                    MetricTile(title: "Watchlist", value: "\(store.watchlistAssets.count) assets")
                    MetricTile(title: "Top mover", value: store.topMover?.symbol ?? "--")
                }
                MetricTile(title: "Weekly ideas", value: "$5-$10")
                Text("Sign in only when you need account-linked controls like cash, card setup, managing watched addresses, or full ledger history.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            WalletPanel {
                HStack {
                    Eyebrow(text: "System status")
                    Spacer()
                    if store.isRefreshing {
                        ProgressView()
                    }
                }
                Label(store.statusMessage, systemImage: "checkmark.seal.fill")
                    .foregroundStyle(Color.walletMint)
            }

            WalletPanel {
                Eyebrow(text: "Premium access")
                Text(store.hasPremiumAccess ? "Beyond ID connected" : "Beyond ID unlocks premium wallet tools")
                    .font(.title3.weight(.bold))
                    .foregroundStyle(.white)
                Text("Wallet balance, markets, and crypto viewing are free. Cash, card controls, managing watched addresses, and full activity require Beyond ID.")
                    .foregroundStyle(.secondary)

                Button {
                    if store.hasPremiumAccess {
                        store.disconnectBeyondIDPreview()
                    } else {
                        store.connectBeyondIDPreview()
                    }
                } label: {
                    Label(store.hasPremiumAccess ? "Disconnect preview" : "Connect Beyond ID", systemImage: store.hasPremiumAccess ? "lock.open.fill" : "key.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(store.hasPremiumAccess ? Color.walletGold : Color.walletMint)
            }
        }
    }
}

struct MetricTile: View {
    let title: String
    let value: String

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title.uppercased())
                .font(.caption2.weight(.black))
                .foregroundStyle(.secondary)
            Text(value)
                .font(.headline.weight(.black))
                .foregroundStyle(.white)
                .lineLimit(1)
                .minimumScaleFactor(0.72)
        }
        .padding(14)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.walletPanelSoft, in: RoundedRectangle(cornerRadius: 8))
    }
}
