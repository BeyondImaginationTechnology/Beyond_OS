import SwiftUI

struct AboutView: View {
    var body: some View {
        NavigationStack {
            Form {
                Section("Beyond TV") {
                    LabeledContent("Version", value: "1.0 (1)")
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
}
