import AppKit
import Foundation

let root = URL(fileURLWithPath: FileManager.default.currentDirectoryPath)
let appRoot = root.appendingPathComponent("BeyondMusicApple")
let sourceIconURL = appRoot.appendingPathComponent("AppStoreAssets/AppIcon-Source.png")
let appStoreIconURL = appRoot.appendingPathComponent("AppStoreAssets/AppIcon-1024.png")
let iconSetURL = appRoot.appendingPathComponent("Resources/Assets.xcassets/AppIcon.appiconset")
let screenshotRootURL = appRoot.appendingPathComponent("AppStoreAssets/Screenshots")

func ensureDirectory(_ url: URL) throws {
    try FileManager.default.createDirectory(at: url, withIntermediateDirectories: true)
}

func writePNG(_ image: NSImage, to url: URL, width: Int, height: Int) throws {
    let rep = NSBitmapImageRep(
        bitmapDataPlanes: nil,
        pixelsWide: width,
        pixelsHigh: height,
        bitsPerSample: 8,
        samplesPerPixel: 4,
        hasAlpha: true,
        isPlanar: false,
        colorSpaceName: .deviceRGB,
        bytesPerRow: 0,
        bitsPerPixel: 0
    )!
    rep.size = NSSize(width: width, height: height)
    NSGraphicsContext.saveGraphicsState()
    NSGraphicsContext.current = NSGraphicsContext(bitmapImageRep: rep)
    image.draw(in: NSRect(x: 0, y: 0, width: width, height: height), from: .zero, operation: .copy, fraction: 1)
    NSGraphicsContext.restoreGraphicsState()
    guard let data = rep.representation(using: .png, properties: [:]) else {
        throw NSError(domain: "BeyondMusicAssets", code: 1, userInfo: [NSLocalizedDescriptionKey: "Could not encode PNG"])
    }
    try data.write(to: url)
}

func resizedImage(from source: NSImage, size: Int) -> NSImage {
    let image = NSImage(size: NSSize(width: size, height: size))
    image.lockFocus()
    NSGraphicsContext.current?.imageInterpolation = .high
    source.draw(in: NSRect(x: 0, y: 0, width: size, height: size), from: .zero, operation: .copy, fraction: 1)
    image.unlockFocus()
    return image
}

let iconSlots: [(idiom: String, size: String, scale: String, pixels: Int, filename: String)] = [
    ("iphone", "20x20", "2x", 40, "AppIcon-20@2x.png"),
    ("iphone", "20x20", "3x", 60, "AppIcon-20@3x.png"),
    ("iphone", "29x29", "2x", 58, "AppIcon-29@2x.png"),
    ("iphone", "29x29", "3x", 87, "AppIcon-29@3x.png"),
    ("iphone", "40x40", "2x", 80, "AppIcon-40@2x.png"),
    ("iphone", "40x40", "3x", 120, "AppIcon-40@3x.png"),
    ("iphone", "60x60", "2x", 120, "AppIcon-60@2x.png"),
    ("iphone", "60x60", "3x", 180, "AppIcon-60@3x.png"),
    ("ipad", "20x20", "1x", 20, "AppIcon-20~ipad.png"),
    ("ipad", "20x20", "2x", 40, "AppIcon-20@2x~ipad.png"),
    ("ipad", "29x29", "1x", 29, "AppIcon-29~ipad.png"),
    ("ipad", "29x29", "2x", 58, "AppIcon-29@2x~ipad.png"),
    ("ipad", "40x40", "1x", 40, "AppIcon-40~ipad.png"),
    ("ipad", "40x40", "2x", 80, "AppIcon-40@2x~ipad.png"),
    ("ipad", "76x76", "1x", 76, "AppIcon-76.png"),
    ("ipad", "76x76", "2x", 152, "AppIcon-76@2x.png"),
    ("ipad", "83.5x83.5", "2x", 167, "AppIcon-83.5@2x.png"),
    ("ios-marketing", "1024x1024", "1x", 1024, "AppIcon-1024.png")
]

