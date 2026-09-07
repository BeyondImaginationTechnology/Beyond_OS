# Beyond TV TestFlight release

`azure-pipelines-beyondtv-release.yml` tests Beyond TV iOS, creates a signed
App Store archive, and uploads the IPA to TestFlight.

The pipeline reuses the `dailybreath-testflight` variable group, the shared
`AuthKey_WCGDUVCBRB.p8` API key, and the shared
`DailyBreath_Distribution.p12` certificate. Azure Secure Files must also contain
`Beyond_TV_Azure_App_Store.mobileprovision`, an App Store profile named
`Beyond TV Azure App Store` for `technology.co.beyondimagination.beyondtv`.

The variable group must provide `BEYOND_TV_PROFILE_NAME` with the value
`Beyond TV Azure App Store`. Run the pipeline manually from `main`; build 111 is
the default for this release. Never reuse a build number accepted by App Store
Connect.
