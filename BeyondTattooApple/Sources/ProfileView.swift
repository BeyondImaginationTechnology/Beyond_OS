import SwiftUI

struct ProfileView: View {
    @EnvironmentObject private var store: TattooStore

    var body: some View {
        TattooScreen(title: "Profile") {
            VStack(alignment: .leading, spacing: 14) {
                SectionTitle(text: "Beyond ID")
                HStack {
                    Image(systemName: store.hasBeyondID ? "checkmark.seal.fill" : "person.badge.key.fill")
                        .font(.largeTitle)
                        .foregroundStyle(store.hasBeyondID ? .green : .tattooViolet)
                    VStack(alignment: .leading, spacing: 4) {
                        Text(store.hasBeyondID ? "Signed in" : "Beta access")
                            .font(.title3.weight(.bold))
                            .foregroundStyle(Color.tattooInk)
                        Text(store.hasBeyondID ? "Your saved stencils and healing logs are ready." : "Join the beta to sync profile, studio requests, and rewards.")
                            .foregroundStyle(.secondary)
                    }
                    Spacer()
                }

                Link(destination: WebDestination.signup.url) {
                    Label("Join Beyond Tattoo beta", systemImage: "person.crop.circle.badge.plus")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            VStack(alignment: .leading, spacing: 12) {
                SectionTitle(text: "Role")
                Picker("Role", selection: $store.activeRole) {
                    ForEach(UserRole.allCases) { role in
                        Label(role.rawValue, systemImage: role.symbolName).tag(role)
                    }
                }
                .pickerStyle(.segmented)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            VStack(alignment: .leading, spacing: 10) {
                SectionTitle(text: "Quick links")
                ForEach(WebDestination.allCases) { destination in
                    Link(destination.rawValue, destination: destination.url)
                }
            }
            .padding()
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
        }
    }
}
