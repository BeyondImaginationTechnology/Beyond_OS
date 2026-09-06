# Beyond Baby Names TestFlight release

`azure-pipelines-beyondbabynames-release.yml` is the manual-only Azure pipeline
for version 1.0 of Beyond Baby Names. It tests the app on an iPhone simulator,
creates a signed App Store archive, and uploads the IPA to TestFlight.

## Required Apple profile

Create an App Store provisioning profile named
`Beyond Baby Names Azure App Store` for explicit App ID
`technology.co.beyondimagination.beyondbabynames`, using the Apple Distribution
certificate already stored in Azure. Download it as
`Beyond_Baby_Names_Azure_App_Store.mobileprovision`.

## Required Azure configuration

The pipeline reuses the existing `dailybreath-testflight` variable group and
these shared assets:

- `AuthKey_WCGDUVCBRB.p8`
- `DailyBreath_Distribution.p12`
- `APPLE_CERTIFICATE_PASSWORD`
- `APP_STORE_CONNECT_KEY_ID`
- `APP_STORE_CONNECT_ISSUER_ID`

Upload `Beyond_Baby_Names_Azure_App_Store.mobileprovision` to Azure Secure Files
and add this variable to the variable group:

| Variable | Value |
| --- | --- |
| `BEYOND_BABY_NAMES_PROFILE_NAME` | `Beyond Baby Names Azure App Store` |

Create an Azure pipeline from `azure-pipelines-beyondbabynames-release.yml`,
run it from `main` with build number `1`, and authorize the variable group and
Secure Files if Azure asks on the first run. Never reuse a build number that
App Store Connect has accepted.