func generateIcons() throws {
    guard let source = NSImage(contentsOf: sourceIconURL) else {
        throw NSError(domain: "BeyondMusicAssets", code: 2, userInfo: [NSLocalizedDescriptionKey: "Missing source icon at \(sourceIconURL.path)"])
    }
    try ensureDirectory(iconSetURL)
    for slot in iconSlots {
        let image = resizedImage(from: source, size: slot.pixels)
        try writePNG(image, to: iconSetURL.appendingPathComponent(slot.filename), width: slot.pixels, height: slot.pixels)
        if slot.pixels == 1024 {
            try writePNG(image, to: appStoreIconURL, width: 1024, height: 1024)
        }
    }
    let images = iconSlots.map { slot in
        [
            "filename": slot.filename,
            "idiom": slot.idiom,
            "scale": slot.scale,
            "size": slot.size
        ]
    }
    let contents: [String: Any] = [
        "images": images,
        "info": ["author": "xcode", "version": 1]
    ]
    let data = try JSONSerialization.data(withJSONObject: contents, options: [.prettyPrinted, .sortedKeys])
    try data.write(to: iconSetURL.appendingPathComponent("Contents.json"))
}

struct ScreenshotSpec {
    let slug: String
    let title: String
    let subtitle: String
    let tab: String
    let lines: [String]
    let accent: NSColor
}

let screenshotSpecs = [
    ScreenshotSpec(slug: "search-open-music", title: "Search open music", subtitle: "Find tracks across public and creator-friendly catalogs.", tab: "Discover", lines: ["Authorized results", "Provider shown", "License note visible", "Download after review"], accent: NSColor(calibratedRed: 0.27, green: 0.93, blue: 0.86, alpha: 1)),
    ScreenshotSpec(slug: "play-screen-off", title: "Play with screen off", subtitle: "Background audio mode keeps your queue moving.", tab: "Listen", lines: ["Now playing", "Local or streamed audio", "Lock-screen friendly", "Audio session: playback"], accent: NSColor(calibratedRed: 0.93, green: 0.31, blue: 0.61, alpha: 1)),
    ScreenshotSpec(slug: "download-offline", title: "Download for offline", subtitle: "Save authorized audio into your personal library.", tab: "Library", lines: ["Local files", "Stored on device", "Metadata from audio", "Tap to play anytime"], accent: NSColor(calibratedRed: 0.98, green: 0.78, blue: 0.30, alpha: 1)),
    ScreenshotSpec(slug: "random-discovery", title: "Pages of discovery", subtitle: "Next page and random audio keep results fresh.", tab: "Discover", lines: ["Next page", "Random audio", "Open catalog search", "Dedupe built in"], accent: NSColor(calibratedRed: 0.42, green: 0.72, blue: 1.0, alpha: 1)),
    ScreenshotSpec(slug: "personal-service", title: "Your music service", subtitle: "Search, play, download, import, and review source licenses.", tab: "Profile", lines: ["Background audio enabled", "Imported files", "Authorized/open policy", "Private library"], accent: NSColor(calibratedRed: 0.72, green: 0.52, blue: 1.0, alpha: 1))
]

func drawText(_ text: String, in rect: NSRect, size: CGFloat, weight: NSFont.Weight, color: NSColor, alignment: NSTextAlignment = .left) {
    let paragraph = NSMutableParagraphStyle()
    paragraph.alignment = alignment
    paragraph.lineBreakMode = .byWordWrapping
    let font = NSFont.systemFont(ofSize: size, weight: weight)
    let attrs: [NSAttributedString.Key: Any] = [.font: font, .foregroundColor: color, .paragraphStyle: paragraph]
    text.draw(in: rect, withAttributes: attrs)
}

