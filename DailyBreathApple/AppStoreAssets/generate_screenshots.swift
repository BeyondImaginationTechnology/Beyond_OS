import AppKit
import CoreGraphics
import Foundation

let root = URL(fileURLWithPath: FileManager.default.currentDirectoryPath)
let green = NSColor(calibratedRed: 0.09, green: 0.25, blue: 0.17, alpha: 1)
let deepGreen = NSColor(calibratedRed: 0.03, green: 0.12, blue: 0.08, alpha: 1)
let gold = NSColor(calibratedRed: 0.82, green: 0.64, blue: 0.30, alpha: 1)
let cream = NSColor(calibratedRed: 0.96, green: 0.92, blue: 0.84, alpha: 1)
let muted = NSColor(calibratedRed: 0.69, green: 0.74, blue: 0.69, alpha: 1)
var canvasHeight: CGFloat = 0

struct StoreScreen {
    let file: String
    let eyebrow: String
    let title: String
    let subtitle: String
    let tab: String
    let cards: [(String, String, String)]
}

struct ScreenshotFormat {
    let folder: String
    let width: Int
    let height: Int
    let deviceTitle: String
    let chrome: CGRect
    let content: CGRect
}

let screens = [
    StoreScreen(
        file: "01-today-verse.png",
        eyebrow: "VERSE OF THE DAY",
        title: "Begin with the Word.",
        subtitle: "Read, listen, breathe, and carry peace into your day.",
        tab: "Today",
        cards: [
            ("Psalm 46:10", "\"Be still, and know that I am God.\"", "A quiet reflection and one-tap listening experience for daily Scripture."),
            ("Today's Devotional", "Walk in Quiet Confidence", "A five-minute reading to help you pause before the next step."),
            ("Quick Practices", "Bible · Prayer · Breath", "Simple spiritual rhythms for calm, gratitude, and focus.")
        ]
    ),
    StoreScreen(
        file: "02-bible-library.png",
        eyebrow: "FULL BIBLE",
        title: "Read all 66 books.",
        subtitle: "Search the World English Bible, open chapters, and keep your place offline.",
        tab: "Bible",
        cards: [
            ("World English Bible", "31,103 searchable verses", "Genesis through Revelation bundled locally for daily reading."),
            ("Chapters", "Book-by-book navigation", "Old and New Testament sections make Scripture easy to browse."),
            ("Continue Reading", "Saved chapter place", "Return to the last chapter you opened without needing a connection.")
        ]
    ),
    StoreScreen(
        file: "03-bible-academy.png",
        eyebrow: "BIBLE ACADEMY",
        title: "Choose your Academy journey.",
        subtitle: "Joining the Christian Faith or Christian Recovery, guided by Chris.",
        tab: "Academy",
        cards: [
            ("Path 1", "Joining the Faith", "Scripture, prayer, baptism, and healthy church community."),
            ("Path 2", "Recovery", "Grace, support, safeguards, and the next healthy step."),
            ("Final Milestone", "Beyond Imagination Certificate", "Unlock after completing both Academy journeys.")
        ]
    ),
    StoreScreen(
        file: "04-peace-breath.png",
        eyebrow: "PEACE BREATH",
        title: "Breathe with Scripture.",
        subtitle: "Follow a gentle rhythm for calm, focus, and prayer.",
        tab: "Breathe",
        cards: [
            ("Four-count rhythm", "Inhale · Hold · Exhale", "A simple breath practice for centering your mind."),
            ("Guidance Prayer", "Ask for wisdom", "Pause before decisions and move with intention."),
            ("Gratitude Reset", "Notice what is good", "A small daily practice that changes the tone of the day.")
        ]
    ),
    StoreScreen(
        file: "05-reflection-journal.png",
        eyebrow: "REFLECTION JOURNAL",
        title: "Keep what matters.",
        subtitle: "Respond to daily prompts and save personal reflections.",
        tab: "Journal",
        cards: [
            ("Today's Prompt", "Where do I need stillness?", "Write privately after reading, breathing, or prayer."),
            ("Saved Reflections", "Return to your growth", "A simple place for spiritual notes and moments of clarity."),
            ("Daily Rhythm", "Read · Breathe · Reflect", "Build a steady practice without clutter.")
        ]
    )
]

let formats = [
    ScreenshotFormat(
        folder: "iPhone-6.5",
        width: 1242,
        height: 2688,
        deviceTitle: "DailyBreath",
        chrome: CGRect(x: 96, y: 206, width: 1050, height: 2170),
        content: CGRect(x: 122, y: 238, width: 998, height: 2110)
    ),
    ScreenshotFormat(
        folder: "iPad-13",
        width: 2064,
        height: 2752,
        deviceTitle: "DailyBreath for iPad",
        chrome: CGRect(x: 174, y: 220, width: 1716, height: 2280),
        content: CGRect(x: 214, y: 260, width: 1636, height: 2200)
    )
]

