import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { WalletHomeView() }
                .tabItem { Label("Wallet", systemImage: "wallet.pass.fill") }
            NavigationStack { MarketsView() }
                .tabItem { Label("Markets", systemImage: "chart.line.uptrend.xyaxis") }
            NavigationStack { CashView() }
                .tabItem { Label("Cash", systemImage: "banknote.fill") }
            NavigationStack { CardView() }
                .tabItem { Label("Card", systemImage: "creditcard.fill") }
            NavigationStack { CryptoWatchView() }
                .tabItem { Label("Crypto", systemImage: "bitcoinsign.circle.fill") }
            NavigationStack { ActivityView() }
                .tabItem { Label("Activity", systemImage: "list.bullet.rectangle.fill") }
        }
        .tint(Color.walletMint)
    }
}

struct WalletScreen<Content: View>: View {
    let title: String
    @ViewBuilder var content: Content

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                WalletHeader()
                content
            }
            .padding()
        }
        .background(Color.walletBackground.ignoresSafeArea())
        .navigationTitle(title)
    }
}

struct WalletHeader: View {
    var body: some View {
        HStack(spacing: 12) {
            ZStack {
                RoundedRectangle(cornerRadius: 12)
                    .fill(.linearGradient(colors: [Color.walletMint, Color.walletBlue], startPoint: .topLeading, endPoint: .bottomTrailing))
                Image(systemName: "wallet.pass.fill")
                    .font(.title2.weight(.black))
                    .foregroundStyle(.white)
            }
            .frame(width: 54, height: 54)

            VStack(alignment: .leading, spacing: 2) {
                Text("BEYOND WALLET")
                    .font(.headline.weight(.black))
                Text("Rewards, markets, cash, card, and crypto")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Spacer()

            Text("1.0")
                .font(.caption.bold())
                .padding(.horizontal, 10)
                .padding(.vertical, 6)
                .background(Color.walletMint.opacity(0.15), in: Capsule())
        }
    }
}

struct WalletPanel<Content: View>: View {
    @ViewBuilder var content: Content

    var body: some View {
        VStack(alignment: .leading, spacing: 14) {
            content
        }
        .padding(18)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.walletPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}

struct BeyondIDGate<Content: View>: View {
    @EnvironmentObject private var store: WalletStore
    let featureName: String
    let lockedMessage: String
    @ViewBuilder var content: Content

    var body: some View {
        if store.hasPremiumAccess {
            content
        } else {
            WalletScreen(title: featureName) {
                WalletPanel {
                    Image(systemName: "person.badge.key.fill")
                        .font(.system(size: 42, weight: .bold))
                        .foregroundStyle(Color.walletMint)

                    Eyebrow(text: "Beyond ID required")
                    Text(featureName)
                        .font(.largeTitle.weight(.black))
                        .foregroundStyle(.white)
                        .minimumScaleFactor(0.75)
                    Text(lockedMessage)
                        .foregroundStyle(.secondary)

                    Button {
                        store.connectBeyondIDPreview()
                    } label: {
                        Label("Connect Beyond ID", systemImage: "key.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(Color.walletMint)
                }

                WalletPanel {
                    Label("Premium wallet protection", systemImage: "lock.shield.fill")
                        .font(.headline.weight(.bold))
                        .foregroundStyle(Color.walletGold)
                    Text("Cash, card controls, account-linked wallet actions, and full ledger activity stay locked until the user has an active Beyond ID session.")
                        .foregroundStyle(.secondary)
                }
            }
        }
    }
}

struct Eyebrow: View {
    let text: String

    var body: some View {
        Text(text.uppercased())
            .font(.caption.weight(.black))
            .tracking(1.4)
            .foregroundStyle(Color.walletMint)
    }
}

extension Color {
    static let walletBackground = Color(red: 0.05, green: 0.06, blue: 0.07)
    static let walletPanel = Color(red: 0.10, green: 0.12, blue: 0.13)
    static let walletPanelSoft = Color(red: 0.14, green: 0.16, blue: 0.17)
    static let walletMint = Color(red: 0.24, green: 0.86, blue: 0.64)
    static let walletBlue = Color(red: 0.18, green: 0.43, blue: 0.92)
    static let walletGold = Color(red: 0.94, green: 0.74, blue: 0.34)
}
