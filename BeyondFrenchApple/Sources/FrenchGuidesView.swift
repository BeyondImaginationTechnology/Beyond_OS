import SwiftUI

enum FrenchGuide: String, CaseIterable, Identifiable {
    case louis, irie, jazzy, pablo

    var id: String { rawValue }
    var name: String { rawValue.capitalized }

    var voice: String {
        switch self {
        case .louis: "French"
        case .irie: "Jamaican Patois"
        case .jazzy: "Haitian Kreyòl"
        case .pablo: "Spanish"
        }
    }

    var image: String { "Guide\(name)" }

    var audioLocale: String {
        switch self {
        case .louis: "fr-FR"
        case .irie: "en-JM"
        case .jazzy: "ht-HT"
        case .pablo: "es-ES"
        }
    }

    var prompt: String {
        switch self {
        case .louis: "Louis models the French pronunciation. Listen, then repeat it aloud."
        case .irie: "Irie connects the idea through Jamaican Patois. Compare it, then say the French phrase."
        case .jazzy: "Jazzy connects the idea through Haitian Kreyòl. Listen for familiar sounds, then speak French."
        case .pablo: "Pablo connects the idea through Spanish. Notice what changes, then answer in French."
        }
    }

    func bridge(in phrase: BeyondPhrase) -> String {
        switch self {
        case .louis: phrase.french
        case .irie: phrase.patois
        case .jazzy: phrase.kreyol
        case .pablo: phrase.spanish
        }
    }
}

struct FrenchGuidesView: View {
    var selectedGuide: FrenchGuide?
    var onSelect: ((FrenchGuide) -> Void)?

    var body: some View {
        LazyVGrid(columns: Array(repeating: GridItem(.flexible(), spacing: 8), count: 4), spacing: 8) {
            ForEach(FrenchGuide.allCases) { guide in
                if let onSelect {
                    Button { onSelect(guide) } label: { guideCard(guide) }
                        .buttonStyle(.plain)
                        .accessibilityAddTraits(selectedGuide == guide ? .isSelected : [])
                } else {
                    guideCard(guide)
                }
            }
        }
    }

    private func guideCard(_ guide: FrenchGuide) -> some View {
        VStack(spacing: 5) {
            Image(guide.image)
                .resizable()
                .scaledToFill()
                .frame(height: 74)
                .frame(maxWidth: .infinity)
                .clipped()
                .clipShape(RoundedRectangle(cornerRadius: 12))
                .accessibilityHidden(true)
            Text(guide.name)
                .font(.caption.weight(.bold))
                .lineLimit(1)
            Text(guide.voice)
                .font(.system(size: 9, weight: .medium))
                .foregroundStyle(.secondary)
                .lineLimit(2)
                .multilineTextAlignment(.center)
        }
        .frame(maxWidth: .infinity, alignment: .top)
        .padding(3)
        .background(selectedGuide == guide ? Color.white.opacity(0.12) : Color.clear, in: RoundedRectangle(cornerRadius: 14))
        .overlay(RoundedRectangle(cornerRadius: 14).stroke(selectedGuide == guide ? Color.yellow : .clear, lineWidth: 2))
        .contentShape(Rectangle())
        .accessibilityElement(children: .ignore)
        .accessibilityLabel("\(guide.name), \(guide.voice) guide")
    }
}
