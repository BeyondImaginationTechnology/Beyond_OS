import SwiftUI

struct RootView: View {
    @EnvironmentObject private var auth: JaguarAuthManager
    @EnvironmentObject private var store: JaguarChatStore
    @State private var showingAccount = false

    var body: some View {
        Group {
            if auth.isSignedIn {
                NavigationSplitView {
                    conversationList
                } detail: {
                    JaguarChatView(showingAccount: $showingAccount)
                }
            } else {
                JaguarWelcomeView()
            }
        }
        .tint(JaguarTheme.magenta)
        .background(JaguarTheme.ink.ignoresSafeArea())
        .sheet(isPresented: $showingAccount) { JaguarAccountView() }
        .alert("Jaguar", isPresented: Binding(get: { auth.message != nil }, set: { if !$0 { auth.clearMessage() } })) {
            Button("OK") { auth.clearMessage() }
        } message: {
            Text(auth.message ?? "")
        }
    }

    private var conversationList: some View {
        List(selection: $store.selectedID) {
            Section {
                Button {
                    store.newConversation()
                } label: {
                    Label("New conversation", systemImage: "square.and.pencil")
                        .fontWeight(.semibold)
                }
            }
            Section("Conversations") {
                ForEach(store.conversations) { conversation in
                    Button { store.select(conversation) } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(conversation.title).lineLimit(2).foregroundStyle(.white)
                            Text(conversation.updatedAt, style: .relative)
                                .font(.caption)
                                .foregroundStyle(JaguarTheme.secondaryText)
                        }
                    }
                    .tag(conversation.id)
                }
                .onDelete(perform: store.delete)
            }
        }
        .scrollContentBackground(.hidden)
        .background(JaguarTheme.ink)
        .navigationTitle("Beyond-1 AI")
    }
}

private struct JaguarWelcomeView: View {
    @EnvironmentObject private var auth: JaguarAuthManager

    var body: some View {
        ZStack {
            JaguarTheme.ink.ignoresSafeArea()
            Circle().fill(JaguarTheme.violet.opacity(0.25)).frame(width: 420).blur(radius: 80).offset(y: -260)
            VStack(spacing: 24) {
                JaguarMark(size: 82)
                VStack(spacing: 10) {
                    Text("LLAMA JAGUAR").font(.caption.weight(.bold)).tracking(3).foregroundStyle(JaguarTheme.mint)
                    Text("Where will we go\nbeyond?")
                        .font(.system(size: 46, weight: .black, design: .rounded))
                        .multilineTextAlignment(.center)
                    Text("AI fuel for the BIT ecosystem. Explain ideas, shape plans, and learn in plain language.")
                        .font(.title3)
                        .foregroundStyle(JaguarTheme.secondaryText)
                        .multilineTextAlignment(.center)
                }
                Button(action: auth.signIn) {
                    Label("Continue with Beyond ID", systemImage: "person.crop.circle.badge.checkmark")
                        .font(.headline)
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 16)
                        .background(JaguarTheme.gradient, in: RoundedRectangle(cornerRadius: 18))
                }
                .buttonStyle(.plain)
                Text("Jaguar uses Beyond ID so your native session never depends on browser cookies or a guest security check.")
                    .font(.footnote)
                    .foregroundStyle(JaguarTheme.secondaryText)
                    .multilineTextAlignment(.center)
            }
            .padding(28)
            .frame(maxWidth: 620)
        }
    }
}

private struct JaguarChatView: View {
    @EnvironmentObject private var auth: JaguarAuthManager
    @EnvironmentObject private var store: JaguarChatStore
    @Binding var showingAccount: Bool
    @FocusState private var composerFocused: Bool
    @State private var isNearBottom = true

    private var conversation: JaguarConversation? { store.selectedConversation }

    var body: some View {
        ZStack {
            JaguarTheme.ink.ignoresSafeArea()
            VStack(spacing: 0) {
                header
                Divider().overlay(Color.white.opacity(0.12))
                if let conversation {
                    if conversation.messages.isEmpty { suggestions }
                    else { transcript(conversation) }
                }
                composer
            }
        }
        .navigationBarHidden(true)
    }

