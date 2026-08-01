import SwiftUI

struct ProfileView: View {
    @EnvironmentObject private var store: MusicStore

    var body: some View {
        MusicScreen(title: "Profile") {
            MusicPanel {
                MusicEyebrow(text: "Plan")
                Text("Personal Music Service")
                    .font(.largeTitle.bold())
                Text("Search, play, download, and keep audio going with the screen off.")
                    .foregroundStyle(.secondary)
            }

            MusicPanel {
                SettingRow(icon: "speaker.wave.2.fill", title: "Background audio", value: "Enabled")
                SettingRow(icon: "arrow.down.circle.fill", title: "Offline tracks", value: "\(store.downloadedTracks.count)")
                SettingRow(icon: "lock.shield.fill", title: "Source policy", value: "Authorized/open")
            }
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
