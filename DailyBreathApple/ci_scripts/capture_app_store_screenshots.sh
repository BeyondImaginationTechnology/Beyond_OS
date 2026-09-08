#!/usr/bin/env bash
# Capture App Store assets from the real DailyBreath app, not a rendered mockup.
set -euo pipefail

bundle_id="technology.co.beyondimagination.thedailybreath"
output_root="${1:?Provide the output directory for screenshots}"
derived_data="${DERIVED_DATA_PATH:-$PWD/.build/app-store-screenshots}"
rm -rf "$output_root"
mkdir -p "$output_root"

device_udid() {
  local kind="$1"
  xcrun simctl list devices available -j | /usr/bin/ruby -rjson -e '
    kind = ARGV.fetch(0)
    devices = JSON.parse(STDIN.read).fetch("devices").values.flatten
    preferred = case kind
      when "iphone" then devices.find { |d| d["isAvailable"] && d["name"].match?(/^iPhone .*Pro Max$/) }
      when "ipad" then devices.find { |d| d["isAvailable"] && d["name"].match?(/^iPad Pro.*13-inch/) }
    end
    fallback = devices.find { |d| d["isAvailable"] && d["name"].start_with?(kind == "iphone" ? "iPhone" : "iPad") }
    device = preferred || fallback
    abort "No available #{kind} simulator was found." unless device
    puts device.fetch("udid")
  ' "$kind"
}

iphone_udid="$(device_udid iphone)"
ipad_udid="$(device_udid ipad)"

xcrun simctl boot "$iphone_udid" 2>/dev/null || true
xcrun simctl bootstatus "$iphone_udid" -b
xcodebuild build \
  -project TheDailyBreath.xcodeproj \
  -scheme "The Daily Breath" \
  -configuration Release \
  -destination "id=$iphone_udid" \
  -derivedDataPath "$derived_data" \
  CODE_SIGNING_ALLOWED=NO

app_path="$(find "$derived_data/Build/Products" -type d -name "The Daily Breath.app" -print -quit)"
if [[ -z "$app_path" ]]; then
  echo "Could not locate the simulator app bundle." >&2
  exit 1
fi

capture_set() {
  local udid="$1"
  local folder="$2"
  xcrun simctl boot "$udid" 2>/dev/null || true
  xcrun simctl bootstatus "$udid" -b
  xcrun simctl uninstall "$udid" "$bundle_id" 2>/dev/null || true
  xcrun simctl install "$udid" "$app_path"
  xcrun simctl launch "$udid" "$bundle_id" >/dev/null
  sleep 5

  local destination="$output_root/$folder"
  mkdir -p "$destination"
  while IFS='|' read -r filename route; do
    # `openurl` presents iOS's "Open in The Daily Breath?" confirmation.
    # Launching with a simulator-only route argument keeps every capture in-app.
    xcrun simctl terminate "$udid" "$bundle_id" 2>/dev/null || true
    xcrun simctl launch "$udid" "$bundle_id" -dailyBreathCaptureRoute "$route" >/dev/null
    sleep 4
    xcrun simctl io "$udid" screenshot "$destination/$filename"
  done <<'ROUTES'
01-today-bible.png|today?theme=forest
02-torah-scripture.png|torah?theme=torahLight
03-quran-scripture.png|quran?theme=quranMoon
04-breathe-hourglass.png|breathe?theme=dawn
05-reflection-journal.png|journal?theme=rose
ROUTES
}

capture_set "$iphone_udid" "iPhone"
capture_set "$ipad_udid" "iPad"
echo "Captured real DailyBreath screenshots in $output_root"