func paragraph(_ text: String, font: NSFont, color: NSColor, rect: CGRect, lineHeight: CGFloat? = nil, alignment: NSTextAlignment = .left) {
    let style = NSMutableParagraphStyle()
    style.alignment = alignment
    style.lineBreakMode = .byWordWrapping
    if let lineHeight {
        style.minimumLineHeight = lineHeight
        style.maximumLineHeight = lineHeight
    }
    let attrs: [NSAttributedString.Key: Any] = [
        .font: font,
        .foregroundColor: color,
        .paragraphStyle: style
    ]
    NSString(string: text).draw(in: topLeft(rect), withAttributes: attrs)
}

func roundedRect(_ rect: CGRect, radius: CGFloat, fill: NSColor, stroke: NSColor? = nil, lineWidth: CGFloat = 1) {
    let path = NSBezierPath(roundedRect: topLeft(rect), xRadius: radius, yRadius: radius)
    fill.setFill()
    path.fill()
    if let stroke {
        stroke.setStroke()
        path.lineWidth = lineWidth
        path.stroke()
    }
}

func circle(_ rect: CGRect, fill: NSColor, stroke: NSColor? = nil, lineWidth: CGFloat = 1) {
    let path = NSBezierPath(ovalIn: topLeft(rect))
    fill.setFill()
    path.fill()
    if let stroke {
        stroke.setStroke()
        path.lineWidth = lineWidth
        path.stroke()
    }
}

func topLeft(_ rect: CGRect) -> CGRect {
    CGRect(x: rect.minX, y: canvasHeight - rect.minY - rect.height, width: rect.width, height: rect.height)
}

func writePNG(_ image: NSImage, to url: URL) throws {
    guard let tiff = image.tiffRepresentation,
          let rep = NSBitmapImageRep(data: tiff),
          let data = rep.representation(using: .png, properties: [:]) else {
        throw NSError(domain: "DailyBreathScreenshots", code: 1)
    }
    try data.write(to: url)
}

func drawTabIcon(_ tab: String, in rect: CGRect, selected: Bool) {
    let centerX = rect.midX
    let iconRect = CGRect(x: centerX - 21, y: rect.minY + 12, width: 42, height: 42)
    circle(iconRect, fill: selected ? gold : NSColor.white.withAlphaComponent(0.14))
    paragraph(tab, font: .systemFont(ofSize: 20, weight: selected ? .bold : .medium), color: selected ? gold : cream, rect: CGRect(x: rect.minX, y: rect.minY + 56, width: rect.width, height: 28), alignment: .center)
}

func drawDeviceChrome(format: ScreenshotFormat, activeTab: String) {
    let chrome = format.chrome
    let content = format.content
    roundedRect(chrome, radius: format.folder == "iPad-13" ? 58 : 76, fill: NSColor.black.withAlphaComponent(0.82), stroke: NSColor.white.withAlphaComponent(0.22), lineWidth: 4)
    roundedRect(content, radius: format.folder == "iPad-13" ? 40 : 56, fill: NSColor(calibratedRed: 0.95, green: 0.96, blue: 0.94, alpha: 1))
    roundedRect(CGRect(x: content.midX - 132, y: content.minY + 30, width: 264, height: 34), radius: 17, fill: NSColor.black.withAlphaComponent(0.78))
    paragraph("9:41", font: .systemFont(ofSize: 30, weight: .bold), color: .black, rect: CGRect(x: content.minX + 54, y: content.minY + 32, width: 120, height: 42))

    let tabs = ["Today", "Bible", "Academy", "Breathe", "Journal"]
    let tabBar = CGRect(x: content.minX + 40, y: content.maxY - 142, width: content.width - 80, height: 92)
    roundedRect(tabBar, radius: 30, fill: green)
    let itemWidth = tabBar.width / CGFloat(tabs.count)
    for (index, tab) in tabs.enumerated() {
        let item = CGRect(x: tabBar.minX + CGFloat(index) * itemWidth, y: tabBar.minY, width: itemWidth, height: tabBar.height)
        drawTabIcon(tab, in: item, selected: tab == activeTab)
    }
}

