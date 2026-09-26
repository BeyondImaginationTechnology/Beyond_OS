import SwiftUI
import UIKit
import UniformTypeIdentifiers

private struct JaguarInkStroke: Identifiable {
    let id = UUID()
    var points: [CGPoint]
    var color: Color
    var width: CGFloat
    var isEraser: Bool = false
}

private struct JaguarInkColor: Identifiable {
    let name: String
    let color: Color

    var id: String { name }

    static let palette = [
        JaguarInkColor(name: "Black", color: .black),
        JaguarInkColor(name: "Violet", color: Color(red: 0.43, green: 0.16, blue: 0.74)),
        JaguarInkColor(name: "Magenta", color: Color(red: 0.83, green: 0.14, blue: 0.58)),
        JaguarInkColor(name: "Gold", color: Color(red: 0.66, green: 0.43, blue: 0.12))
    ]
}

private struct JaguarPNGDocument: FileDocument {
    static var readableContentTypes: [UTType] { [.png] }
    var data: Data

    init(data: Data) { self.data = data }

    init(configuration: ReadConfiguration) throws {
        data = configuration.file.regularFileContents ?? Data()
    }

    func fileWrapper(configuration: WriteConfiguration) throws -> FileWrapper {
        FileWrapper(regularFileWithContents: data)
    }
}

struct JaguarDrawStudioView: View {
    @Environment(\.dismiss) private var dismiss
    @State private var strokes: [JaguarInkStroke] = []
    @State private var undoneStrokes: [[JaguarInkStroke]] = []
    @State private var selectedColor = JaguarInkColor.palette[0]
    @State private var brushWidth: CGFloat = 0.009
    @State private var isErasing = false
    @State private var showsPaper = true
    @State private var document = JaguarPNGDocument(data: Data())
    @State private var showingExporter = false
    @State private var exportError: String?

