import SwiftUI

struct MarketsView: View {
    @EnvironmentObject private var store: WalletStore
    @State private var selectedKind: MarketAssetKind = .crypto

    var visibleAssets: [MarketAsset] {
        store.marketAssets.filter { $0.kind == selectedKind }
    }

    var body: some View {
        WalletScreen(title: "Markets") {
            WalletPanel {
                Eyebrow(text: "Gate-free viewing")
                Text("Stock market and crypto prices")
                    .font(.title2.weight(.bold))
                    .foregroundStyle(.white)
                Text("Market viewing is available without Beyond ID. Trading, funding, and account actions stay unavailable in this preview.")
                    .foregroundStyle(.secondary)
            }

            HStack(spacing: 10) {
                MetricTile(title: "Watchlist", value: "\(store.watchlistAssets.count)")
                MetricTile(title: "Top mover", value: store.topMover?.symbol ?? "--")
            }

            Picker("Market type", selection: $selectedKind) {
                ForEach(MarketAssetKind.allCases) { kind in
                    Text(kind.rawValue).tag(kind)
                }
            }
            .pickerStyle(.segmented)

            WalletPanel {
                Eyebrow(text: "Weekly micro ideas")
                Text("$5-$10 learning-sized options")
                    .font(.title3.weight(.bold))
                    .foregroundStyle(.white)
                Text("These are educational prompts for tiny recurring amounts, not personalized recommendations or trade instructions.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }

            ForEach(store.weeklyMicroIdeas) { idea in
                WalletPanel {
                    HStack(alignment: .top, spacing: 12) {
                        Text(idea.assetSymbol)
                            .font(.caption.weight(.black))
                            .frame(width: 52, height: 52)
                            .background(riskColor(for: idea.riskLevel).opacity(0.15), in: Circle())

                        VStack(alignment: .leading, spacing: 6) {
                            Text(idea.title)
                                .font(.headline.weight(.bold))
                                .foregroundStyle(.white)
                            Text(idea.reason)
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }

                        Spacer()

                        VStack(alignment: .trailing, spacing: 6) {
                            Text(idea.weeklyAmount.moneyText(currency: "USD"))
                                .font(.headline.weight(.black))
                                .foregroundStyle(.white)
                            Text(idea.riskLevel.label)
                                .font(.caption2.weight(.bold))
                                .foregroundStyle(riskColor(for: idea.riskLevel))
                        }
                    }
                }
            }

            ForEach(Array(visibleAssets.enumerated()), id: \.element.id) { _, asset in
                WalletPanel {
                    HStack(spacing: 12) {
                        Text(asset.symbol)
                            .font(.caption.weight(.black))
                            .frame(width: 52, height: 52)
                            .background(Color.walletMint.opacity(0.14), in: Circle())

                        VStack(alignment: .leading, spacing: 5) {
                            Text(asset.name)
                                .font(.headline.weight(.bold))
                                .foregroundStyle(.white)
                            Text(asset.kind.rawValue)
                                .font(.caption)
                                .foregroundStyle(.secondary)
                            Text(asset.note)
                                .font(.caption2)
                                .foregroundStyle(.secondary)
                        }

                        Spacer()

                        VStack(alignment: .trailing, spacing: 5) {
                            Text(asset.price.moneyText(currency: "USD"))
                                .font(.headline.weight(.black))
                                .foregroundStyle(.white)
                            Text(asset.changeText)
                                .font(.caption.weight(.bold))
                                .foregroundStyle(asset.changePercent >= 0 ? Color.walletMint : Color.walletGold)
                            if asset.isWatchlisted {
                                Label("Watchlist", systemImage: "star.fill")
                                    .font(.caption2.weight(.bold))
                                    .foregroundStyle(Color.walletGold)
                            }
                        }
                    }
                }
            }

            WalletPanel {
                Label("Free snapshot only", systemImage: "info.circle.fill")
                    .font(.headline.weight(.bold))
                    .foregroundStyle(Color.walletMint)
                Text("Useful free features: prices, movers, watchlist, conversion math, public wallet balances, and safety notes. Premium begins where identity, money movement, or account history begins.")
                    .foregroundStyle(.secondary)
            }
        }
    }
}

private func riskColor(for riskLevel: RiskLevel) -> Color {
    switch riskLevel {
    case .low:
        Color.walletMint
    case .medium:
        Color.walletBlue
    case .high:
        Color.walletGold
    }
}

private extension MarketAsset {
    var changeText: String {
        let sign = changePercent >= 0 ? "+" : ""
        return "\(sign)\(changePercent)%"
    }
}
