import SwiftUI

struct HoroscopeView: View {
    @AppStorage("selectedZodiacSign") private var selectedSign = ZodiacSign.virgo.rawValue
    @State private var remoteReadings: [String: RemoteDailyHoroscope] = [:]
    @State private var readingDate = ""

    private var sign: ZodiacSign { ZodiacSign(rawValue: selectedSign) ?? .virgo }
    private var remoteReading: RemoteDailyHoroscope? { remoteReadings[sign.rawValue.lowercased()] }
    private var reading: String { remoteReading?.paragraphs.joined(separator: " ") ?? SampleContent.readings[sign] ?? "Give yourself room to notice what matters today." }
    private var mood: String {
        let index = ZodiacSign.allCases.firstIndex(of: sign) ?? 0
        return remoteReading?.mood ?? SampleContent.moods[index % SampleContent.moods.count]
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

                            if !readingDate.isEmpty {
                                Text("Daily Astrology · \(readingDate)")
                                    .font(.footnote)
                                    .foregroundStyle(SpaceTheme.secondaryText)
                            }

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
        .task { await loadDailyReadings() }
    }

    private func loadDailyReadings() async {
        guard let response = try? await DailySpaceService.horoscopes() else { return }
        remoteReadings = Dictionary(uniqueKeysWithValues: response.items.map { ($0.sign.lowercased(), $0) })
        readingDate = response.date
    }
}
