#!/bin/sh
# Xcode Cloud runs this hook after cloning. DailyBreath has no third-party
# dependencies to install, so keep it side-effect free and make the selected
# build environment visible in the build log.
set -eu

echo "Xcode Cloud build: ${CI_XCODE_CLOUD:-false}"
echo "Workflow: ${CI_WORKFLOW:-unknown}"
echo "Action: ${CI_XCODEBUILD_ACTION:-unknown}"
xcodebuild -version
