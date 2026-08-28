import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

private enum Brand {
    static let blue = Color(red: 0.10, green: 0.54, blue: 1.00)
    static let purple = Color(red: 0.46, green: 0.22, blue: 0.94)
    static let pink = Color(red: 0.97, green: 0.24, blue: 0.77)
    static let ink = Color(red: 0.07, green: 0.04, blue: 0.14)
    static let gradient = LinearGradient(colors: [blue, purple, pink], startPoint: .bottomLeading, endPoint: .topTrailing)
}

struct RootView: View {
    @AppStorage("beyond.baby.didOnboard") private var didOnboard = false

    var body: some View {
        TabView {
            DiscoverView()
                .tabItem { Label("Discover", systemImage: "sparkles") }
            SwipeView()
                .tabItem { Label("Swipe", systemImage: "rectangle.stack.fill") }
            FavoritesView()
                .tabItem { Label("Favorites", systemImage: "heart.fill") }
            CoupleView()
                .tabItem { Label("Together", systemImage: "person.2.fill") }
        }
        .tint(Brand.pink)
        .fullScreenCover(isPresented: Binding(get: { !didOnboard }, set: { if !$0 { didOnboard = true } })) {
            OnboardingView { didOnboard = true }
        }
    }
}

private struct OnboardingView: View {
    let finish: () -> Void
    @State private var page = 0

    private let pages: [(String, String, String)] = [
        ("Find the one", "Explore meaningful names from cultures around the world.", "sparkles"),
        ("Swipe together", "Love, maybe, or pass—then celebrate every shared match.", "rectangle.stack.fill"),
        ("Build the future", "Save favorites, discover twin pairs, and keep your shortlist private.", "heart.fill")
    ]

    var body: some View {
        ZStack {
            Brand.ink.ignoresSafeArea()
            Circle().fill(Brand.purple.opacity(0.38)).frame(width: 430).blur(radius: 60).offset(x: 150, y: -300)
            VStack(spacing: 26) {
                Spacer()
                Image(systemName: pages[page].2)
                    .font(.system(size: 62, weight: .semibold))
                    .foregroundStyle(.white)
                    .frame(width: 150, height: 150)
                    .background(Brand.gradient, in: RoundedRectangle(cornerRadius: 44, style: .continuous))
                    .shadow(color: Brand.pink.opacity(0.3), radius: 30, y: 18)
                VStack(spacing: 12) {
                    Text(pages[page].0).font(.system(size: 42, weight: .black, design: .rounded))
                    Text(pages[page].1).font(.title3).foregroundStyle(.white.opacity(0.72)).multilineTextAlignment(.center)
                }
                .padding(.horizontal, 28)
                HStack(spacing: 8) {
                    ForEach(pages.indices, id: \.self) { index in
                        Capsule().fill(index == page ? Brand.pink : .white.opacity(0.2)).frame(width: index == page ? 28 : 8, height: 8)
                    }
                }
                Spacer()
                Button {
                    if page == pages.count - 1 { finish() } else { withAnimation(.snappy) { page += 1 } }
                } label: {
                    Text(page == pages.count - 1 ? "Start discovering" : "Continue")
                        .fontWeight(.bold).frame(maxWidth: .infinity).padding(.vertical, 17)
                }
                .buttonStyle(.plain)
                .foregroundStyle(.white)
                .background(Brand.gradient, in: Capsule())
                .padding(.horizontal, 24)
                .padding(.bottom, 16)
            }
        }
    }
}

private struct DiscoverView: View {
    @EnvironmentObject private var store: BabyNameStore
    @State private var query = ""
    @State private var style: NameStyle?
    @State private var origin: String?
    @State private var selectedName: BabyName?
    @State private var showPreferences = false

    private var results: [BabyName] {
        NameLibrary.search(query, style: style, origin: origin, vibes: [])
    }

