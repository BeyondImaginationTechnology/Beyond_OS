import SwiftUI

struct SettingsView: View {
    @EnvironmentObject private var store: HealthStore
    @State private var confirmClear = false
    var body: some View {
        HealthScreen(title: "Settings") {
            Text("Your space").font(.largeTitle.bold()).foregroundStyle(.healthInk)
            HealthCard {
                Label("Private on this device", systemImage: "lock.shield")
                    .font(.headline)
                Text("Check-ins and notes are stored in this app on this device. v0.0.1 does not sync with the web app or another phone.")
                    .foregroundStyle(.secondary)
            }
            HealthCard {
                Text("Your data").font(.headline)
                Text("\(store.checkIns.count) check-ins · \(store.notes.count) notes")
                    .foregroundStyle(.secondary)
                Button("Delete all local data", role: .destructive) { confirmClear = true }
            }
        }
        .confirmationDialog("Delete all Beyond Health data on this device?", isPresented: $confirmClear) {
            Button("Delete all data", role: .destructive) { store.clearAll() }
        } message: {
            Text("This removes your check-ins and notes from this device.")
        }
    }
}
