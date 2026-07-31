import AppKit
import CoreGraphics
import Foundation

let root = URL(fileURLWithPath: FileManager.default.currentDirectoryPath)
let output = root.appendingPathComponent("AppStoreAssets/Screenshots/iPhone-6.5")
try FileManager.default.createDirectory(at: output, withIntermediateDirectories: true)

let width = 1242
let height = 2688
let scale: CGFloat = 1
let green = NSColor(calibratedRed: 0.09, green: 0.25, blue: 0.17, alpha: 1)
let deepGreen = NSColor(calibratedRed: 0.03, green: 0.12, blue: 0.08, alpha: 1)
let gold = NSColor(calibratedRed: 0.82, green: 0.64, blue: 0.30, alpha: 1)
let cream = NSColor(calibratedRed: 0.96, green: 0.92, blue: 0.84, alpha: 1)
let muted = NSColor(calibratedRed: 0.69, green: 0.74, blue: 0.69, alpha: 1)

struct StoreScreen {
    let file: String
    let eyebrow: String
    let title: String
    let subtitle: String
    let tab: String
    let cards: [(String, String, String)]
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
        eyebrow: "BIBLE LIBRARY",
        title: "Scripture close at hand.",
        subtitle: "Continue reading, listen to verses, and keep your daily place.",
        tab: "Bible",
        cards: [
            ("Continue Reading", "Psalm 46", "God is our refuge and strength, a very present help in trouble."),
            ("Books", "Genesis · Psalms · Proverbs", "A clean starting library designed for everyday reflection."),
            ("Listen", "Chapter narration", "Built for quiet moments, commuting, prayer, and rest.")
        ]
    ),
    StoreScreen(
        file: "03-bible-academy.png",
        eyebrow: "BIBLE ACADEMY",
        title: "Grow with guided lessons.",
        subtitle: "Foundations, Gospel study, wisdom books, and saved progress.",
        tab: "Academy",
        cards: [
            ("Free Starter", "Foundations of Faith", "Prayer, Scripture, reflection, and daily practice."),
            ("Guided Path", "Life of Jesus", "A clear learning route through the Gospels."),
            ("Progress", "42% complete", "See where you are and return without losing momentum.")
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
    NSString(string: text).draw(in: rect, withAttributes: attrs)
}

func roundedRect(_ rect: CGRect, radius: CGFloat, fill: NSColor, stroke: NSColor? = nil, lineWidth: CGFloat = 1) {
    let path = NSBezierPath(roundedRect: rect, xRadius: radius, yRadius: radius)
    fill.setFill()
    path.fill()
    if let stroke {
        stroke.setStroke()
        path.lineWidth = lineWidth
        path.stroke()
    }
}

func circle(_ rect: CGRect, fill: NSColor, stroke: NSColor? = nil, lineWidth: CGFloat = 1) {
    let path = NSBezierPath(ovalIn: rect)
    fill.setFill()
    path.fill()
    if let stroke {
        stroke.setStroke()
        path.lineWidth = lineWidth
        path.stroke()
    }
}

func writePNG(_ image: NSImage, to url: URL) throws {
    guard let tiff = image.tiffRepresentation,
          let rep = NSBitmapImageRep(data: tiff),
          let data = rep.representation(using: .png, properties: [:]) else {
        throw NSError(domain: "DailyBreathScreenshots", code: 1)
    }
    try data.write(to: url)
}

func drawPhoneChrome(activeTab: String) {
    roundedRect(CGRect(x: 96, y: 206, width: 1050, height: 2170), radius: 76, fill: NSColor.black.withAlphaComponent(0.82), stroke: NSColor.white.withAlphaComponent(0.22), lineWidth: 4)
    roundedRect(CGRect(x: 122, y: 238, width: 998, height: 2110), radius: 56, fill: NSColor(calibratedRed: 0.95, green: 0.96, blue: 0.94, alpha: 1))
    roundedRect(CGRect(x: 458, y: 270, width: 326, height: 42), radius: 21, fill: NSColor.black.withAlphaComponent(0.88))
    paragraph("9:41", font: .systemFont(ofSize: 30, weight: .bold), color: .black, rect: CGRect(x: 176, y: 272, width: 120, height: 42))

    let tabs = ["Today", "Bible", "Academy", "Breathe", "Journal"]
    let tabY: CGFloat = 2206
    roundedRect(CGRect(x: 162, y: tabY, width: 918, height: 92), radius: 30, fill: green)
    for (index, tab) in tabs.enumerated() {
        let x = 186 + CGFloat(index) * 176
        let selected = tab == activeTab
        circle(CGRect(x: x + 58, y: tabY + 12, width: 42, height: 42), fill: selected ? gold : NSColor.white.withAlphaComponent(0.14))
        paragraph(tab, font: .systemFont(ofSize: 20, weight: selected ? .bold : .medium), color: selected ? gold : cream, rect: CGRect(x: x, y: tabY + 56, width: 160, height: 28), alignment: .center)
    }
}

func drawAppContent(_ screen: StoreScreen) {
    paragraph("DAILYBREATH", font: .systemFont(ofSize: 28, weight: .black), color: green, rect: CGRect(x: 178, y: 356, width: 300, height: 40))
    paragraph("Faith-centered wellness", font: .systemFont(ofSize: 22, weight: .medium), color: muted, rect: CGRect(x: 178, y: 394, width: 340, height: 32))
    roundedRect(CGRect(x: 930, y: 356, width: 96, height: 42), radius: 21, fill: green.withAlphaComponent(0.12))
    paragraph("1.0", font: .systemFont(ofSize: 20, weight: .bold), color: green, rect: CGRect(x: 930, y: 366, width: 96, height: 24), alignment: .center)

    roundedRect(CGRect(x: 174, y: 470, width: 894, height: 650), radius: 34, fill: green)
    paragraph(screen.eyebrow, font: .systemFont(ofSize: 24, weight: .black), color: gold, rect: CGRect(x: 222, y: 530, width: 520, height: 34))
    paragraph(screen.title, font: .systemFont(ofSize: 66, weight: .black), color: .white, rect: CGRect(x: 222, y: 602, width: 760, height: 160), lineHeight: 74)
    paragraph(screen.subtitle, font: .systemFont(ofSize: 31, weight: .medium), color: cream, rect: CGRect(x: 222, y: 792, width: 720, height: 92), lineHeight: 42)
    roundedRect(CGRect(x: 222, y: 952, width: 322, height: 74), radius: 37, fill: gold)
    paragraph("Open \(screen.tab)", font: .systemFont(ofSize: 26, weight: .black), color: deepGreen, rect: CGRect(x: 222, y: 972, width: 322, height: 34), alignment: .center)

    var y: CGFloat = 1162
    for (label, title, subtitle) in screen.cards {
        roundedRect(CGRect(x: 174, y: y, width: 894, height: 256), radius: 28, fill: .white, stroke: NSColor.black.withAlphaComponent(0.05))
        paragraph(label.uppercased(), font: .systemFont(ofSize: 20, weight: .black), color: gold, rect: CGRect(x: 222, y: y + 38, width: 620, height: 30))
        paragraph(title, font: .systemFont(ofSize: 36, weight: .bold), color: .black, rect: CGRect(x: 222, y: y + 80, width: 730, height: 50))
        paragraph(subtitle, font: .systemFont(ofSize: 25, weight: .regular), color: NSColor.darkGray, rect: CGRect(x: 222, y: y + 140, width: 735, height: 74), lineHeight: 34)
        y += 286
    }
}

for screen in screens {
    let image = NSImage(size: NSSize(width: width, height: height))
    image.lockFocus()
    NSGraphicsContext.current?.imageInterpolation = .high
    let background = NSGradient(colors: [deepGreen, green, NSColor(calibratedRed: 0.16, green: 0.34, blue: 0.23, alpha: 1)])!
    background.draw(in: CGRect(x: 0, y: 0, width: width, height: height), angle: -35)
    circle(CGRect(x: 780, y: -170, width: 650, height: 650), fill: gold.withAlphaComponent(0.14))
    circle(CGRect(x: -210, y: 1620, width: 540, height: 540), fill: cream.withAlphaComponent(0.08))
    paragraph("DailyBreath", font: .systemFont(ofSize: 74, weight: .black), color: cream, rect: CGRect(x: 96, y: 96, width: 640, height: 94))
    paragraph("Faith · Growth · Peace · Purpose", font: .systemFont(ofSize: 28, weight: .bold), color: gold, rect: CGRect(x: 100, y: 180, width: 760, height: 38))
    drawPhoneChrome(activeTab: screen.tab)
    drawAppContent(screen)
    image.unlockFocus()
    try writePNG(image, to: output.appendingPathComponent(screen.file))
}

print("Generated \(screens.count) screenshots in \(output.path)")
