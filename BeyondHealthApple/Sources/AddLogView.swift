import SwiftUI

struct AddLogView: View {
    @EnvironmentObject private var store: HealthStore
    @State private var category: HealthCategory = .body
    @State private var title = ""
    @State private var detail = ""
    @State private var entryDate = Date.now
    @State private var includesPhoto = false
    @State private var didSave = false

    var body: some View {
        HealthScreen(title: "Add Log") {
            FamilySwitcher()

            HealthPanel {
                HealthEyebrow(text: "New entry")
                Picker("Category", selection: $category) {
                    ForEach(HealthCategory.allCases) { category in
                        Label(category.rawValue, systemImage: category.systemImage).tag(category)
                    }
                }
                .pickerStyle(.menu)
                .tint(Color.healthTeal)

                DatePicker("When", selection: $entryDate)
                    .foregroundStyle(.white)

                TextField("Short title", text: $title)
                    .padding(12)
                    .background(Color.healthPanelSoft, in: RoundedRectangle(cornerRadius: 8))

                TextField(promptText, text: $detail, axis: .vertical)
                    .lineLimit(3...6)
                    .padding(12)
                    .background(Color.healthPanelSoft, in: RoundedRectangle(cornerRadius: 8))

                if category == .food {
                    Button {
                        includesPhoto.toggle()
                    } label: {
                        Label(includesPhoto ? "Food photo attached" : "Mark food photo", systemImage: includesPhoto ? "checkmark.seal.fill" : "camera.fill")
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.bordered)
                    .tint(Color.healthLeaf)
                }

                Button {
                    store.addEntry(
                        category: category,
                        title: title,
                        detail: detail,
                        attachmentLabel: includesPhoto && category == .food ? "Food photo" : nil,
                        date: entryDate
                    )
                    title = ""
                    detail = ""
                    includesPhoto = false
                    didSave = true
                } label: {
                    Label("Save entry", systemImage: "checkmark.circle.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .tint(Color.healthTeal)
            }

            if didSave {
                HealthPanel {
                    Label("Saved to \(store.selectedMember.name)'s calendar", systemImage: "checkmark.seal.fill")
                        .font(.subheadline.weight(.bold))
                        .foregroundStyle(Color.healthTeal)
                }
            }
        }
    }

    private var promptText: String {
        switch category {
        case .body: "Symptoms, mood, pain, cycle notes, energy"
        case .food: "Food, drink, timing, reaction"
        case .sleep: "Wake time, sleep quality, dreams"
        case .medication: "Medication, pill, supplement, dose"
        case .smoke: "Cigarette or smoke session context"
        case .workout: "Workout completed or skipped"
        case .hygiene: "Hygiene or body care routine"
        }
    }
}
