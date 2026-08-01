import SwiftUI

struct CardView: View {
    @EnvironmentObject private var store: WalletStore

    var body: some View {
        BeyondIDGate(featureName: "Beyond Card", lockedMessage: "Cardholder setup and card status require a verified Beyond ID session.") {
            WalletScreen(title: "Beyond Card") {
                VStack(alignment: .leading, spacing: 18) {
                    HStack {
                        Text("BEYOND")
                            .font(.headline.weight(.black))
                        Spacer()
                        Text(store.cardProgram.environment == .production ? "PREPAID" : "SANDBOX")
                            .font(.caption.weight(.black))
                    }

                    Spacer(minLength: 24)

                    Text(".... .... .... \(store.cardProgram.lastFour ?? "----")")
                        .font(.system(.title2, design: .monospaced).weight(.bold))

                    HStack {
                        Text(store.cardProgram.environment.displayName.uppercased())
                        Spacer()
                        Text(store.cardProgram.lastFour == nil ? "NOT REAL MONEY" : "ACTIVE")
                    }
                    .font(.caption.weight(.bold))
                }
                .padding(24)
                .frame(maxWidth: .infinity, minHeight: 220, alignment: .leading)
                .foregroundStyle(.white)
                .background(
                    .linearGradient(colors: [Color.walletBlue.opacity(0.92), Color.walletPanelSoft, Color.walletMint.opacity(0.72)], startPoint: .topLeading, endPoint: .bottomTrailing),
                    in: RoundedRectangle(cornerRadius: 8)
                )

                WalletPanel {
                    Eyebrow(text: "Card program")
                    StatusRow(title: "Processor", value: "Marqeta")
                    StatusRow(title: "Cardholder", value: store.cardProgram.cardholderStatus ?? "Not connected")
                    StatusRow(title: "Production issuer", value: store.cardProgram.issuerNote)
                }

                WalletPanel {
                    Label("Production controls disabled", systemImage: "exclamationmark.lock.fill")
                        .font(.headline.weight(.bold))
                        .foregroundStyle(Color.walletGold)
                    Text("KYC, funding, safeguarding, reconciliation, disputes, and cardholder agreements must be complete before real card activity is enabled.")
                        .foregroundStyle(.secondary)
                }
            }
        }
    }
}

struct StatusRow: View {
    let title: String
    let value: String

    var body: some View {
        HStack(alignment: .firstTextBaseline) {
            Text(title)
                .foregroundStyle(.secondary)
            Spacer()
            Text(value)
                .fontWeight(.bold)
                .foregroundStyle(.white)
                .multilineTextAlignment(.trailing)
        }
        .font(.subheadline)
        .padding(.vertical, 3)
    }
}
