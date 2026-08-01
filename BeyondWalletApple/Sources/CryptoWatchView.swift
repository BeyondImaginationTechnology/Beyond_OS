import SwiftUI

struct CryptoWatchView: View {
    @EnvironmentObject private var store: WalletStore

    var body: some View {
        WalletScreen(title: "Crypto Watch") {
            WalletPanel {
                Eyebrow(text: "Gate-free viewing")
                Text("Track public wallet addresses without storing keys.")
                    .font(.title2.weight(.bold))
                    .foregroundStyle(.white)
                Text("Viewing crypto and wallet balances is free. Adding or removing watched addresses requires Beyond ID.")
                    .foregroundStyle(.secondary)
            }

            WalletPanel {
                if store.hasPremiumAccess {
                    Picker("Network", selection: $store.selectedNetwork) {
                        ForEach(CryptoNetwork.allCases) { network in
                            Text(network.rawValue).tag(network)
                        }
                    }
                    .pickerStyle(.segmented)

                    TextField("Wallet label", text: $store.walletLabel)
                        .textInputAutocapitalization(.words)
                        .textFieldStyle(.roundedBorder)

                    TextField("Public address", text: $store.publicAddress)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                        .textFieldStyle(.roundedBorder)

                    Button {
                        store.addWatchOnlyWallet()
                    } label: {
                        Label("Add wallet", systemImage: "plus.circle.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(Color.walletMint)
                } else {
                    Label("Beyond ID required to manage addresses", systemImage: "person.badge.key.fill")
                        .font(.headline.weight(.bold))
                        .foregroundStyle(Color.walletGold)
                    Text("Existing public wallet views remain visible without signing in.")
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
            }

            ForEach(Array(store.cryptoAccounts.enumerated()), id: \.element.id) { _, account in
                WalletPanel {
                    HStack(spacing: 13) {
                        Text(account.network.rawValue)
                            .font(.caption.weight(.black))
                            .frame(width: 46, height: 46)
                            .background(Color.walletMint.opacity(0.16), in: Circle())

                        VStack(alignment: .leading, spacing: 4) {
                            Text(account.label)
                                .font(.headline.weight(.bold))
                                .foregroundStyle(.white)
                            Text(account.shortenedAddress)
                                .font(.caption.monospaced())
                                .foregroundStyle(.secondary)
                            Text(account.displayBalance)
                                .font(.caption.weight(.bold))
                                .foregroundStyle(Color.walletMint)
                        }

                        Spacer()

                        if store.hasPremiumAccess {
                            Button {
                                store.removeWatchOnlyWallet(account)
                            } label: {
                                Image(systemName: "trash")
                            }
                            .buttonStyle(.borderless)
                            .foregroundStyle(.secondary)
                        }
                    }
                }
            }
        }
    }
}
