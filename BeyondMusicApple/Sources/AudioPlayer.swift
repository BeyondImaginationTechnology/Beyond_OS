@preconcurrency import AVFoundation
@preconcurrency import Foundation
@preconcurrency import ObjectiveC

@MainActor
final class AudioPlayer {
    private var player: AVPlayer?
    private var currentURL: URL?
    private var endObserver: NSObjectProtocol?
    var onPlaybackFinished: (() -> Void)?

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
        if let endObserver {
            NotificationCenter.default.removeObserver(endObserver)
        }
        let item = AVPlayerItem(url: url)
        endObserver = NotificationCenter.default.addObserver(
            forName: .AVPlayerItemDidPlayToEndTime,
            object: item,
            queue: .main
        ) { [weak self] _ in
            Task { @MainActor in
                self?.onPlaybackFinished?()
            }
        }
        player = AVPlayer(playerItem: item)
        player?.play()
    }

    func pause() {
        player?.pause()
    }

    func stop() {
        player?.pause()
        currentURL = nil
        player = nil
    }
}
