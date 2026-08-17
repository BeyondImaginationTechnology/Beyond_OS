import SwiftUI

struct LearningProgressView: View {
    @EnvironmentObject private var store: QuestStore

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()

                QuestCard {
                    VStack(alignment: .leading, spacing: 14) {
                        Text("QUEST HERO")
                            .font(.caption.weight(.black))
                            .foregroundStyle(store.theme.accent)
                        Text(heroTitle)
                            .font(.largeTitle.weight(.black))
                        Text("\(store.completedChallengeIDs.count) of \(store.totalChallenges) launch challenges cleared")
                            .foregroundStyle(.secondary)
                        SwiftUI.ProgressView(value: store.progress)
                            .tint(store.theme.accent)
                    }
                }

                QuestStatBar()

                QuestCard {
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            Label("Beyond ID", systemImage: "person.crop.circle.badge.checkmark")
                                .font(.title3.weight(.black))
                            Spacer()
                            if store.isCloudBusy { SwiftUI.ProgressView() }
                        }

                        if let account = store.beyondIDAccount {
                            Text(account.displayName).font(.headline)
                            Text(account.email).font(.caption).foregroundStyle(.secondary)
                            HStack {
                                Button {
                                    Task { await store.loadFromCloud() }
                                } label: {
                                    Label("Load", systemImage: "icloud.and.arrow.down.fill")
                                        .frame(maxWidth: .infinity)
                                }
                                .buttonStyle(.borderedProminent)

                                Button {
                                    Task { await store.saveToCloud() }
                                } label: {
                                    Label("Save", systemImage: "icloud.and.arrow.up.fill")
                                        .frame(maxWidth: .infinity)
                                }
                                .buttonStyle(.bordered)
                            }
                            .disabled(store.isCloudBusy)

                            Button("Sign Out", role: .destructive) {
                                store.signOutOfBeyondID()
                            }
                            .font(.subheadline.weight(.bold))
                        } else {
                            Text("Use your Beyond ID to keep this quest available across your iPhone and iPad.")
                                .foregroundStyle(.secondary)
                            Button {
                                Task { await store.signInToBeyondID() }
                            } label: {
                                Label("Sign in with Beyond ID", systemImage: "person.badge.key.fill")
                                    .frame(maxWidth: .infinity)
                            }
                            .buttonStyle(.borderedProminent)
                            .disabled(store.isCloudBusy)
                        }

                        Text(store.cloudMessage)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                }

                ThemePicker()

                QuestCard {
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Recovery")
                            .font(.title3.weight(.black))
                        Text("Refill your hearts to keep practicing and exploring.")
                            .foregroundStyle(.secondary)
                        HStack {
                            Button {
                                store.refillHearts()
                            } label: {
                                Label("Refill Hearts", systemImage: "heart.fill")
                                    .frame(maxWidth: .infinity)
                            }
                            .buttonStyle(.borderedProminent)

                            Button(role: .destructive) {
                                store.resetProgress()
                            } label: {
                                Label("Reset", systemImage: "arrow.counterclockwise")
                                    .frame(maxWidth: .infinity)
                            }
                            .buttonStyle(.bordered)
                        }
                    }
                }
            }
            .padding()
        }
        .background(store.theme.background)
        .navigationTitle("Hero")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var heroTitle: String {
        switch store.progress {
        case 0:
            return "New Explorer"
        case 0..<0.34:
            return "Bonjour Scout"
        case 0.34..<0.70:
            return "Cafe Navigator"
        case 0.70..<1:
            return "Metro Adventurer"
        default:
            return "French Quest Champion"
        }
    }
}