    private var recommended: [BabyName] {
        Array(NameLibrary.recommendations(vibes: store.preferredVibes, favorites: store.favorites).prefix(5))
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                LazyVStack(alignment: .leading, spacing: 22) {
                    hero
                    preferenceRow
                    if query.isEmpty && style == nil && origin == nil {
                        sectionTitle("Made for you", detail: "Based on your taste")
                        ScrollView(.horizontal, showsIndicators: false) {
                            LazyHStack(spacing: 14) {
                                ForEach(recommended) { item in
                                    RecommendationCard(name: item, isFavorite: store.favorites.contains(item.id)) {
                                        store.toggleFavorite(item)
                                    }
                                    .onTapGesture { selectedName = item }
                                }
                            }
                            .padding(.horizontal, 20)
                        }
                        .contentMargins(.horizontal, -20, for: .scrollContent)
                    }
                    sectionTitle("Explore names", detail: "\(results.count) results")
                    LazyVStack(spacing: 12) {
                        ForEach(results) { item in
                            NameRow(name: item, isFavorite: store.favorites.contains(item.id)) {
                                store.toggleFavorite(item)
                            }
                            .contentShape(Rectangle())
                            .onTapGesture { selectedName = item }
                        }
                    }
                }
                .padding(.horizontal, 20)
                .padding(.bottom, 28)
            }
            .background(Brand.ink)
            .navigationTitle("Beyond Baby")
            .searchable(text: $query, prompt: "Search name, meaning, or origin")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button { showPreferences = true } label: { Image(systemName: "slider.horizontal.3") }
                }
            }
            .sheet(item: $selectedName) { NameDetailView(name: $0) }
            .sheet(isPresented: $showPreferences) { PreferencesView() }
        }
    }

    private var hero: some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("YOUR DAILY DISCOVERY", systemImage: "wand.and.stars").font(.caption.bold()).tracking(1.4)
            Text(recommended.first?.name ?? "Luna").font(.system(size: 50, weight: .black, design: .rounded))
            Text(recommended.first?.meaning ?? "Moon").font(.title3.weight(.semibold))
            Text("A thoughtful name, surfaced just for you.").foregroundStyle(.white.opacity(0.72))
            Button { selectedName = recommended.first } label: {
                Label("See the story", systemImage: "arrow.up.right").fontWeight(.bold)
            }
            .buttonStyle(.borderedProminent).tint(.white).foregroundStyle(Brand.purple)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(24)
        .background(Brand.gradient, in: RoundedRectangle(cornerRadius: 30, style: .continuous))
        .overlay(alignment: .topTrailing) { Image(systemName: "sparkles").font(.system(size: 78)).opacity(0.18).padding(24) }
    }

    private var preferenceRow: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 9) {
                FilterChip(label: "All", selected: style == nil) { style = nil }
                ForEach(NameStyle.allCases) { item in FilterChip(label: item.rawValue, selected: style == item) { style = item } }
                Menu {
                    Button("Every origin") { origin = nil }
                    ForEach(NameLibrary.origins, id: \.self) { value in Button(value) { origin = value } }
                } label: {
                    Label(origin ?? "Origin", systemImage: "globe").font(.subheadline.bold()).padding(.horizontal, 15).padding(.vertical, 10)
                        .background(.white.opacity(0.08), in: Capsule()).overlay(Capsule().stroke(.white.opacity(0.12)))
                }
            }
        }
    }

    private func sectionTitle(_ title: String, detail: String) -> some View {
        HStack(alignment: .firstTextBaseline) {
            Text(title).font(.title2.bold())
            Spacer()
            Text(detail).font(.caption).foregroundStyle(.secondary)
        }
    }
}

private struct SwipeView: View {
    @EnvironmentObject private var store: BabyNameStore
    @State private var index = 0
    @State private var selectedStyle: NameStyle?
    @State private var showMatch: BabyName?

    private var deck: [BabyName] {
        let choices = store.choices
        let filtered = NameLibrary.all.filter { selectedStyle == nil || $0.style == selectedStyle }
        let remaining = filtered.filter { choices[$0.id] == nil }
        return remaining.isEmpty ? filtered : remaining
    }

