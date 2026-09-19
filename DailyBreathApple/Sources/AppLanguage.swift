import SwiftUI

enum DailyBreathLanguage: String, CaseIterable, Identifiable {
    case english = "en"
    case french = "fr"
    case spanish = "es"

    var id: String { rawValue }
    var name: String {
        switch self {
        case .english: "English"
        case .french: "Français"
        case .spanish: "Español"
        }
    }
}

struct LanguageSetupView: View {
    @Binding var languageID: String

    var body: some View {
        VStack(spacing: 22) {
            Spacer()
            Image("DailyBreathIcon")
                .resizable().scaledToFit().frame(width: 104, height: 104)
                .clipShape(RoundedRectangle(cornerRadius: 26))
            VStack(spacing: 8) {
                Text("Choose your language").font(.largeTitle.bold())
                Text("Choisissez votre langue · Elige tu idioma")
                    .foregroundStyle(.secondary).multilineTextAlignment(.center)
            }
            VStack(spacing: 12) {
                ForEach(DailyBreathLanguage.allCases) { language in
                    Button {
                        languageID = language.rawValue
                    } label: {
                        Text(language.name).frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(.dailyGreen)
                    .controlSize(.large)
                }
            }
            .frame(maxWidth: 420)
            Spacer()
            Text("You can change this later in Settings.")
                .font(.caption).foregroundStyle(.secondary)
        }
        .padding(28)
        .background(DailyBreathThemeBackground(theme: .forest))
        .interactiveDismissDisabled()
    }
}

struct AccountChoiceView: View {
    @EnvironmentObject private var auth: BeyondIDAuthManager
    @Binding var onboardingComplete: Bool

    var body: some View {
        VStack(spacing: 22) {
            Spacer()
            Image(systemName: "person.crop.circle.badge.checkmark")
                .font(.system(size: 72))
                .foregroundStyle(Color.dailyGreen)
            VStack(spacing: 8) {
                Text("Choose how to begin")
                    .font(.largeTitle.bold())
                    .multilineTextAlignment(.center)
                Text("Sign in to sync progress and unlock purchases across your devices, or explore free content locally.")
                    .foregroundStyle(.secondary)
                    .multilineTextAlignment(.center)
            }
            VStack(spacing: 12) {
                Button {
                    auth.signIn()
                } label: {
                    Label("Sign in with Beyond-ID", systemImage: "person.crop.circle.badge.checkmark")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(.dailyGreen)
                .controlSize(.large)
                if let message = auth.message {
                    Text(message).font(.footnote).foregroundStyle(.secondary)
                }

                Button("Continue without signing in") {
                    onboardingComplete = true
                }
                .buttonStyle(.bordered)
                .controlSize(.large)
            }
            .frame(maxWidth: 420)
            Spacer()
            Text("You can sign in later from Academy or Settings.")
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .padding(28)
        .background(DailyBreathThemeBackground(theme: .forest))
        .interactiveDismissDisabled()
        .onChange(of: auth.isSignedIn) { _, signedIn in
            if signedIn { onboardingComplete = true }
        }
    }
}