    var body: some View {
        NavigationStack {
            VStack(spacing: 18) {
                HStack(alignment: .firstTextBaseline) {
                    VStack(alignment: .leading, spacing: 4) {
                        Text("Draw a stencil idea")
                            .font(.title2.bold())
                        Text("Sketch locally, then export a transparent PNG for the Tattoo editor.")
                            .font(.footnote)
                            .foregroundStyle(JaguarTheme.secondaryText)
                    }
                    Spacer(minLength: 0)
                }

                GeometryReader { geometry in
                    let boardSize = CGSize(width: geometry.size.width, height: geometry.size.height)
                    ZStack {
                        RoundedRectangle(cornerRadius: 18)
                            .fill(showsPaper ? Color.white : Color.clear)
                        JaguarInkCanvas(strokes: strokes) { point in
                            guard !strokes.isEmpty else { return }
                            strokes[strokes.count - 1].points.append(point)
                        } onStrokeBegan: { point in
                            undoneStrokes.removeAll()
                            strokes.append(JaguarInkStroke(points: [point], color: selectedColor.color, width: brushWidth, isEraser: isErasing))
                        }
                        .clipShape(RoundedRectangle(cornerRadius: 18))
                        .accessibilityLabel("Drawing canvas")
                    }
                    .overlay(RoundedRectangle(cornerRadius: 18).stroke(Color.white.opacity(0.16)))
                    .frame(width: boardSize.width, height: boardSize.height)
                }
                .frame(maxWidth: .infinity)
                .frame(height: 420)
                .background(JaguarTheme.panel, in: RoundedRectangle(cornerRadius: 18))
                .accessibilityIdentifier("jaguar-draw-canvas")

                HStack(spacing: 10) {
                    ForEach(JaguarInkColor.palette) { option in
                        Button {
                            selectedColor = option
                            isErasing = false
                        } label: {
                            Circle()
                                .fill(option.color)
                                .frame(width: 34, height: 34)
                                .overlay(Circle().stroke(.white, lineWidth: selectedColor.id == option.id && !isErasing ? 3 : 0))
                                .padding(3)
                        }
                        .buttonStyle(.plain)
                        .accessibilityLabel("\(option.name) ink")
                    }
                    Button { isErasing = true } label: {
                        Image(systemName: "eraser")
                            .frame(width: 38, height: 38)
                            .background(isErasing ? JaguarTheme.violet : JaguarTheme.panelRaised, in: Circle())
                    }
                    .accessibilityLabel("Eraser")
                    Spacer()
                    Button { showsPaper.toggle() } label: {
                        Image(systemName: showsPaper ? "rectangle" : "rectangle.on.rectangle")
                            .frame(width: 38, height: 38)
                            .background(JaguarTheme.panelRaised, in: Circle())
                    }
                    .accessibilityLabel(showsPaper ? "Show transparent canvas" : "Show white paper")
                }

                HStack(spacing: 14) {
                    Image(systemName: "line.3.horizontal")
                    Slider(value: $brushWidth, in: 0.003...0.025)
                        .tint(JaguarTheme.magenta)
                    Button("Undo", systemImage: "arrow.uturn.backward") { undo() }
                        .disabled(strokes.isEmpty)
                    Button("Redo", systemImage: "arrow.uturn.forward") { redo() }
                        .disabled(undoneStrokes.isEmpty)
                }
                .font(.subheadline.weight(.semibold))

                HStack(spacing: 12) {
                    Button("Clear", systemImage: "trash") { clear() }
                        .buttonStyle(.bordered)
                    Button {
                        document = JaguarPNGDocument(data: exportPNG())
                        showingExporter = true
                    } label: {
                        Label("Export PNG", systemImage: "square.and.arrow.down")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                    .tint(JaguarTheme.violet)
                    .disabled(strokes.isEmpty)
                }

                Text("Your sketch stays on this device. Export it, then choose Import drawing in the Beyond Tattoo stencil editor.")
                    .font(.caption)
                    .foregroundStyle(JaguarTheme.secondaryText)
                    .multilineTextAlignment(.center)
            }
            .padding(20)
            .background(JaguarTheme.ink.ignoresSafeArea())
            .navigationTitle("Draw Studio")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .confirmationAction) {
                    Button("Done") { dismiss() }
                }
            }
            .fileExporter(
                isPresented: $showingExporter,
                document: document,
                contentType: .png,
                defaultFilename: "beyond-tattoo-sketch.png"
            ) { result in
                if case let .failure(error) = result {
                    exportError = error.localizedDescription
                }
            }
            .alert("Couldn’t export drawing", isPresented: Binding(
                get: { exportError != nil },
                set: { if !$0 { exportError = nil } }
            )) {
                Button("OK", role: .cancel) { exportError = nil }
            } message: {
                Text(exportError ?? "Please try again.")
            }
        }
        .preferredColorScheme(.dark)
    }

    private func undo() {
        guard let last = strokes.popLast() else { return }
        undoneStrokes.append([last])
    }

    private func redo() {
        guard let group = undoneStrokes.popLast() else { return }
        strokes.append(contentsOf: group)
    }

    private func clear() {
        guard !strokes.isEmpty else { return }
        undoneStrokes.append(strokes)
        strokes.removeAll()
    }

    @MainActor
    private func exportPNG() -> Data {
        let imageSize = CGSize(width: 1200, height: 1600)
        let format = UIGraphicsImageRendererFormat()
        format.scale = 1
        format.opaque = false
        let renderer = UIGraphicsImageRenderer(size: imageSize, format: format)
        let image = renderer.image { rendererContext in
            let context = rendererContext.cgContext
            for stroke in strokes {
                guard let first = stroke.points.first else { continue }
                let path = CGMutablePath()
                path.move(to: CGPoint(x: first.x * imageSize.width, y: first.y * imageSize.height))
                for point in stroke.points.dropFirst() {
                    path.addLine(to: CGPoint(x: point.x * imageSize.width, y: point.y * imageSize.height))
                }
                context.setLineCap(.round)
                context.setLineJoin(.round)
                context.setLineWidth(stroke.width * imageSize.width)
                context.setBlendMode(stroke.isEraser ? .clear : .normal)
                context.setStrokeColor(UIColor(stroke.color).cgColor)
                context.addPath(path)
                context.strokePath()
            }
        }
        return image.pngData() ?? Data()
    }
}

private struct JaguarInkCanvas: View {
    let strokes: [JaguarInkStroke]
    let onPoint: (CGPoint) -> Void
    let onStrokeBegan: (CGPoint) -> Void
    @State private var hasActiveStroke = false

    var body: some View {
        GeometryReader { geometry in
            Canvas { context, size in
                for stroke in strokes {
                    guard let first = stroke.points.first else { continue }
                    var path = Path()
                    path.move(to: CGPoint(x: first.x * size.width, y: first.y * size.height))
                    for point in stroke.points.dropFirst() {
                        path.addLine(to: CGPoint(x: point.x * size.width, y: point.y * size.height))
                    }
                    var inkContext = context
                    if stroke.isEraser { inkContext.blendMode = .destinationOut }
                    inkContext.stroke(
                        path,
                        with: .color(stroke.color),
                        style: StrokeStyle(lineWidth: stroke.width * size.width, lineCap: .round, lineJoin: .round)
                    )
                }
            }
            .contentShape(Rectangle())
            .gesture(DragGesture(minimumDistance: 0)
                .onChanged { value in
                    let point = CGPoint(
                        x: min(max(value.location.x / max(geometry.size.width, 1), 0), 1),
                        y: min(max(value.location.y / max(geometry.size.height, 1), 0), 1)
                    )
                    if !hasActiveStroke {
                        hasActiveStroke = true
                        onStrokeBegan(point)
                    } else {
                        onPoint(point)
                    }
                }
                .onEnded { _ in hasActiveStroke = false }
            )
        }
    }
}