    private var header: some View {
        HStack(spacing: 12) {
            JaguarMark(size: 38)
            VStack(alignment: .leading, spacing: 2) {
                Text("Llama Jaguar").font(.headline)
                HStack(spacing: 5) {
                    Circle().fill(JaguarTheme.mint).frame(width: 7, height: 7)
                    Text("v0.3 Preview · Explain")
                }
                .font(.caption)
                .foregroundStyle(JaguarTheme.secondaryText)
            }
            Spacer()
            Menu {
                ForEach(JaguarLanguage.allCases) { language in
                    Button { store.setLanguage(language) } label: {
                        if language == conversation?.language { Label(language.title, systemImage: "checkmark") }
                        else { Text(language.title) }
                    }
                }
            } label: {
                Image(systemName: "globe").frame(width: 38, height: 38).background(JaguarTheme.panelRaised, in: Circle())
            }
            Button { showingAccount = true } label: {
                Image(systemName: "person.crop.circle").font(.title2)
            }
            .accessibilityLabel("Account")
        }
        .padding(.horizontal, 18)
        .padding(.vertical, 12)
    }

    private var suggestions: some View {
        ScrollView {
            VStack(spacing: 18) {
                Spacer(minLength: 48)
                JaguarMark(size: 64)
                Text("Where will we go beyond?")
                    .font(.system(size: 36, weight: .black, design: .rounded))
                    .multilineTextAlignment(.center)
                Text("Ask Jaguar to explain an idea, shape a plan, or help you find a stronger starting point.")
                    .font(.title3)
                    .foregroundStyle(JaguarTheme.secondaryText)
                    .multilineTextAlignment(.center)
                ForEach([
                    "Explain AI tokens with a memorable analogy.",
                    "Help me turn a rough idea into a clear project plan.",
                    "Teach me something difficult in plain language."
                ], id: \.self) { prompt in
                    Button {
                        store.draft = prompt
                        Task { await send() }
                    } label: {
                        HStack { Text(prompt).multilineTextAlignment(.leading); Spacer(); Image(systemName: "arrow.up.right") }
                            .padding(18)
                            .frame(maxWidth: 620)
                            .background(JaguarTheme.panel, in: RoundedRectangle(cornerRadius: 18))
                            .overlay(RoundedRectangle(cornerRadius: 18).stroke(Color.white.opacity(0.13)))
                    }
                    .buttonStyle(.plain)
                }
            }
            .padding(24)
        }
    }

    private func transcript(_ conversation: JaguarConversation) -> some View {
        GeometryReader { viewport in
            ScrollViewReader { proxy in
                ScrollView {
                    LazyVStack(spacing: 18) {
                        ForEach(conversation.messages) { message in
                            JaguarMessageView(message: message).id(message.id)
                        }
                        if store.isThinking {
                            HStack(alignment: .top, spacing: 12) {
                                JaguarMark(size: 34)
                                VStack(alignment: .leading, spacing: 6) {
                                    HStack { ProgressView(); Text("\(store.thinkingStage)…") }
                                    Text("\(store.elapsedSeconds)s elapsed")
                                        .font(.caption.monospacedDigit())
                                        .foregroundStyle(JaguarTheme.secondaryText)
                                }
                                Spacer()
                            }
                            .id("thinking")
                        }
                        GeometryReader { marker in
                            Color.clear.preference(key: JaguarBottomPreferenceKey.self, value: marker.frame(in: .named("chat-scroll")).minY)
                        }
                        .frame(height: 1)
                        .id("bottom")
                    }
                    .padding(18)
                }
                .coordinateSpace(name: "chat-scroll")
                .onPreferenceChange(JaguarBottomPreferenceKey.self) { markerY in
                    isNearBottom = markerY <= viewport.size.height + 100
                }
                .onChange(of: conversation.messages.count) { _, _ in
                    if isNearBottom { withAnimation { proxy.scrollTo("bottom", anchor: .bottom) } }
                }
                .onChange(of: store.isThinking) { _, thinking in
                    if thinking { withAnimation { proxy.scrollTo("thinking", anchor: .bottom) } }
                }
                .overlay(alignment: .bottomTrailing) {
                    if !isNearBottom {
                        Button { withAnimation { proxy.scrollTo("bottom", anchor: .bottom) } } label: {
                            Image(systemName: "arrow.down").padding(12).background(.ultraThinMaterial, in: Circle())
                        }
                        .padding(12)
                        .accessibilityLabel("Jump to latest message")
                    }
                }
            }
        }
    }

