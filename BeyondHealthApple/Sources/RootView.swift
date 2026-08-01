import SwiftUI

struct RootView: View {
    var body: some View {
        TabView {
            NavigationStack { TodayView() }
                .tabItem { Label("Today", systemImage: "sun.max.fill") }
            NavigationStack { HealthCalendarView() }
                .tabItem { Label("Calendar", systemImage: "calendar") }
            NavigationStack { AddLogView() }
                .tabItem { Label("Add", systemImage: "plus.circle.fill") }
            NavigationStack { InsightsView() }
                .tabItem { Label("Insights", systemImage: "chart.xyaxis.line") }
            NavigationStack { FamilyView() }
                .tabItem { Label("Family", systemImage: "person.2.fill") }
        }
        .tint(Color.healthTeal)
    }
}

struct HealthScreen<Content: View>: View {
    let title: String
    @ViewBuilder var content: Content

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                BrandHeader()
                content
            }
            .padding()
        }
        .background(Color.healthBackground.ignoresSafeArea())
        .navigationTitle(title)
    }
}

struct BrandHeader: View {
    var body: some View {
        HStack(spacing: 12) {
            ZStack {
                RoundedRectangle(cornerRadius: 8)
                    .fill(.linearGradient(colors: [Color.healthTeal, Color.healthGold], startPoint: .topLeading, endPoint: .bottomTrailing))
                Image(systemName: "heart.text.square.fill")
                    .font(.title2.weight(.bold))
                    .foregroundStyle(.white)
            }
            .frame(width: 52, height: 52)

            VStack(alignment: .leading, spacing: 2) {
                Text("BEYOND HEALTH")
                    .font(.headline.weight(.black))
                    .foregroundStyle(.white)
                Text("Body and family health log")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            Spacer()
        }
    }
}

struct HealthPanel<Content: View>: View {
    @ViewBuilder var content: Content

    var body: some View {
        VStack(alignment: .leading, spacing: 14) {
            content
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.healthPanel, in: RoundedRectangle(cornerRadius: 8))
    }
}

struct HealthEyebrow: View {
    let text: String

    var body: some View {
        Text(text.uppercased())
            .font(.caption.weight(.black))
            .foregroundStyle(Color.healthGold)
    }
}

struct FamilySwitcher: View {
    @EnvironmentObject private var store: HealthStore

    var body: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 10) {
                ForEach(store.members) { member in
                    Button {
                        store.selectedMemberID = member.id
                    } label: {
                        HStack(spacing: 8) {
                            Circle()
                                .fill(member.accent.color)
                                .frame(width: 10, height: 10)
                            VStack(alignment: .leading, spacing: 1) {
                                Text(member.name)
                                    .font(.subheadline.weight(.bold))
                                Text(member.relationship)
                                    .font(.caption2)
                            }
                        }
                        .foregroundStyle(.white)
                        .padding(.horizontal, 12)
                        .padding(.vertical, 10)
                        .background(store.selectedMemberID == member.id ? Color.healthPanelSoft : Color.healthPanel, in: RoundedRectangle(cornerRadius: 8))
                    }
                    .buttonStyle(.plain)
                }
            }
        }
    }
}

struct CategoryPill: View {
    let category: HealthCategory

    var body: some View {
        Label(category.rawValue, systemImage: category.systemImage)
            .font(.caption.weight(.bold))
            .foregroundStyle(category.color)
            .padding(.horizontal, 10)
            .padding(.vertical, 6)
            .background(category.color.opacity(0.16), in: Capsule())
    }
}

struct EmptyState: View {
    let title: String
    let systemImage: String

    var body: some View {
        VStack(spacing: 8) {
            Image(systemName: systemImage)
                .font(.title2)
                .foregroundStyle(Color.healthTeal)
            Text(title)
                .font(.subheadline.weight(.semibold))
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 24)
    }
}

extension HealthAccent {
    var color: Color {
        switch self {
        case .teal: Color.healthTeal
        case .gold: Color.healthGold
        case .rose: Color.healthRose
        case .sky: Color.healthSky
        }
    }
}

extension HealthCategory {
    var color: Color {
        switch self {
        case .body: Color.healthRose
        case .food: Color.healthLeaf
        case .sleep: Color.healthSky
        case .medication: Color.healthGold
        case .smoke: Color.healthSmoke
        case .workout: Color.healthTeal
        case .hygiene: Color.healthLavender
        }
    }
}

extension Date {
    var shortDayText: String {
        let formatter = DateFormatter()
        formatter.dateFormat = "EEE"
        return formatter.string(from: self)
    }

    var dayNumberText: String {
        let formatter = DateFormatter()
        formatter.dateFormat = "d"
        return formatter.string(from: self)
    }

    var timeText: String {
        let formatter = DateFormatter()
        formatter.timeStyle = .short
        formatter.dateStyle = .none
        return formatter.string(from: self)
    }

    var fullDayText: String {
        let formatter = DateFormatter()
        formatter.dateStyle = .full
        formatter.timeStyle = .none
        return formatter.string(from: self)
    }
}

extension Color {
    static let healthBackground = Color(red: 0.05, green: 0.07, blue: 0.07)
    static let healthPanel = Color(red: 0.10, green: 0.13, blue: 0.13)
    static let healthPanelSoft = Color(red: 0.16, green: 0.21, blue: 0.20)
    static let healthTeal = Color(red: 0.16, green: 0.76, blue: 0.68)
    static let healthGold = Color(red: 0.95, green: 0.70, blue: 0.28)
    static let healthRose = Color(red: 0.94, green: 0.36, blue: 0.48)
    static let healthSky = Color(red: 0.36, green: 0.66, blue: 0.96)
    static let healthLeaf = Color(red: 0.44, green: 0.78, blue: 0.35)
    static let healthSmoke = Color(red: 0.68, green: 0.68, blue: 0.72)
    static let healthLavender = Color(red: 0.66, green: 0.54, blue: 0.96)
}
