import SwiftUI

struct AcademyView: View {
    @EnvironmentObject private var store: DailyBreathStore
    @Environment(\.openURL) private var openURL

    private let beyondIDLoginURL = URL(string: "https://beyondimagination.co.technology/beyond-id/auth/login.php?app=dailybreath")!

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                Text("Bible Academy")
                    .font(.largeTitle.weight(.black))
                Text("Guided Bible learning for teens and adults, with narrated lessons, checks, and saved progress.")
                    .foregroundStyle(.secondary)

                academyComingSoon
                lockedPreview
            }
            .padding()
        }
        .background(Color(.systemGroupedBackground))
        .navigationTitle("Academy")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var academyComingSoon: some View {
        VStack(alignment: .leading, spacing: 16) {
            VStack(alignment: .leading, spacing: 8) {
                HStack(spacing: 10) {
                    Image(systemName: "lock.shield.fill")
                        .font(.title2)
                        .foregroundStyle(Color.dailyGold)
                    Text("Coming next update")
                        .font(.title2.weight(.bold))
                }
                Text("Bible Academy content will unlock through Beyond ID in Fall 2026.")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                Text("Sign in to Beyond ID when the Academy launch is ready.")
                    .font(.caption.bold())
                    .foregroundStyle(Color.dailyGreen)
            }

            Button {
                openURL(beyondIDLoginURL)
            } label: {
                Label("Sign In with Beyond ID", systemImage: "person.badge.key.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.borderedProminent)
            .tint(Color.dailyGreen)
            .controlSize(.large)
        }
        .padding()
        .background(.background, in: RoundedRectangle(cornerRadius: 20))
    }

    private var lockedPreview: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Fall 2026 Academy Preview")
                .font(.headline)

            ForEach(store.modules) { module in
                HStack(spacing: 12) {
                    Image(systemName: "lock.fill")
                        .foregroundStyle(Color.dailyGold)
                        .frame(width: 24)
                    VStack(alignment: .leading, spacing: 3) {
                        Text(module.title)
                            .font(.subheadline.weight(.semibold))
                        Text(module.subtitle)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                            .lineLimit(2)
                    }
                }
                .padding(12)
                .background(Color(.secondarySystemGroupedBackground), in: RoundedRectangle(cornerRadius: 14))
            }
        }
    }
}
