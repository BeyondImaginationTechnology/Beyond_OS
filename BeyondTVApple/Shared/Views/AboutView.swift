import SwiftUI

struct AboutView: View {
    var body: some View {
        NavigationStack {
            Form {
                Section("Beyond TV") {
                    HStack(spacing: 14) {
                        Image("BeyondTVLogo")
                            .resizable()
                            .scaledToFit()
                            .frame(width: 56, height: 56)
                            .clipShape(RoundedRectangle(cornerRadius: 14))
                            .accessibilityHidden(true)
                        VStack(alignment: .leading, spacing: 4) {
                            Text("Beyond TV")
                                .font(.headline)
                            Text("Live channels from Beyond Imagination")
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                        }
                    }
                    LabeledContent("Version", value: versionText)
                    LabeledContent("Schedule", value: "America/Vancouver")
                    LabeledContent("Platforms", value: "iOS · iPadOS · tvOS")
                }

                Section("Playback") {
                    Text("Beyond TV uses AVPlayer for native MP4 and HLS streams. Provider attribution remains visible with each program.")
                }

                Section("Legal") {
                    Link("Privacy Policy", destination: URL(string: "https://beyondimagination.co.technology/legal/privacy.php")!)
                    Link("Terms of Use", destination: URL(string: "https://beyondimagination.co.technology/legal/terms.php")!)
                }
            }
            .navigationTitle("About")
        }
    }

    private var versionText: String {
        let version = Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "1.0"
        let build = Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String ?? "1"
        return "\(version) (\(build))"
    }
}
