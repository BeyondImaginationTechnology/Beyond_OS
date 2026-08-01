import SwiftUI

struct CashView: View {
    @EnvironmentObject private var store: WalletStore

    var body: some View {
        BeyondIDGate(featureName: "Beyond Cash", lockedMessage: "Provider-backed CAD and USD balances require a verified Beyond ID session.") {
            WalletScreen(title: "Beyond Cash") {
                WalletPanel {
                    Eyebrow(text: "Provider-backed money")
                    Text("Cash accounts stay separate from bit$ Rewards.")
                        .font(.title2.weight(.bold))
                        .foregroundStyle(.white)
                    Text("Only reconciled provider funds may enter CAD or USD balances after regulated wallet onboarding is active.")
                        .foregroundStyle(.secondary)
                }

                ForEach(Array(store.cashAccounts.enumerated()), id: \.element.id) { _, account in
                    WalletPanel {
                        HStack {
                            VStack(alignment: .leading, spacing: 5) {
                                Text("\(account.currency) balance")
                                    .font(.headline.weight(.bold))
                                    .foregroundStyle(.white)
                                Text(account.status.displayName)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            Spacer()
                            Image(systemName: account.status == .active ? "checkmark.circle.fill" : "clock.fill")
                                .foregroundStyle(account.status == .active ? Color.walletMint : Color.walletGold)
                        }

                        Text(account.availableBalance.moneyText(currency: account.currency))
                            .font(.system(size: 38, weight: .black, design: .rounded))
                            .foregroundStyle(.white)
                            .minimumScaleFactor(0.7)

                        Text("\(account.pendingBalance.moneyText(currency: account.currency)) pending")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                    }
                }

                WalletPanel {
                    Label("Two-ledger protection", systemImage: "lock.shield.fill")
                        .font(.headline.weight(.bold))
                        .foregroundStyle(Color.walletMint)
                    Text("Learning rewards can never increase a provider cash balance.")
                        .foregroundStyle(.secondary)
                }
            }
        }
    }
}
