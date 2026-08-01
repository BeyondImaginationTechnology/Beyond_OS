import SwiftUI
import CoreLocation

struct StudiosView: View {
    @EnvironmentObject private var store: TattooStore
    @StateObject private var locationProvider = StudioLocationProvider()

    var body: some View {
        TattooScreen(title: "Studios") {
            VStack(alignment: .leading, spacing: 10) {
                SectionTitle(text: "Nearest 10")
                Text(locationProvider.statusTitle)
                    .font(.title2.weight(.bold))
                    .foregroundStyle(Color.tattooInk)
                Text(locationProvider.statusMessage)
                    .foregroundStyle(.secondary)

                Button {
                    locationProvider.requestLocation()
                } label: {
                    Label(locationProvider.buttonTitle, systemImage: "location.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)

                Link(destination: WebDestination.studios.url) {
                    Label("Open studio directory", systemImage: "arrow.up.forward.app.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)
            }
            .padding()
            .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))

            ForEach(Array(store.nearestStudios.enumerated()), id: \.element.id) { index, studio in
                StudioCard(
                    rank: index + 1,
                    studio: studio,
                    distanceMiles: studio.distanceMiles(from: store.userLocation)
                )
            }
        }
        .onReceive(locationProvider.$location) { location in
            store.userLocation = location
        }
    }
}

private struct StudioCard: View {
    let rank: Int
    let studio: StudioLead
    let distanceMiles: Double?

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Text("\(rank)")
                    .font(.headline.weight(.black))
                    .frame(width: 34, height: 34)
                    .background(Color.tattooGold.opacity(0.18), in: Circle())
                    .foregroundStyle(Color.tattooGold)
                VStack(alignment: .leading, spacing: 4) {
                    Text(studio.name)
                        .font(.headline.weight(.bold))
                        .foregroundStyle(Color.tattooInk)
                    Text("\(studio.city), \(studio.region)")
                        .foregroundStyle(.secondary)
                }
                Spacer()
                if studio.isVerified {
                    Image(systemName: "checkmark.seal.fill")
                        .foregroundStyle(.green)
                }
            }
            Text(studio.address)
                .font(.caption)
                .foregroundStyle(.secondary)

            HStack(spacing: 8) {
                ForEach(studio.specialties, id: \.self) { specialty in
                    Text(specialty)
                        .font(.caption.weight(.semibold))
                        .padding(.horizontal, 8)
                        .padding(.vertical, 5)
                        .background(Color.tattooViolet.opacity(0.15), in: Capsule())
                }
            }

            HStack {
                Label(distanceText, systemImage: distanceMiles == nil ? "location.slash.fill" : "location.fill")
                Spacer()
                Label(studio.acceptsWalkIns ? "Walk-ins" : "Booking", systemImage: studio.acceptsWalkIns ? "figure.walk" : "calendar")
                Spacer()
                Label(studio.responseTime, systemImage: "clock.fill")
            }
            .font(.caption.weight(.semibold))
            .foregroundStyle(Color.tattooGold)

            Link(destination: studio.profileURL) {
                Label("View profile", systemImage: "person.text.rectangle.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.bordered)
        }
        .padding()
        .background(Color.tattooPanel, in: RoundedRectangle(cornerRadius: 8))
    }

    private var distanceText: String {
        guard let distanceMiles else { return "Enable location" }
        return "\(distanceMiles.formatted(.number.precision(.fractionLength(1)))) mi"
    }
}

@MainActor
final class StudioLocationProvider: NSObject, ObservableObject, CLLocationManagerDelegate {
    @Published var location: CLLocation?
    @Published private var authorizationStatus: CLAuthorizationStatus = .notDetermined

    private let manager = CLLocationManager()

    override init() {
        super.init()
        manager.delegate = self
        manager.desiredAccuracy = kCLLocationAccuracyKilometer
        authorizationStatus = manager.authorizationStatus
    }

    var statusTitle: String {
        switch authorizationStatus {
        case .authorizedAlways, .authorizedWhenInUse:
            location == nil ? "Finding nearby studios" : "Studios closest to you"
        case .denied, .restricted:
            "Studio directory"
        case .notDetermined:
            "Find artists ready for stencil packs"
        @unknown default:
            "Studio directory"
        }
    }

    var statusMessage: String {
        switch authorizationStatus {
        case .authorizedAlways, .authorizedWhenInUse:
            location == nil ? "Location is on. Waiting for the first studio ranking." : "Sorted by distance from your current location."
        case .denied, .restricted:
            "Location is off, so studios are sorted alphabetically."
        case .notDetermined:
            "Use location to rank the nearest 10 studios, or browse the full web directory."
        @unknown default:
            "Browse the studio directory while location is unavailable."
        }
    }

    var buttonTitle: String {
        switch authorizationStatus {
        case .authorizedAlways, .authorizedWhenInUse:
            "Refresh nearby studios"
        case .denied, .restricted:
            "Location unavailable"
        case .notDetermined:
            "Use my location"
        @unknown default:
            "Use my location"
        }
    }

    func requestLocation() {
        switch authorizationStatus {
        case .notDetermined:
            manager.requestWhenInUseAuthorization()
        case .authorizedAlways, .authorizedWhenInUse:
            manager.requestLocation()
        case .denied, .restricted:
            break
        @unknown default:
            break
        }
    }

    nonisolated func locationManagerDidChangeAuthorization(_ manager: CLLocationManager) {
        let status = manager.authorizationStatus
        Task { @MainActor [weak self] in
            guard let self else { return }
            authorizationStatus = status
            if status == .authorizedWhenInUse || status == .authorizedAlways {
                self.manager.requestLocation()
            }
        }
    }

    nonisolated func locationManager(_ manager: CLLocationManager, didUpdateLocations locations: [CLLocation]) {
        let latestLocation = locations.last
        Task { @MainActor in
            location = latestLocation
        }
    }

    nonisolated func locationManager(_ manager: CLLocationManager, didFailWithError error: Error) {}
}