func drawRoundedRect(_ rect: NSRect, radius: CGFloat, color: NSColor) {
    color.setFill()
    NSBezierPath(roundedRect: rect, xRadius: radius, yRadius: radius).fill()
}

func drawIconMark(in rect: NSRect, accent: NSColor) {
    let ring = NSBezierPath(ovalIn: rect)
    NSColor(calibratedWhite: 1, alpha: 0.13).setStroke()
    ring.lineWidth = 7
    ring.stroke()

    let wave = NSBezierPath()
    wave.move(to: NSPoint(x: rect.minX + 44, y: rect.midY))
    wave.curve(to: NSPoint(x: rect.midX - 40, y: rect.midY + 80), controlPoint1: NSPoint(x: rect.minX + 90, y: rect.midY - 80), controlPoint2: NSPoint(x: rect.midX - 95, y: rect.midY + 120))
    wave.curve(to: NSPoint(x: rect.midX + 56, y: rect.midY - 35), controlPoint1: NSPoint(x: rect.midX + 10, y: rect.midY + 45), controlPoint2: NSPoint(x: rect.midX + 5, y: rect.midY - 95))
    wave.curve(to: NSPoint(x: rect.maxX - 44, y: rect.midY), controlPoint1: NSPoint(x: rect.midX + 106, y: rect.midY + 52), controlPoint2: NSPoint(x: rect.maxX - 94, y: rect.midY - 32))
    accent.setStroke()
    wave.lineWidth = 11
    wave.lineCapStyle = .round
    wave.stroke()

    let play = NSBezierPath()
    play.move(to: NSPoint(x: rect.midX - 22, y: rect.midY - 54))
    play.line(to: NSPoint(x: rect.midX - 22, y: rect.midY + 54))
    play.line(to: NSPoint(x: rect.midX + 66, y: rect.midY))
    play.close()
    NSColor.white.withAlphaComponent(0.85).setFill()
    play.fill()
}

