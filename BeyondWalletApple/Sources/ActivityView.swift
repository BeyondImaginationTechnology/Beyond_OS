import SwiftUI

struct ActivityView: View {
    @EnvironmentObject private var store: WalletStore

    var body: some View {
        BeyondIDGate(featureName: "Activity", lockedMessage: "Full wallet activity requires Beyond ID so transactions can be tied to the right account.") {
            WalletScreen(title: "Activity") {
                WalletPanel {
                    Eyebrow(text: "Wallet activity")
                    Text("\(store.transactions.count) entries")
                        .font(.title2.weight(.bold))
                        .foregroundStyle(.white)
                }

                ForEach(Array(store.transactions.enumerated()), id: \.element.id) { _, transaction in
                    WalletPanel {
                        HStack(alignment: .top, spacing: 12) {
                            Image(systemName: transaction.kind == .credit ? "arrow.down.circle.fill" : "arrow.up.circle.fill")
                                .font(.title2)
                                .foregroundStyle(transaction.kind == .credit ? Color.walletMint : Color.walletGold)

                            VStack(alignment: .leading, spacing: 5) {
                                Text(transaction.description)
                                    .font(.headline.weight(.bold))
                                    .foregroundStyle(.white)
                                Text(transaction.appName)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }

                            Spacer()

                            Text("\(transaction.kind == .credit ? "+" : "-")\(transaction.amountBits.wholeNumberText) bit$")
                                .font(.headline.weight(.black))
                                .foregroundStyle(transaction.kind == .credit ? Color.walletMint : Color.walletGold)
                        }
                    }
                }
            }
        }
    }
}
