import SwiftUI

struct AccountView: View {
    @EnvironmentObject private var model: AppModel

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    header

                    if let user = model.beyondIDUser {
                        signedInCard(user)
                    } else {
                        signedOutCard
                    }

                    supporterCard
                }
                .padding(.horizontal, 20)
                .padding(.vertical, 24)
            }
            .background(BeyondTVBackground().ignoresSafeArea())
            .navigationTitle("Beyond ID")
            .toolbar {
                ToolbarItem(placement: .primaryAction) {
                    ThemeToggleButton()
                }
            }
        }
    }

    private var supporterCard: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("BEYOND SUPPORTER · 1.1.0")
                .font(.caption.bold())
                .tracking(1.5)
                .foregroundStyle(.orange)

            Text("Your first year is on us.")
                .font(.title2.bold())

            Text("Enjoy 365 days of the private iOS experience at no charge and with no card required. Beyond TV on the web stays free forever.")
                .font(.subheadline)
                .foregroundStyle(.secondary)

            HStack(spacing: 8) {
                membershipPrice("Weekly", "$1.49")
                membershipPrice("Monthly", "$4.99")
                membershipPrice("Yearly", "$39.99")
            }

            Label("Android coming soon", systemImage: "smartphone")
                .font(.caption.bold())
                .foregroundStyle(.secondary)

            Link(destination: URL(string: "https://beyondimagination.co.technology/beyond-tv/#membership")!) {
                Label("Membership details", systemImage: "safari.fill")
            }
            .buttonStyle(.bordered)
        }
        .padding(18)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 18))
        .overlay(
            RoundedRectangle(cornerRadius: 18)
                .stroke(Color.orange.opacity(0.35))
        )
    }

    private func membershipPrice(_ period: String, _ price: String) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(period.uppercased())
                .font(.caption2.bold())
                .foregroundStyle(.secondary)
            Text(price)
                .font(.headline.bold())
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(10)
        .background(.white.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
    }

    private var header: some View {
        VStack(alignment: .leading, spacing: 10) {
            Image("BeyondTVLogo")
                .resizable()
                .scaledToFit()
                .frame(width: 72, height: 72)
                .clipShape(RoundedRectangle(cornerRadius: 16))
                .accessibilityHidden(true)

            Text("Beyond ID")
                .font(.system(size: 34, weight: .black, design: .rounded))

            Text("Use one Beyond account for TV, saved lists, wallet balance, and connected apps.")
                .font(.body)
                .foregroundStyle(.secondary)
                .fixedSize(horizontal: false, vertical: true)
        }
    }

    private var signedOutCard: some View {
        VStack(alignment: .leading, spacing: 18) {
            Label("Sign in with Google", systemImage: "person.crop.circle.badge.checkmark")
                .font(.headline)

            Text("Google sign-in creates or connects your Beyond ID, then returns you to Beyond TV automatically.")
                .font(.subheadline)
                .foregroundStyle(.secondary)

            Button {
                Task { await model.signInWithGoogle() }
            } label: {
                HStack {
                    Text("G")
                        .font(.system(size: 18, weight: .black, design: .rounded))
                        .frame(width: 28, height: 28)
                        .background(.white)
                        .foregroundStyle(.black)
                        .clipShape(Circle())
                    Text(model.isSigningIn ? "Opening Google..." : "Continue with Google")
                        .fontWeight(.bold)
                    Spacer()
                    Image(systemName: "arrow.up.forward.app.fill")
                }
            }
            .buttonStyle(.borderedProminent)
            .controlSize(.large)
            .disabled(model.isSigningIn)

            if let message = model.authErrorMessage {
                Text(message)
                    .font(.footnote)
                    .foregroundStyle(.red)
            }
        }
        .padding(18)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 18))
        .overlay(
            RoundedRectangle(cornerRadius: 18)
                .stroke(Color.white.opacity(0.12))
        )
    }

    private func signedInCard(_ user: BeyondIDUser) -> some View {
        VStack(alignment: .leading, spacing: 18) {
            HStack(spacing: 14) {
                Circle()
                    .fill(
                        LinearGradient(
                            colors: [.orange, .pink, .purple],
                            startPoint: .topLeading,
                            endPoint: .bottomTrailing
                        )
                    )
                    .frame(width: 58, height: 58)
                    .overlay {
                        Text(initials(for: user))
                            .font(.headline.weight(.black))
                            .foregroundStyle(.white)
                    }

                VStack(alignment: .leading, spacing: 4) {
                    Text(user.preferredName)
                        .font(.headline)
                    Text(user.email)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                        .lineLimit(1)
                }
                Spacer()
            }

            Divider().overlay(Color.white.opacity(0.14))

            LabeledContent("Role", value: user.role?.capitalized ?? "Member")
            LabeledContent("Wallet", value: model.beyondIDWallet?.balanceText ?? "0 BITS")

            Button(role: .destructive) {
                model.signOutBeyondID()
            } label: {
                Label("Sign Out", systemImage: "rectangle.portrait.and.arrow.right")
            }
            .buttonStyle(.bordered)
        }
        .padding(18)
        .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 18))
        .overlay(
            RoundedRectangle(cornerRadius: 18)
                .stroke(Color.white.opacity(0.12))
        )
    }

    private func initials(for user: BeyondIDUser) -> String {
        let parts = user.preferredName
            .split(separator: " ")
            .prefix(2)
            .compactMap(\.first)
        let value = String(parts).uppercased()
        return value.isEmpty ? "ID" : value
    }
}
