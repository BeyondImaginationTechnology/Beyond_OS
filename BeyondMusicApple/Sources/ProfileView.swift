import SwiftUI

struct ProfileView: View {
    @EnvironmentObject private var store: MusicStore
    @State private var authMode: BeyondIDAuthMode = .signIn
    @State private var firstName = ""
    @State private var lastName = ""
    @State private var email = ""
    @State private var password = ""

    private let beyondIDURL = URL(string: "https://beyondimagination.co.technology/beyond-id/auth/login.php")!
    private var canSubmitBeyondID: Bool {
        let hasEmail = email.trimmingCharacters(in: .whitespacesAndNewlines).contains("@")
        let hasPassword = password.count >= 8
        if authMode == .signIn {
            return hasEmail && !password.isEmpty
        }
        return hasEmail && hasPassword && !firstName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty && !lastName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
    }

    var body: some View {
        MusicScreen(title: "Profile") {
            MusicPanel {
                MusicEyebrow(text: "Beyond Music 1.1.1")
                Text("Personal Music")
                    .font(.largeTitle.bold())
                Text("Import your own audio files, download tracks under their source terms, and keep playback running with the screen off.")
                    .foregroundStyle(.secondary)
            }

            MusicPanel {
                HStack(spacing: 12) {
                    Image(systemName: store.hasBeyondID ? "checkmark.seal.fill" : "person.badge.key.fill")
                        .font(.title)
                        .foregroundStyle(Color.musicAqua)
                    VStack(alignment: .leading, spacing: 4) {
                        MusicEyebrow(text: "Beyond ID")
                        Text(store.beyondIDSession.label)
                            .font(.title3.bold())
                        if store.hasBeyondID, !store.beyondIDSession.email.isEmpty {
                            Text(store.beyondIDSession.email)
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                        if store.hasBeyondID {
                            Text("User \(store.beyondIDSession.userID.map(String.init) ?? "unknown") · \(store.beyondIDSession.role ?? "user") · \(store.beyondIDSession.walletText)")
                                .font(.caption2)
                                .foregroundStyle(Color.musicGold)
                        }
                    }
                    Spacer()
                }

                if store.hasBeyondID {
                    HStack {
                        Button {
                            Task { await store.refreshBeyondIDSession() }
                        } label: {
                            Label("Verify Session", systemImage: "checkmark.shield")
                                .frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.bordered)
                        .disabled(store.isAuthenticatingBeyondID)

                        Button(role: .destructive) {
                            Task {
                                await store.signOutBeyondID()
                                password = ""
                            }
                        } label: {
                            Label("Sign Out", systemImage: "rectangle.portrait.and.arrow.right")
                                .frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.bordered)
                        .disabled(store.isAuthenticatingBeyondID)
                    }
                } else {
                    Button {
                        Task {
                            await store.signInBeyondIDWithGoogle()
                            password = ""
                        }
                    } label: {
                        Label("Continue with Google", systemImage: "g.circle.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(store.isAuthenticatingBeyondID)

                    Picker("Beyond ID mode", selection: $authMode) {
                        ForEach(BeyondIDAuthMode.allCases) { mode in
                            Text(mode.rawValue).tag(mode)
                        }
                    }
                    .pickerStyle(.segmented)

                    if authMode == .register {
                        TextField("First name", text: $firstName)
                            .textFieldStyle(.roundedBorder)
                            .textInputAutocapitalization(.words)
                        TextField("Last name", text: $lastName)
                            .textFieldStyle(.roundedBorder)
                            .textInputAutocapitalization(.words)
                    }

                    TextField("Beyond ID email", text: $email)
                        .textFieldStyle(.roundedBorder)
                        .textInputAutocapitalization(.never)
                        .keyboardType(.emailAddress)
                    SecureField("Beyond ID password", text: $password)
                        .textFieldStyle(.roundedBorder)

                    HStack {
                        Link(destination: beyondIDURL) {
                            Label("Open Beyond ID", systemImage: "safari")
                        }
                        .buttonStyle(.bordered)

                        Button {
                            Task {
                                switch authMode {
                                case .signIn:
                                    await store.signInBeyondID(email: email, password: password)
                                    password = ""
                                case .register:
                                    await store.registerBeyondID(firstName: firstName, lastName: lastName, email: email, password: password)
                                    password = ""
                                }
                            }
                        } label: {
                            Label(authMode.buttonTitle, systemImage: authMode.systemImage)
                                .frame(maxWidth: .infinity)
                        }
                        .buttonStyle(.borderedProminent)
                        .disabled(!canSubmitBeyondID || store.isAuthenticatingBeyondID)
                    }
                }

                if store.isAuthenticatingBeyondID {
                    ProgressView()
                }
                Text(store.statusMessage)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            MusicPanel {
                SettingRow(icon: "speaker.wave.2.fill", title: "Background audio", value: "Enabled")
                SettingRow(icon: "music.note.list", title: "Library songs", value: "\(store.tracks.count)")
                SettingRow(icon: "arrow.down.circle.fill", title: "Downloaded", value: "\(store.downloadedTracks.count)")
                SettingRow(icon: "folder.fill", title: "Imported", value: "\(store.importedTracks.count)")
                SettingRow(icon: "heart.fill", title: "Favorites", value: "\(store.favoriteTracks.count)")
                SettingRow(icon: "lock.shield.fill", title: "Source policy", value: "Terms-aware")
            }
        }
        .onAppear {
            email = store.beyondIDSession.email
        }
    }
}

private enum BeyondIDAuthMode: String, CaseIterable, Identifiable {
    case signIn = "Sign In"
    case register = "Register"

    var id: String { rawValue }

    var buttonTitle: String {
        switch self {
        case .signIn: "Sign In"
        case .register: "Create Beyond ID"
        }
    }

    var systemImage: String {
        switch self {
        case .signIn: "person.crop.circle.badge.checkmark"
        case .register: "person.crop.circle.badge.plus"
        }
    }
}

private struct SettingRow: View {
    let icon: String
    let title: String
    let value: String

    var body: some View {
        HStack(spacing: 12) {
            Image(systemName: icon)
                .foregroundStyle(Color.musicAqua)
                .frame(width: 28)
            Text(title)
            Spacer()
            Text(value)
                .font(.caption.bold())
                .foregroundStyle(.secondary)
        }
    }
}