    var body: some View {
        NavigationStack {
            VStack(spacing: 18) {
                Picker("Style", selection: $selectedStyle) {
                    Text("All").tag(NameStyle?.none)
                    ForEach(NameStyle.allCases) { Text($0.rawValue).tag(NameStyle?.some($0)) }
                }
                .pickerStyle(.segmented)
                .onChange(of: selectedStyle) { index = 0 }
                Spacer(minLength: 0)
                if let name = deck[safe: index % max(deck.count, 1)] {
                    SwipeCard(name: name)
                        .id(name.id)
                        .transition(.asymmetric(insertion: .scale(scale: 0.92).combined(with: .opacity), removal: .move(edge: .leading).combined(with: .opacity)))
                    HStack(spacing: 22) {
                        SwipeButton(symbol: "xmark", label: "Pass", color: .white.opacity(0.18)) { choose(.pass, name) }
                        SwipeButton(symbol: "bookmark.fill", label: "Maybe", color: Brand.blue) { choose(.maybe, name) }
                        SwipeButton(symbol: "heart.fill", label: "Love", color: Brand.pink) { choose(.love, name) }
                    }
                } else {
                    ContentUnavailableView("No names yet", systemImage: "sparkles", description: Text("Try another filter."))
                }
                Spacer(minLength: 0)
                Text("\(store.choices.count) names reviewed · \(store.matches.count) matches").font(.caption).foregroundStyle(.secondary)
            }
            .padding(20)
            .background(Brand.ink)
            .navigationTitle("Swipe & discover")
            .sheet(item: $showMatch) { MatchView(name: $0) }
        }
    }

    private func choose(_ choice: SwipeChoice, _ name: BabyName) {
        store.choose(choice, for: name)
        if choice == .love && store.partnerLikes.contains(name.id) { showMatch = name }
        withAnimation(.snappy) { index += 1 }
    }
}

private struct FavoritesView: View {
    @EnvironmentObject private var store: BabyNameStore
    @State private var selectedName: BabyName?

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 22) {
                    if store.favoriteNames.isEmpty {
                        ContentUnavailableView("Your shortlist is waiting", systemImage: "heart", description: Text("Love a name while browsing or swiping to save it here."))
                            .frame(minHeight: 420)
                    } else {
                        summary
                        ForEach(store.favoriteNames) { name in
                            NameRow(name: name, isFavorite: true) { store.toggleFavorite(name) }
                                .onTapGesture { selectedName = name }
                        }
                        if !NameLibrary.twinPairs(from: store.favoriteNames).isEmpty {
                            Text("Twin pair ideas").font(.title2.bold()).padding(.top, 8)
                            ForEach(NameLibrary.twinPairs(from: store.favoriteNames)) { pair in
                                HStack {
                                    VStack(alignment: .leading, spacing: 5) {
                                        Text("\(pair.first.name) + \(pair.second.name)").font(.title3.bold())
                                        Text("A balanced pair from your favorites").font(.caption).foregroundStyle(.secondary)
                                    }
                                    Spacer()
                                    Image(systemName: "sparkles").foregroundStyle(Brand.pink)
                                }
                                .padding(18).background(.white.opacity(0.06), in: RoundedRectangle(cornerRadius: 20))
                            }
                        }
                    }
                }
                .padding(20)
            }
            .background(Brand.ink)
            .navigationTitle("Favorites")
            .sheet(item: $selectedName) { NameDetailView(name: $0) }
        }
    }

    private var summary: some View {
        HStack(spacing: 12) {
            StatBlock(value: "\(store.favoriteNames.count)", label: "saved", symbol: "heart.fill")
            StatBlock(value: "\(store.matches.count)", label: "matches", symbol: "person.2.fill")
            StatBlock(value: "\(Set(store.favoriteNames.map(\.origin)).count)", label: "origins", symbol: "globe")
        }
    }
}

