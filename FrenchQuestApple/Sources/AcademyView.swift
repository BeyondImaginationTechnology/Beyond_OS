import SwiftUI
import UIKit

struct WorldTourMapView: View {
    @EnvironmentObject private var store: QuestStore
    let onMenu: () -> Void

    private let sourceAspect = 941.0 / 1672.0
    private let stops: [String: CGPoint] = [
        "port-au-prince": CGPoint(x: 0.56, y: 0.86),
        "haiti-highlands": CGPoint(x: 0.53, y: 0.59),
        "morocco": CGPoint(x: 0.55, y: 0.40),
        "montreal": CGPoint(x: 0.43, y: 0.25),
        "france": CGPoint(x: 0.56, y: 0.14)
    ]

    var body: some View {
        GeometryReader { geometry in
            let mapWidth = min(geometry.size.width, geometry.size.height * sourceAspect)
            let mapHeight = mapWidth / sourceAspect
            let originX = (geometry.size.width - mapWidth) / 2
            let originY = (geometry.size.height - mapHeight) / 2

            ZStack {
                Color(red: 0.01, green: 0.10, blue: 0.18).ignoresSafeArea()

                Image("WorldTourMap")
                    .resizable()
                    .scaledToFill()
                    .frame(width: mapWidth, height: mapHeight)
                    .clipped()
                    .overlay(
                        LinearGradient(
                            colors: [.black.opacity(0.28), .clear, .black.opacity(0.18)],
                            startPoint: .top,
                            endPoint: .bottom
                        )
                    )
                    .position(x: geometry.size.width / 2, y: geometry.size.height / 2)

                ForEach(Array(store.regions.enumerated()), id: \.element.id) { index, region in
                    if let stop = stops[region.id] {
                        WorldStopButton(region: region, number: index + 1)
                            .position(
                                x: originX + mapWidth * stop.x,
                                y: originY + mapHeight * stop.y
                            )
                    }
                }

                VStack {
                    HStack(spacing: 10) {
                        Button(action: onMenu) {
                            Image(systemName: "house.fill")
                                .font(.headline.weight(.black))
                                .frame(width: 44, height: 44)
                                .background(.ultraThinMaterial, in: Circle())
                        }
                        .buttonStyle(.plain)

                        VStack(alignment: .leading, spacing: 0) {
                            Text("WORLD TOUR").font(.headline.weight(.black))
                            Text("Choose your next destination")
                                .font(.caption2.weight(.bold))
                                .foregroundStyle(.white.opacity(0.72))
                        }

                        Spacer()

                        Label("\(store.xp)", systemImage: "sparkles")
                            .font(.subheadline.weight(.black))
                            .padding(.horizontal, 12)
                            .frame(height: 40)
                            .background(.ultraThinMaterial, in: Capsule())
                    }
                    .padding(.horizontal, 16)
                    .padding(.top, 8)

                    Spacer()
                }
                .foregroundStyle(.white)
            }
        }
        .toolbar(.hidden, for: .navigationBar)
    }
}

private struct WorldStopButton: View {
    @EnvironmentObject private var store: QuestStore
    let region: QuestRegion
    let number: Int
    @State private var pulsing = false

    private var unlocked: Bool { store.isRegionUnlocked(region) }
    private var complete: Bool { store.completedCount(in: region) == region.lessonCount }

    var body: some View {
        NavigationLink(value: QuestRoute.destination(region.id)) {
            VStack(spacing: 5) {
                ZStack {
                    if unlocked && !complete {
                        Circle()
                            .stroke(region.color.opacity(0.75), lineWidth: 4)
                            .frame(width: 72, height: 72)
                            .scaleEffect(pulsing ? 1.25 : 0.92)
                            .opacity(pulsing ? 0 : 0.85)
                    }

                    Circle()
                        .fill(
                            LinearGradient(
                                colors: unlocked ? [region.color, region.color.opacity(0.62)] : [.gray, .black.opacity(0.72)],
                                startPoint: .topLeading,
                                endPoint: .bottomTrailing
                            )
                        )
                        .frame(width: 58, height: 58)
                        .overlay(Circle().stroke(.white.opacity(0.88), lineWidth: 3))
                        .shadow(color: .black.opacity(0.45), radius: 9, y: 5)

                    Image(systemName: complete ? "checkmark" : unlocked ? region.icon : "lock.fill")
                        .font(.title3.weight(.black))
                        .foregroundStyle(.white)
                }

                Text(region.title)
                    .font(.caption2.weight(.black))
                    .lineLimit(1)
                    .padding(.horizontal, 9)
                    .padding(.vertical, 5)
                    .background(.black.opacity(0.68), in: Capsule())
                    .overlay(Capsule().stroke(.white.opacity(0.22), lineWidth: 1))
            }
        }
        .buttonStyle(.plain)
        .disabled(!unlocked)
        .opacity(unlocked ? 1 : 0.72)
        .onAppear {
            guard unlocked && !complete else { return }
            withAnimation(.easeOut(duration: 1.35).repeatForever(autoreverses: false)) {
                pulsing = true
            }
        }
        .accessibilityLabel("Stop \(number), \(region.title), \(unlocked ? "unlocked" : "locked")")
    }
}