func drawAppContent(_ screen: StoreScreen, format: ScreenshotFormat) {
    let content = format.content
    let margin: CGFloat = format.folder == "iPad-13" ? 86 : 56
    let x = content.minX + margin
    let maxWidth = content.width - (margin * 2)

    paragraph("DAILYBREATH", font: .systemFont(ofSize: 28, weight: .black), color: green, rect: CGRect(x: x, y: content.minY + 118, width: 330, height: 40))
    paragraph("Faith-centered wellness", font: .systemFont(ofSize: 22, weight: .medium), color: muted, rect: CGRect(x: x, y: content.minY + 156, width: 340, height: 32))
    roundedRect(CGRect(x: content.maxX - margin - 96, y: content.minY + 118, width: 96, height: 42), radius: 21, fill: green.withAlphaComponent(0.12))
    paragraph("1.2", font: .systemFont(ofSize: 20, weight: .bold), color: green, rect: CGRect(x: content.maxX - margin - 96, y: content.minY + 128, width: 96, height: 24), alignment: .center)

    let heroHeight: CGFloat = format.folder == "iPad-13" ? 590 : 650
    roundedRect(CGRect(x: x, y: content.minY + 232, width: maxWidth, height: heroHeight), radius: 34, fill: green)
    paragraph(screen.eyebrow, font: .systemFont(ofSize: 24, weight: .black), color: gold, rect: CGRect(x: x + 48, y: content.minY + 292, width: 720, height: 34))
    paragraph(screen.title, font: .systemFont(ofSize: format.folder == "iPad-13" ? 72 : 66, weight: .black), color: .white, rect: CGRect(x: x + 48, y: content.minY + 360, width: maxWidth - 96, height: 170), lineHeight: format.folder == "iPad-13" ? 80 : 74)
    paragraph(screen.subtitle, font: .systemFont(ofSize: 31, weight: .medium), color: cream, rect: CGRect(x: x + 48, y: content.minY + 550, width: maxWidth - 160, height: 92), lineHeight: 42)
    roundedRect(CGRect(x: x + 48, y: content.minY + 690, width: 322, height: 74), radius: 37, fill: gold)
    paragraph("Open \(screen.tab)", font: .systemFont(ofSize: 26, weight: .black), color: deepGreen, rect: CGRect(x: x + 48, y: content.minY + 710, width: 322, height: 34), alignment: .center)

    let columns = format.folder == "iPad-13" ? 2 : 1
    let gap: CGFloat = 28
    let cardWidth = (maxWidth - (CGFloat(columns - 1) * gap)) / CGFloat(columns)
    let cardHeight: CGFloat = format.folder == "iPad-13" ? 302 : 256
    let startY = content.minY + heroHeight + 276

    for (index, card) in screen.cards.enumerated() {
        let column = index % columns
        let row = index / columns
        let cardX = x + CGFloat(column) * (cardWidth + gap)
        let cardY = startY + CGFloat(row) * (cardHeight + gap)
        roundedRect(CGRect(x: cardX, y: cardY, width: cardWidth, height: cardHeight), radius: 28, fill: .white, stroke: NSColor.black.withAlphaComponent(0.05))
        paragraph(card.0.uppercased(), font: .systemFont(ofSize: 20, weight: .black), color: gold, rect: CGRect(x: cardX + 42, y: cardY + 38, width: cardWidth - 84, height: 30))
        paragraph(card.1, font: .systemFont(ofSize: 36, weight: .bold), color: .black, rect: CGRect(x: cardX + 42, y: cardY + 82, width: cardWidth - 84, height: 92), lineHeight: 42)
        paragraph(card.2, font: .systemFont(ofSize: 25, weight: .regular), color: NSColor.darkGray, rect: CGRect(x: cardX + 42, y: cardY + 176, width: cardWidth - 84, height: 88), lineHeight: 34)
    }
}

func drawScreenshot(screen: StoreScreen, format: ScreenshotFormat, output: URL) throws {
    let image = NSImage(size: NSSize(width: format.width, height: format.height))
    image.lockFocus()
    canvasHeight = CGFloat(format.height)
    NSGraphicsContext.current?.imageInterpolation = .high
    let background = NSGradient(colors: [deepGreen, green, NSColor(calibratedRed: 0.16, green: 0.34, blue: 0.23, alpha: 1)])!
    background.draw(in: CGRect(x: 0, y: 0, width: format.width, height: format.height), angle: -35)
    circle(CGRect(x: CGFloat(format.width) - 460, y: -170, width: 650, height: 650), fill: gold.withAlphaComponent(0.14))
    circle(CGRect(x: -210, y: CGFloat(format.height) - 1068, width: 540, height: 540), fill: cream.withAlphaComponent(0.08))
    paragraph(format.deviceTitle, font: .systemFont(ofSize: 74, weight: .black), color: cream, rect: CGRect(x: 96, y: 96, width: 920, height: 94))
    paragraph("Faith · Growth · Peace · Purpose", font: .systemFont(ofSize: 28, weight: .bold), color: gold, rect: CGRect(x: 100, y: 180, width: 760, height: 38))
    drawDeviceChrome(format: format, activeTab: screen.tab)
    drawAppContent(screen, format: format)
    image.unlockFocus()
    try writePNG(image, to: output.appendingPathComponent(screen.file))
}

for format in formats {
    let output = root.appendingPathComponent("AppStoreAssets/Screenshots/\(format.folder)")
    try FileManager.default.createDirectory(at: output, withIntermediateDirectories: true)
    for screen in screens {
        try drawScreenshot(screen: screen, format: format, output: output)
    }
    print("Generated \(screens.count) screenshots in \(output.path)")
}