private struct CoupleView: View {
    @EnvironmentObject private var store: BabyNameStore
    @State private var copied = false

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 22) {
                    VStack(spacing: 16) {
                        Image(systemName: "person.2.fill").font(.system(size: 42)).foregroundStyle(.white)
                            .frame(width: 92, height: 92).background(Brand.gradient, in: Circle())
                        Text("Name it together").font(.largeTitle.bold())
                        Text("Compare private shortlists and reveal only the names you both love.").foregroundStyle(.secondary).multilineTextAlignment(.center)
                    }
                    .padding(.vertical, 16)
                    VStack(alignment: .leading, spacing: 14) {
                        Text("YOUR COUPLE SPACE").font(.caption.bold()).foregroundStyle(Brand.pink).tracking(1.2)
                        TextField("Partner name", text: $store.partnerName).textFieldStyle(.plain).font(.title3.bold()).onSubmit { store.savePartnerName() }
                        Button {
                            UIPasteboard.general.string = store.inviteCode
                            copied = true
                            DispatchQueue.main.asyncAfter(deadline: .now() + 1.5) { copied = false }
                        } label: {
                            HStack { Text(store.inviteCode).font(.system(.headline, design: .monospaced)); Spacer(); Label(copied ? "Copied" : "Copy", systemImage: copied ? "checkmark" : "doc.on.doc") }
                                .padding(16).background(.white.opacity(0.07), in: RoundedRectangle(cornerRadius: 16))
                        }
                        .buttonStyle(.plain)
                        Text("Share this code with your partner. Version 1.0 keeps demo picks on this device; account sync can plug into this space later.").font(.caption).foregroundStyle(.secondary)
                    }
                    .padding(20).background(.white.opacity(0.05), in: RoundedRectangle(cornerRadius: 24))
                    HStack(spacing: 12) {
                        StatBlock(value: "\(store.favorites.count)", label: "you loved", symbol: "heart.fill")
                        StatBlock(value: "\(store.partnerLikes.count)", label: "partner loved", symbol: "heart.circle.fill")
                        StatBlock(value: "\(store.matches.count)", label: "matches", symbol: "sparkles")
                    }
                    VStack(alignment: .leading, spacing: 12) {
                        HStack { Text("Your matches").font(.title2.bold()); Spacer(); Image(systemName: "sparkles").foregroundStyle(Brand.pink) }
                        if store.matches.isEmpty {
                            Text("Keep swiping—your shared favorites will appear here.").foregroundStyle(.secondary).padding(.vertical, 20)
                        } else {
                            ForEach(store.matches) { NameRow(name: $0, isFavorite: true) { store.toggleFavorite($0) } }
                        }
                    }
                    Button("Refresh demo partner picks") { store.loadDemoPartnerPicks() }
                        .buttonStyle(.bordered).tint(.white.opacity(0.7))
                }
                .padding(20)
            }
            .background(Brand.ink)
            .navigationTitle("Together")
        }
    }
}

private struct PreferencesView: View {
    @EnvironmentObject private var store: BabyNameStore
    @Environment(\.dismiss) private var dismiss
    private let vibes = ["Biblical", "Rare", "Vintage", "Modern", "Nature", "Celestial", "Gentle", "Bold", "Global", "Classic"]

    var body: some View {
        NavigationStack {
            VStack(alignment: .leading, spacing: 18) {
                Text("What feels like you?").font(.largeTitle.bold())
                Text("Choose a few qualities. Your recommendations update instantly and stay private on this device.").foregroundStyle(.secondary)
                FlowLayout(spacing: 10) {
                    ForEach(vibes, id: \.self) { vibe in
                        FilterChip(label: vibe, selected: store.preferredVibes.contains(vibe)) { store.toggleVibe(vibe) }
                    }
                }
                Spacer()
                Button("Update my picks") { dismiss() }.fontWeight(.bold).frame(maxWidth: .infinity).padding(.vertical, 15)
                    .background(Brand.gradient, in: Capsule()).foregroundStyle(.white)
            }
            .padding(22).background(Brand.ink).toolbar { ToolbarItem(placement: .topBarTrailing) { Button("Done") { dismiss() } } }
        }
        .presentationDetents([.large])
    }
}

private struct NameDetailView: View {
    @EnvironmentObject private var store: BabyNameStore
    @Environment(\.dismiss) private var dismiss
    let name: BabyName

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 22) {
                    VStack(spacing: 8) {
                        Text(name.name).font(.system(size: 54, weight: .black, design: .rounded))
                        Text(name.pronunciation).font(.headline).foregroundStyle(.white.opacity(0.75))
                        Text(name.meaning).font(.title2.bold())
                    }
                    .frame(maxWidth: .infinity).padding(.vertical, 40)
                    .background(Brand.gradient, in: RoundedRectangle(cornerRadius: 30))
                    HStack(spacing: 12) {
                        DetailPill(title: "Origin", value: name.origin, symbol: "globe")
                        DetailPill(title: "Style", value: name.style.rawValue, symbol: name.style.symbol)
                    }
                    VStack(alignment: .leading, spacing: 12) {
                        Text("The feeling").font(.title2.bold())
                        FlowLayout(spacing: 9) { ForEach(name.vibe, id: \.self) { Text($0).font(.subheadline.bold()).padding(.horizontal, 14).padding(.vertical, 9).background(.white.opacity(0.08), in: Capsule()) } }
                        Text("\(name.name) carries a \(name.vibe.joined(separator: " and ").lowercased()) spirit. Its \(name.origin) roots and meaning—\(name.meaning.lowercased())—give it a story worth sharing.").foregroundStyle(.secondary).padding(.top, 4)
                    }
                    .frame(maxWidth: .infinity, alignment: .leading)
                    Button { store.toggleFavorite(name) } label: {
                        Label(store.favorites.contains(name.id) ? "Saved to favorites" : "Love this name", systemImage: store.favorites.contains(name.id) ? "heart.fill" : "heart")
                            .fontWeight(.bold).frame(maxWidth: .infinity).padding(.vertical, 16)
                    }
                    .foregroundStyle(.white).background(Brand.gradient, in: Capsule())
                }
                .padding(20)
            }
            .background(Brand.ink)
            .toolbar { ToolbarItem(placement: .topBarTrailing) { Button("Done") { dismiss() } } }
        }
    }
}

