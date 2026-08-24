import XCTest
@testable import BeyondSpace

final class BeyondSpaceTests: XCTestCase {
    func testEverySignHasAReading() {
        XCTAssertEqual(SampleContent.readings.count, ZodiacSign.allCases.count)
    }

    func testFactSourcesUseHTTPS() {
        XCTAssertTrue(SampleContent.facts.allSatisfy { $0.sourceURL.scheme == "https" })
    }
}