func makeScreenshot(width: Int, height: Int, spec: ScreenshotSpec) -> NSImage {
    let image = NSImage(size: NSSize(width: width, height: height))
    image.lockFocus()

    NSColor(calibratedRed: 0.035, green: 0.04, blue: 0.06, alpha: 1).setFill()
    NSRect(x: 0, y: 0, width: width, height: height).fill()

    let bg = NSGradient(colors: [
        NSColor(calibratedRed: 0.02, green: 0.03, blue: 0.05, alpha: 1),
        spec.accent.withAlphaComponent(0.28),
        NSColor(calibratedRed: 0.13, green: 0.04, blue: 0.09, alpha: 1)
    ])!
    bg.draw(in: NSRect(x: 0, y: 0, width: width, height: height), angle: 35)

    let margin = CGFloat(width) * 0.075
    let top = CGFloat(height) - margin - 60
    drawText("BEYOND MUSIC", in: NSRect(x: margin, y: top, width: CGFloat(width) - margin * 2, height: 40), size: 34, weight: .heavy, color: spec.accent)
    drawText(spec.title, in: NSRect(x: margin, y: top - 190, width: CGFloat(width) - margin * 2, height: 160), size: 86, weight: .black, color: .white)
    drawText(spec.subtitle, in: NSRect(x: margin, y: top - 285, width: CGFloat(width) - margin * 2, height: 86), size: 38, weight: .semibold, color: NSColor(calibratedWhite: 0.82, alpha: 1))

    let phoneWidth = CGFloat(width) * 0.78
    let phoneHeight = CGFloat(height) * 0.54
    let phoneX = (CGFloat(width) - phoneWidth) / 2
    let phoneY = CGFloat(height) * 0.10
    drawRoundedRect(NSRect(x: phoneX - 14, y: phoneY - 14, width: phoneWidth + 28, height: phoneHeight + 28), radius: 82, color: NSColor.black.withAlphaComponent(0.42))
    drawRoundedRect(NSRect(x: phoneX, y: phoneY, width: phoneWidth, height: phoneHeight), radius: 70, color: NSColor(calibratedRed: 0.06, green: 0.07, blue: 0.09, alpha: 1))
    drawRoundedRect(NSRect(x: phoneX + 34, y: phoneY + 34, width: phoneWidth - 68, height: phoneHeight - 68), radius: 44, color: NSColor(calibratedRed: 0.09, green: 0.10, blue: 0.13, alpha: 1))

    let screenX = phoneX + 72
    let screenY = phoneY + 76
    let screenW = phoneWidth - 144
    let screenH = phoneHeight - 152
    drawText(spec.tab, in: NSRect(x: screenX, y: screenY + screenH - 66, width: screenW, height: 44), size: 34, weight: .bold, color: .white)

    let hero = NSRect(x: screenX, y: screenY + screenH - 315, width: screenW, height: 200)
    drawRoundedRect(hero, radius: 18, color: NSColor(calibratedRed: 0.12, green: 0.14, blue: 0.17, alpha: 1))
    drawIconMark(in: NSRect(x: hero.minX + 28, y: hero.minY + 35, width: 130, height: 130), accent: spec.accent)
    drawText(spec.lines[0], in: NSRect(x: hero.minX + 185, y: hero.maxY - 72, width: hero.width - 220, height: 42), size: 31, weight: .bold, color: .white)
    drawText(spec.lines[1], in: NSRect(x: hero.minX + 185, y: hero.maxY - 126, width: hero.width - 220, height: 42), size: 26, weight: .medium, color: NSColor(calibratedWhite: 0.72, alpha: 1))

    for index in 0..<3 {
        let y = hero.minY - CGFloat(index + 1) * 118
        drawRoundedRect(NSRect(x: screenX, y: y, width: screenW, height: 90), radius: 14, color: NSColor(calibratedRed: 0.13, green: 0.14, blue: 0.17, alpha: 1))
        drawRoundedRect(NSRect(x: screenX + 20, y: y + 22, width: 46, height: 46), radius: 10, color: spec.accent.withAlphaComponent(0.88))
        let line = spec.lines[min(index + 1, spec.lines.count - 1)]
        drawText(line, in: NSRect(x: screenX + 88, y: y + 27, width: screenW - 150, height: 42), size: 25, weight: .semibold, color: .white)
    }

    let mini = NSRect(x: screenX, y: screenY + 28, width: screenW, height: 96)
    drawRoundedRect(mini, radius: 18, color: NSColor(calibratedRed: 0.05, green: 0.06, blue: 0.08, alpha: 0.96))
    drawText("Now playing", in: NSRect(x: mini.minX + 28, y: mini.minY + 51, width: mini.width - 140, height: 24), size: 18, weight: .bold, color: spec.accent)
    drawText("Local library ready", in: NSRect(x: mini.minX + 28, y: mini.minY + 22, width: mini.width - 140, height: 24), size: 21, weight: .semibold, color: .white)
    drawRoundedRect(NSRect(x: mini.maxX - 72, y: mini.minY + 24, width: 48, height: 48), radius: 24, color: spec.accent)

    image.unlockFocus()
    return image
}

func generateScreenshots() throws {
    let sizes = [
        ("iPhone-6.9", 1320, 2868),
        ("iPhone-6.7", 1290, 2796),
        ("iPhone-6.5", 1242, 2688),
        ("iPad-13", 2048, 2732)
    ]

    for (folder, width, height) in sizes {
        let directory = screenshotRootURL.appendingPathComponent(folder)
        try ensureDirectory(directory)
        for (index, spec) in screenshotSpecs.enumerated() {
            let image = makeScreenshot(width: width, height: height, spec: spec)
            let filename = String(format: "%02d-%@.png", index + 1, spec.slug)
            try writePNG(image, to: directory.appendingPathComponent(filename), width: width, height: height)
        }
    }
}

try generateIcons()
try generateScreenshots()
print("Generated Beyond Music icon and App Store screenshots.")