private struct MatchView: View {
    @Environment(\.dismiss) private var dismiss
    let name: BabyName
    var body: some View {
        ZStack {
            Brand.ink.ignoresSafeArea()
            Circle().fill(Brand.pink.opacity(0.25)).frame(width: 430).blur(radius: 70)
            VStack(spacing: 18) {
                Image(systemName: "heart.circle.fill").font(.system(size: 94)).foregroundStyle(Brand.gradient)
                Text("It’s a match!").font(.system(size: 44, weight: .black, design: .rounded))
                Text("You both loved").foregroundStyle(.secondary)
                Text(name.name).font(.system(size: 58, weight: .black, design: .rounded))
                Text(name.meaning).font(.title3)
                Button("Keep swiping") { dismiss() }.fontWeight(.bold).padding(.horizontal, 28).padding(.vertical, 14).background(Brand.gradient, in: Capsule())
            }
        }
    }
}

private struct RecommendationCard: View {
    let name: BabyName
    let isFavorite: Bool
    let toggle: () -> Void
    var body: some View {
        VStack(alignment: .leading, spacing: 9) {
            HStack { Text(name.origin.uppercased()).font(.caption2.bold()).tracking(1); Spacer(); Button(action: toggle) { Image(systemName: isFavorite ? "heart.fill" : "heart") } }
            Spacer()
            Text(name.name).font(.system(size: 30, weight: .black, design: .rounded))
            Text(name.meaning).font(.subheadline).foregroundStyle(.white.opacity(0.72))
            HStack { ForEach(name.vibe.prefix(2), id: \.self) { Text($0).font(.caption2.bold()).padding(.horizontal, 8).padding(.vertical, 5).background(.white.opacity(0.1), in: Capsule()) } }
        }
        .padding(18).frame(width: 220, height: 220)
        .background(LinearGradient(colors: [Brand.purple.opacity(0.75), .white.opacity(0.06)], startPoint: .topLeading, endPoint: .bottomTrailing), in: RoundedRectangle(cornerRadius: 26))
        .overlay(RoundedRectangle(cornerRadius: 26).stroke(.white.opacity(0.1)))
    }
}

private struct NameRow: View {
    let name: BabyName
    let isFavorite: Bool
    let toggle: () -> Void
    var body: some View {
        HStack(spacing: 14) {
            Image(systemName: name.style.symbol).foregroundStyle(.white).frame(width: 48, height: 48).background(Brand.gradient, in: RoundedRectangle(cornerRadius: 15))
            VStack(alignment: .leading, spacing: 4) {
                Text(name.name).font(.title3.bold())
                Text("\(name.meaning) · \(name.origin)").font(.subheadline).foregroundStyle(.secondary).lineLimit(1)
            }
            Spacer()
            Button(action: toggle) { Image(systemName: isFavorite ? "heart.fill" : "heart").foregroundStyle(isFavorite ? Brand.pink : .secondary).font(.title3) }
            .buttonStyle(.plain).padding(8)
        }
        .padding(14).background(.white.opacity(0.055), in: RoundedRectangle(cornerRadius: 19)).overlay(RoundedRectangle(cornerRadius: 19).stroke(.white.opacity(0.07)))
    }
}

