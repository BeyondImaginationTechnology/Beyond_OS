# Apple CI: Azure Pipelines and Xcode Cloud

DailyBreath uses both services with deliberately separate jobs:

| Service | Purpose | Signing / delivery |
| --- | --- | --- |
| Azure Pipelines | Fast pull-request and `main` validation | No signing; simulator tests only |
| Xcode Cloud | Apple-native archive and TestFlight delivery | Xcode Cloud-managed signing for the app, widget, and App Clip |

This avoids duplicating credentials and avoids Azure's Xcode task limitation for
projects that need multiple provisioning profiles. It also keeps routine Azure
usage limited to changes under `DailyBreathApple/`.

## Azure Pipelines

1. Create an Azure DevOps project and connect this repository.
2. Choose **Pipelines → New pipeline**, select the repository, and use the
   existing `azure-pipelines.yml` at the repository root.
3. Run it once and approve the pipeline. It tests the shared scheme on an iPhone
   simulator with code signing disabled and uploads the `.xcresult` bundle even
   when tests fail.
4. Require this pipeline for pull requests to `main` in the repository branch
   policy, if the repository host supports status checks.

The pipeline intentionally has no Apple certificate, provisioning-profile, App
Store Connect key, or secret variable. Do not add these for PR validation.

The hosted macOS image must contain an Xcode version that supports the project.
The first pipeline step prints its version and installed simulators. If `iPhone
16` is renamed or unavailable on a future image, update the `destination`
variable in `azure-pipelines.yml` to one displayed in that log.

## Xcode Cloud

Xcode Cloud setup is an App Store Connect/Xcode action; it cannot be enabled by
committing a YAML file. The repository already includes the compatible hook at
`DailyBreathApple/ci_scripts/ci_post_clone.sh`.

1. Open `DailyBreathApple/TheDailyBreath.xcodeproj` in Xcode while signed into
   the `FK9QM3VUNH` development team.
2. Select **Product → Xcode Cloud → Create Workflow**, connect the repository,
   then select the **The Daily Breath** scheme.
3. Create a **Validate** workflow: start on pull requests and changes to `main`,
   add a Test action on an iPhone simulator, and do not distribute its builds.
4. Create a separate **Release** workflow: start manually or from a protected
   release branch/tag, add an Archive action, and use the TestFlight post-action.
   Do not enable automatic TestFlight distribution on every `main` push.
5. In the workflow's signing settings, enable automatic signing for the app,
   `DailyBreathWidget`, and `TheDailyBreathClip`. Keep their provisioning
   profiles independent; this app has multiple bundle targets.
6. Run the Validate workflow once, then run Release only after confirming the
   archive's privacy report and TestFlight build metadata.

The hook only writes diagnostics, so it consumes no meaningful build time and
does not install dependencies. Xcode Cloud discovers hooks only when they are
inside `ci_scripts` beside the selected Xcode project. Make it executable before
the next commit from a Unix-compatible Git client, if its executable bit is not
preserved:

```sh
git update-index --chmod=+x DailyBreathApple/ci_scripts/ci_post_clone.sh
```

## Operating rule

Use Azure as the inexpensive early warning system and Xcode Cloud as the source
of truth for distributable Apple builds. A successful Azure run does not prove
that Apple signing, the widget, the App Clip, or TestFlight upload will succeed;
the Release workflow is the release gate for those concerns.
