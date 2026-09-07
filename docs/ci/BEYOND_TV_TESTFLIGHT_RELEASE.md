# Beyond TV TestFlight release

`azure-pipelines-beyondtv-release.yml` tests Beyond TV iOS, creates a signed
App Store archive, and uploads the IPA to TestFlight.

The pipeline reuses the `dailybreath-testflight` variable group, the shared
`AuthKey_WCGDUVCBRB.p8` API key, and the shared
`DailyBreath_Distribution.p12` certificate. Fastlane creates or refreshes the
App Store provisioning profile for `technology.co.beyondimagination.beyondtv`
through that API key and installs it on the ephemeral build agent.

Run the pipeline manually from `main`; build 111 is the default for this
release. Never reuse a build number accepted by App Store Connect.