private struct SwipeCard: View {
    let name: BabyName
    var body: some View {
        VStack(spacing: 14) {
            HStack { Label(name.origin.uppercased(), systemImage: "globe").font(.caption.bold()).tracking(1); Spacer(); Text(name.style.rawValue).font(.caption.bold()).padding(.horizontal, 11).padding(.vertical, 7).background(.white.opacity(0.12), in: Capsule()) }
            Spacer()
            Image(systemName: name.style.symbol).font(.system(size: 48)).foregroundStyle(.white.opacity(0.75))
            Text(name.name).font(.system(size: 54, weight: .black, design: .rounded))
            Text(name.meaning).font(.title2.bold())
            Text(name.pronunciation).foregroundStyle(.white.opacity(0.7))
            HStack { ForEach(name.vibe, id: \.self) { Text($0).font(.caption.bold()).padding(.horizontal, 12).padding(.vertical, 7).background(.white.opacity(0.12), in: Capsule()) } }
            Spacer()
            Text("Swipe with the buttons below").font(.caption).foregroundStyle(.white.opacity(0.55))
        }
        .padding(24).frame(maxWidth: .infinity, maxHeight: 510)
        .background(Brand.gradient, in: RoundedRectangle(cornerRadius: 34, style: .continuous))
        .overlay(RoundedRectangle(cornerRadius: 34).stroke(.white.opacity(0.25)))
        .shadow(color: Brand.purple.opacity(0.35), radius: 30, y: 20)
    }
}

private struct SwipeButton: View {
    let symbol: String, label: String, color: Color
    let action: () -> Void
    var body: some View {
        Button(action: action) {
            VStack(spacing: 6) { Image(systemName: symbol).font(.title2.bold()).frame(width: 58, height: 58).background(color, in: Circle()); Text(label).font(.caption.bold()).foregroundStyle(.secondary) }
        }
        .buttonStyle(.plain)
    }
}

private struct FilterChip: View {
    let label: String, selected: Bool
    let action: () -> Void
    var body: some View {
        Button(label, action: action).font(.subheadline.bold()).padding(.horizontal, 15).padding(.vertical, 10)
            .background(selected ? AnyShapeStyle(Brand.gradient) : AnyShapeStyle(Color.white.opacity(0.08)), in: Capsule())
            .overlay(Capsule().stroke(.white.opacity(selected ? 0 : 0.12)))
    }
}

private struct StatBlock: View {
    let value: String, label: String, symbol: String
    var body: some View {
        VStack(spacing: 6) { Image(systemName: symbol).foregroundStyle(Brand.pink); Text(value).font(.title2.bold()); Text(label).font(.caption2).foregroundStyle(.secondary) }
            .frame(maxWidth: .infinity).padding(.vertical, 18).background(.white.opacity(0.055), in: RoundedRectangle(cornerRadius: 20))
    }
}

private struct DetailPill: View {
    let title: String, value: String, symbol: String
    var body: some View {
        HStack { Image(systemName: symbol).foregroundStyle(Brand.pink); VStack(alignment: .leading) { Text(title).font(.caption).foregroundStyle(.secondary); Text(value).font(.headline) }; Spacer() }
            .padding(16).frame(maxWidth: .infinity).background(.white.opacity(0.06), in: RoundedRectangle(cornerRadius: 18))
    }
}

private struct FlowLayout: Layout {
    var spacing: CGFloat = 8
    func sizeThatFits(proposal: ProposedViewSize, subviews: Subviews, cache: inout ()) -> CGSize {
        let width = proposal.width ?? 0
        var x: CGFloat = 0, y: CGFloat = 0, rowHeight: CGFloat = 0
        for view in subviews {
            let size = view.sizeThatFits(.unspecified)
            if x > 0 && x + size.width > width { x = 0; y += rowHeight + spacing; rowHeight = 0 }
            x += size.width + spacing; rowHeight = max(rowHeight, size.height)
        }
        return CGSize(width: width, height: y + rowHeight)
    }
    func placeSubviews(in bounds: CGRect, proposal: ProposedViewSize, subviews: Subviews, cache: inout ()) {
        var x = bounds.minX, y = bounds.minY, rowHeight: CGFloat = 0
        for view in subviews {
            let size = view.sizeThatFits(.unspecified)
            if x > bounds.minX && x + size.width > bounds.maxX { x = bounds.minX; y += rowHeight + spacing; rowHeight = 0 }
            view.place(at: CGPoint(x: x, y: y), proposal: ProposedViewSize(size))
            x += size.width + spacing; rowHeight = max(rowHeight, size.height)
        }
    }
}

private extension Collection {
    subscript(safe index: Index) -> Element? { indices.contains(index) ? self[index] : nil }
}
