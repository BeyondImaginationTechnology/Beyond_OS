import SwiftUI

struct HoroscopeView: View {
    @AppStorage("selectedZodiacSign") private var selectedSign = ZodiacSign.virgo.rawValue

    private var sign: ZodiacSign { ZodiacSign(rawValue: selectedSign) ?? .virgo }
    private var reading: String { SampleContent.readings[sign] ?? "Give yourself room to notice what matters today." }
    private var mood: String {
        let index = ZodiacSign.allCases.firstIndex(of: sign) ?? 0
        return SampleContent.moods[index % SampleContent.moods.count]
    }

    var body: some View {
        ZStack {
            SpaceBackground()
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    Picker("Zodiac sign", selection: $selectedSign) {
                        ForEach(ZodiacSign.allCases) { sign in
                            Text("\(sign.symbol) \(sign.rawValue)").tag(sign.rawValue)
                        }
                    }
                    .pickerStyle(.menu)
                    .frame(minHeight: 44)

                    SpaceCard {
                        VStack(alignment: .leading, spacing: 16) {
                            HStack(alignment: .firstTextBaseline) {
                                Text(sign.symbol).font(.largeTitle)
                                    .accessibilityHidden(true)
                                VStack(alignment: .leading) {
                                    Text(sign.rawValue).font(.title.bold())
                                    Text(sign.dates).foregroundStyle(SpaceTheme.secondaryText)
                                }
                            }
                            .accessibilityElement(children: .combine)
                            .accessibilityAddTraits(.isHeader)

                            Text(reading)
                                .font(.title3)
                                .fixedSize(horizontal: false, vertical: true)

                            Divider().overlay(Color.white.opacity(0.28))

                            Label("Mood: \(mood)", systemImage: "heart.fill")
                                .font(.headline)
                                .foregroundStyle(SpaceTheme.ink)
                                .padding(.horizontal, 16)
                                .frame(minHeight: 44)
                                .background(SpaceTheme.cyan, in: Capsule())
                                .fixedSize(horizontal: false, vertical: true)
                        }
                    }
                    Text("For reflection and entertainment—not professional advice.")
                        .font(.footnote)
                        .foregroundStyle(SpaceTheme.secondaryText)
                }
                .padding()
            }
        }
        .navigationTitle("Daily Horoscope")
    }
}
