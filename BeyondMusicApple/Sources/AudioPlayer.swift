import AVFoundation
import Foundation

@MainActor
final class AudioPlayer {
    private var player: AVPlayer?
    private var currentURL: URL?

    func configureForBackgroundPlayback() {
        do {
            try AVAudioSession.sharedInstance().setCategory(.playback, mode: .default, options: [])
            try AVAudioSession.sharedInstance().setActive(true)
        } catch {
            assertionFailure("Unable to configure audio session: \(error.localizedDescription)")
        }
    }

    func play(url: URL) {
        if currentURL == url {
            player?.play()
            return
        }

        currentURL = url
        player = AVPlayer(url: url)
        player?.play()
    }

    func pause() {
        player?.pause()
    }
}
