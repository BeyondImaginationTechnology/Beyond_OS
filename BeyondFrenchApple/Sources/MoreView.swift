import SwiftUI

struct MoreView: View {
    @EnvironmentObject private var store: AppStore
    @EnvironmentObject private var auth: BeyondFrenchAuth
    @State private var showDeletionConfirmation = false
    @State private var deletionMessage: String?

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()

                VStack(alignment: .leading, spacing: 12) {
                    Text("Your progress")
                        .font(.title2.weight(.black))
                    Text(auth.isSignedIn
                         ? "Your Beyond ID keeps French progress across devices."
                         : "Learn as a guest. Sign in when you want to save progress across devices.")
                        .foregroundStyle(.secondary)
                    Text("\(store.completedAcademyLessonCount) Academy lessons · \(store.correctPracticeCount) practice answers")
                        .font(.subheadline.weight(.bold))
                    if let message = auth.message { Text(message).font(.footnote).foregroundStyle(.secondary) }
                    if let status = store.cloudStatus { Text(status).font(.footnote).foregroundStyle(.secondary) }

                    if auth.isSignedIn {
                        Button { Task { await store.syncProgress() } } label: {
                            Label("Sync now", systemImage: "arrow.triangle.2.circlepath")
                        }
                        .buttonStyle(.borderedProminent)
                        Button("Sign out") {
                            store.switchProgressAccount(to: nil)
                            auth.signOut()
                        }
                        .buttonStyle(.bordered)
                    } else {
                        Button { auth.signIn() } label: {
                            Label("Sign in with Beyond ID", systemImage: "person.crop.circle")
                        }
                        .buttonStyle(.borderedProminent)
                    }
                }
                .frame(maxWidth: .infinity, alignment: .leading)
                .padding(18)
                .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 20))

                NavigationLink { FrenchQuestHubView() } label: {
                    Label("French Quest", systemImage: "map.fill")
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .padding(18)
                        .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
                }
                .buttonStyle(.plain)

                NavigationLink { LearningProgressView() } label: {
                    Label("Progress by difficulty", systemImage: "chart.bar.fill")
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .padding(18)
                        .background(store.appTheme.cardFill, in: RoundedRectangle(cornerRadius: 18))
                }
                .buttonStyle(.plain)

                ThemePicker()

                if auth.isSignedIn {
                    Button("Request Beyond ID account deletion", role: .destructive) {
                        showDeletionConfirmation = true
                    }
                    .font(.footnote)
                    if let deletionMessage { Text(deletionMessage).font(.footnote) }
                }
            }
            .padding(20)
        }
        .background(store.appTheme.appBackground.ignoresSafeArea())
        .navigationTitle("More")
        .onChange(of: auth.userID) { _, userID in
            if let userID {
                store.switchProgressAccount(to: userID)
                Task { await store.syncProgress() }
            }
        }
        .confirmationDialog(
            "Request deletion of your Beyond ID and its saved data?",
            isPresented: $showDeletionConfirmation,
            titleVisibility: .visible
        ) {
            Button("Request account deletion", role: .destructive) {
                Task { await requestAccountDeletion() }
            }
        } message: {
            Text("This affects your Beyond ID across connected apps. The request is processed by the Beyond ID team.")
        }
    }

    private func requestAccountDeletion() async {
        guard let token = auth.accessToken else { return }
        var request = URLRequest(url: URL(string: "https://beyondimagination.co.technology/beyond-id/api/account-deletion-request.php")!)
        request.httpMethod = "POST"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try? JSONEncoder().encode(["confirm": "DELETE"])
        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            let result = try? JSONDecoder().decode(AccountDeletionResponse.self, from: data)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200 else {
                deletionMessage = result?.error ?? "Could not submit the deletion request."
                return
            }
            deletionMessage = result?.message ?? "Account deletion request submitted."
        } catch {
            deletionMessage = "Could not connect. Try again later."
        }
    }
}

private struct AccountDeletionResponse: Decodable {
    let message: String?
    let error: String?
}