    private var composer: some View {
        VStack(spacing: 8) {
            if let error = store.errorMessage {
                HStack(alignment: .top) {
                    Image(systemName: "exclamationmark.triangle.fill")
                    Text(error).frame(maxWidth: .infinity, alignment: .leading)
                    Button { store.errorMessage = nil } label: { Image(systemName: "xmark") }
                }
                .font(.footnote)
                .foregroundStyle(.pink)
            }
            HStack(alignment: .bottom, spacing: 10) {
                TextField("Message Jaguar…", text: $store.draft, axis: .vertical)
                    .lineLimit(1...6)
                    .focused($composerFocused)
                    .padding(.horizontal, 16)
                    .padding(.vertical, 13)
                    .background(JaguarTheme.panel, in: RoundedRectangle(cornerRadius: 18))
                Button { Task { await send() } } label: {
                    Image(systemName: "arrow.up")
                        .font(.headline.bold())
                        .frame(width: 50, height: 50)
                        .background(store.draft.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty ? AnyShapeStyle(Color.gray.opacity(0.35)) : AnyShapeStyle(JaguarTheme.gradient), in: RoundedRectangle(cornerRadius: 16))
                }
                .disabled(store.isThinking || store.draft.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                .accessibilityLabel("Send message")
            }
            Text("Explain is the live fast lane. Jaguar can make mistakes; check important information.")
                .font(.caption2)
                .foregroundStyle(JaguarTheme.secondaryText)
                .multilineTextAlignment(.center)
        }
        .padding(14)
        .background(.ultraThinMaterial)
    }

    private func send() async {
        guard let token = auth.accessToken else {
            auth.signOut(message: "Your Beyond ID session expired. Sign in again to continue.")
            return
        }
        composerFocused = false
        let success = await store.send(accessToken: token)
        if !success, store.errorMessage?.localizedCaseInsensitiveContains("session expired") == true {
            auth.signOut(message: "Your Beyond ID session expired. Sign in again to continue.")
        }
    }
}

private struct JaguarBottomPreferenceKey: PreferenceKey {
    static let defaultValue: CGFloat = .greatestFiniteMagnitude
    static func reduce(value: inout CGFloat, nextValue: () -> CGFloat) { value = nextValue() }
}

private struct JaguarMessageView: View {
    let message: JaguarMessage

    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            if message.role == .assistant { JaguarMark(size: 34) }
            else { Text("YOU").font(.caption2.bold()).frame(width: 34, height: 34).background(JaguarTheme.panelRaised, in: RoundedRectangle(cornerRadius: 10)) }
            Text(message.content)
                .textSelection(.enabled)
                .frame(maxWidth: 720, alignment: .leading)
                .padding(.top, 6)
            Spacer(minLength: 0)
        }
        .accessibilityElement(children: .combine)
        .accessibilityLabel(message.role == .assistant ? "Jaguar" : "You")
    }
}

private struct JaguarMark: View {
    let size: CGFloat

    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: size * 0.27).fill(JaguarTheme.gradient)
            RoundedRectangle(cornerRadius: size * 0.22).fill(JaguarTheme.ink).padding(size * 0.08)
            Image(systemName: "eye.fill").font(.system(size: size * 0.40, weight: .bold)).foregroundStyle(JaguarTheme.gradient)
        }
        .frame(width: size, height: size)
        .accessibilityHidden(true)
    }
}

private struct JaguarAccountView: View {
    @EnvironmentObject private var auth: JaguarAuthManager
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        NavigationStack {
            List {
                Section("Beyond ID") {
                    Label("Signed in securely", systemImage: "checkmark.shield.fill").foregroundStyle(JaguarTheme.mint)
                    Button("Sign out", role: .destructive) { auth.signOut(); dismiss() }
                }
                Section("About") {
                    LabeledContent("App", value: "Beyond-1 AI")
                    LabeledContent("Version", value: "0.1 (v0.3 Preview service)")
                    Text("Conversation history stays on this device. Messages are sent to Jaguar when you ask a question.")
                }
            }
            .navigationTitle("Account")
            .toolbar { ToolbarItem(placement: .confirmationAction) { Button("Done") { dismiss() } } }
        }
    }
}
