#if os(iOS)
import SwiftUI
import WebKit

struct WebPlayerView: UIViewRepresentable {
    let url: URL

    func makeCoordinator() -> Coordinator {
        Coordinator()
    }

    func makeUIView(context: Context) -> WKWebView {
        let configuration = WKWebViewConfiguration()
        configuration.allowsInlineMediaPlayback = true
        configuration.mediaTypesRequiringUserActionForPlayback = []

        let webView = WKWebView(frame: .zero, configuration: configuration)
        webView.isOpaque = false
        webView.backgroundColor = .black
        webView.scrollView.backgroundColor = .black
        load(url, in: webView, coordinator: context.coordinator)
        return webView
    }

    func updateUIView(_ webView: WKWebView, context: Context) {
        guard context.coordinator.loadedURL != url else { return }
        load(url, in: webView, coordinator: context.coordinator)
    }

    private func load(_ url: URL, in webView: WKWebView, coordinator: Coordinator) {
        var components = URLComponents(url: url, resolvingAgainstBaseURL: true)
        if url.host()?.contains("youtube") == true {
            var items = components?.queryItems ?? []
            if !items.contains(where: { $0.name == "origin" }) {
                items.append(URLQueryItem(
                    name: "origin",
                    value: "https://beyondimagination.co.technology"
                ))
            }
            if !items.contains(where: { $0.name == "widget_referrer" }) {
                items.append(URLQueryItem(
                    name: "widget_referrer",
                    value: "https://beyondimagination.co.technology/beyond-tv/"
                ))
            }
            components?.queryItems = items
        }

        let playbackURL = components?.url ?? url
        var request = URLRequest(url: playbackURL)
        request.setValue(
            "https://beyondimagination.co.technology/beyond-tv/",
            forHTTPHeaderField: "Referer"
        )
        coordinator.loadedURL = url
        webView.load(request)
    }

    final class Coordinator {
        var loadedURL: URL?
    }
}
#endif
