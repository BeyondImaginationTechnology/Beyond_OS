# DailyBreath TestFlight release from Azure Pipelines

`azure-pipelines-dailybreath-release.yml` is intentionally manual-only. It
creates a signed archive of The Daily Breath, embeds the separately signed
widget and App Clip, then uploads the IPA to TestFlight.

## One-time Apple preparation

Use an Apple Developer account that is a member of team `FK9QM3VUNH` and has
access to the DailyBreath app in App Store Connect.

1. In Certificates, Identifiers & Profiles, create or confirm an **Apple
   Distribution** certificate. Export it from Keychain as a password-protected
   `.p12` file.
2. Create three **App Store** provisioning profiles, all based on the matching
   distribution certificate and with the capabilities already declared by the
   targets:

   | Target | Bundle ID | Secure-file name |
   | --- | --- | --- |
   | App | `technology.co.beyondimagination.thedailybreath` | `TheDailyBreath_AppStore.mobileprovision` |
   | Widget | `technology.co.beyondimagination.thedailybreath.widget` | `DailyBreathWidget_AppStore.mobileprovision` |
   | App Clip | `technology.co.beyondimagination.thedailybreath.Clip` | `TheDailyBreathClip_AppStore.mobileprovision` |

3. In App Store Connect, create a **team** API key with **App Manager** access.
   This may be reused for every app in the team. Download the `.p8` file once
   and record its Key ID and Issuer ID. Treat the `.p8`
   file like a password: Apple does not allow it to be downloaded again.

## One-time Azure preparation

In **Azure DevOps → Beyond OS → Pipelines → Library → Secure files**, upload
the following exact file names. Secure files are never committed to Git.

| Secure file | Source |
| --- | --- |
| `AuthKey_WCGDUVCBRB.p8` | App Store Connect API key |
| `DailyBreath_Distribution.p12` | Apple Distribution certificate export |
| `TheDailyBreath_AppStore.mobileprovision` | App profile |
| `DailyBreathWidget_AppStore.mobileprovision` | Widget profile |
| `TheDailyBreathClip_AppStore.mobileprovision` | App Clip profile |

In **Pipelines → Library → Variable group**, create `dailybreath-testflight`
and add these variables. Mark only the certificate password as secret; the API
key itself remains a Secure file.

| Variable | Value |
| --- | --- |
| `APPLE_CERTIFICATE_PASSWORD` | Password used when exporting the `.p12` (secret) |
| `APP_STORE_CONNECT_KEY_ID` | App Store Connect key ID |
| `APP_STORE_CONNECT_ISSUER_ID` | App Store Connect issuer ID |
| `THE_DAILY_BREATH_PROFILE_NAME` | App Store profile name, exactly as created by Apple |
| `DAILY_BREATH_WIDGET_PROFILE_NAME` | Widget profile name, exactly as created by Apple |
| `DAILY_BREATH_CLIP_PROFILE_NAME` | App Clip profile name, exactly as created by Apple |

## First release

1. In Azure DevOps, choose **Pipelines → New pipeline**, connect
   `BeyondImaginationTechnology/Beyond_OS`, and select the existing
   `azure-pipelines-dailybreath-release.yml` file on `main`.
2. Open the new pipeline's **Variables** tab and link `dailybreath-testflight`.
3. Run it manually from `main`. Each Azure run supplies a new temporary build
   number; Fastlane applies it to the CI checkout before archive.
4. Watch the archive and upload task. Azure succeeds once the IPA is accepted;
   Apple can take additional time to process it.
5. In App Store Connect → TestFlight, wait for processing, complete any export
   compliance prompt, then add internal testers.

## Future apps

Reuse this pattern with an app-specific release YAML file, Fastlane lane,
provisioning profiles, and variable group for each app. The team API key and
Apple Distribution certificate can be shared; each bundle ID and extension
still needs its own provisioning profile. Keep release pipelines manual-only
until their validation pipeline is stable.
