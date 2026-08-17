import Foundation
import XCTest
@testable import FrenchQuest

final class FrenchQuestTests: XCTestCase {
    func testLaunchContentHasPlayableRegions() {
        XCTAssertEqual(QuestContent.regions.count, 3)
        XCTAssertEqual(QuestContent.regions.reduce(0) { $0 + $1.challenges.count }, 9)
    }

    func testEachChallengeHasAnswerInOptions() {
        for region in QuestContent.regions {
            for challenge in region.challenges {
                XCTAssertTrue(challenge.options.contains(challenge.answer), "\(challenge.id) answer must be selectable")
            }
        }
    }

    func testQuestThemesAreAvailable() {
        XCTAssertEqual(QuestTheme.allCases.map(\.title), ["Night", "Riviera", "Market", "Garden"])
    }

    func testCloudSaveUsesServerFieldNames() throws {
        let save = FrenchQuestCloudSave(
            completedChallengeIDs: ["hello", "thanks"],
            xp: 55,
            hearts: 4,
            streak: 2,
            theme: "riviera"
        )
        let data = try JSONEncoder().encode(save)
        let object = try XCTUnwrap(JSONSerialization.jsonObject(with: data) as? [String: Any])

        XCTAssertEqual(object["completed_challenge_ids"] as? [String], ["hello", "thanks"])
        XCTAssertEqual(object["xp"] as? Int, 55)
    }

    @MainActor
    func testInitialStoreState() {
        let store = QuestStore()

        XCTAssertEqual(store.totalChallenges, 9)
        XCTAssertGreaterThanOrEqual(store.hearts, 0)
    }
}