struct DestinationAdventureView: View {
    @EnvironmentObject private var store: QuestStore
    @Environment(\.dismiss) private var dismiss
    let region: QuestRegion

    private var currentMission: QuestChallenge? {
        region.challenges.first {
            !store.completedChallengeIDs.contains($0.id) && store.isChallengeUnlocked($0, in: region)
        }
    }

    var body: some View {
        ZStack {
            store.theme.background.ignoresSafeArea()
            DestinationBackdrop(region: region)

            ScrollView {
                VStack(alignment: .leading, spacing: 18) {
                    Text("DESTINATION \(store.regions.firstIndex(where: { $0.id == region.id }).map { $0 + 1 } ?? 1)")
                        .font(.caption.weight(.black))
                        .tracking(2)
                        .foregroundStyle(region.color)
                    Text(region.title)
                        .font(.system(size: 42, weight: .black, design: .rounded))
                    Text(region.subtitle)
                        .font(.headline)
                        .foregroundStyle(.white.opacity(0.72))

                    HStack(spacing: 12) {
                        ForEach(Array(region.challenges.enumerated()), id: \.element.id) { index, mission in
                            VStack(spacing: 5) {
                                ZStack {
                                    Circle()
                                        .fill(store.completedChallengeIDs.contains(mission.id) ? Color.green : region.color.opacity(store.isChallengeUnlocked(mission, in: region) ? 0.9 : 0.2))
                                        .frame(width: 44, height: 44)
                                    Image(systemName: store.completedChallengeIDs.contains(mission.id) ? "checkmark" : store.isChallengeUnlocked(mission, in: region) ? "flag.fill" : "lock.fill")
                                        .font(.caption.weight(.black))
                                }
                                Text("\(index + 1)").font(.caption2.weight(.black))
                            }
                            if index < region.challenges.count - 1 {
                                Rectangle()
                                    .fill(region.color.opacity(0.42))
                                    .frame(height: 3)
                            }
                        }
                    }
                    .padding(.vertical, 8)

                    if let currentMission {
                        Text("MISSION \(region.challenges.firstIndex(of: currentMission).map { $0 + 1 } ?? 1)")
                            .font(.caption.weight(.black))
                            .tracking(1.8)
                            .foregroundStyle(region.color)
                        ChallengePlayer(region: region, challenge: currentMission)
                            .id(currentMission.id)
                    } else {
                        QuestCard {
                            VStack(spacing: 14) {
                                Image(systemName: "trophy.fill")
                                    .font(.system(size: 54))
                                    .foregroundStyle(.yellow)
                                Text("DESTINATION CLEARED")
                                    .font(.title2.weight(.black))
                                Text("The next stop on the world tour is now open.")
                                    .multilineTextAlignment(.center)
                                    .foregroundStyle(.secondary)
                                Button {
                                    dismiss()
                                } label: {
                                    Label("Continue World Tour", systemImage: "map.fill")
                                        .font(.headline.weight(.black))
                                        .frame(maxWidth: .infinity)
                                        .padding(.vertical, 14)
                                        .background(region.color, in: Capsule())
                                }
                                .buttonStyle(.plain)
                            }
                            .frame(maxWidth: .infinity)
                        }
                        .onAppear {
                            store.speakDestinationCleared()
                        }
                    }
                }
                .padding(22)
            }
        }
        .foregroundStyle(.white)
        .navigationTitle(region.title)
        .navigationBarTitleDisplayMode(.inline)
    }
}

private struct DestinationBackdrop: View {
    let region: QuestRegion
    @State private var drifting = false

    var body: some View {
        ZStack {
            Image("WorldTourMap")
                .resizable()
                .scaledToFill()
                .blur(radius: 18)
                .opacity(0.16)

            if UIImage(named: "Destination-\(region.id)") != nil {
                Image("Destination-\(region.id)")
                    .resizable()
                    .scaledToFill()
                    .scaleEffect(drifting ? 1.12 : 1.04)
                    .offset(x: drifting ? -12 : 12, y: drifting ? 8 : -8)
                    .opacity(0.42)
                    .overlay(Color.black.opacity(0.38))
            }
        }
        .ignoresSafeArea()
        .clipped()
        .onAppear {
            withAnimation(.easeInOut(duration: 12).repeatForever(autoreverses: true)) {
                drifting = true
            }
        }
        .accessibilityHidden(true)
    }
}

struct AcademyView: View {
    var body: some View {
        WorldTourMapView(onMenu: {})
    }
}
