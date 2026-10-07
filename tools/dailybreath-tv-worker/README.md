# Daily Breath local TV worker

Run once while signed in:

```powershell
.\setup-secret.ps1 -Token 'the-token-from-private-live-config'
schtasks /Create /TN "Beyond Daily Breath TV" /SC DAILY /ST 05:40 /TR "powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\Users\Greg\Documents\Beyond_OS\tools\dailybreath-tv-worker\run.ps1" /RL LIMITED /F
```

`worker-secret.xml` is encrypted with Windows DPAPI for the current Windows account. The server endpoint and upload endpoint require the same value from private `dailybreath.local_worker_token`.

## Daily Windows task

The installed **Daily Breath — Daily Readings Production** task runs each day at **8:00 PM Pacific** while Greg is signed in. It writes one timestamped log for every run in `logs/`, can run on battery, wakes the PC when Windows permits it, and starts when the next interactive session is available after a missed schedule. Each run prepares the following calendar day so the files exist before early prayer-based programming slots.

It produces three faith-specific videos:

- **Bible:** the Daily Bible Verse, narrated by Chris's selected ElevenLabs voice (Prayan), rendered with the Chris study presenter scene in Blender.
- **Tanakh and Quran:** their own language-matched ElevenLabs narration and current Daily Breath TV render.

Chris's present rig has a timed seated body-performance loop but no facial bones or mouth shape keys. The narration and performance render together; adding phoneme-accurate lipsync requires a later facial-rig pass.

## YouTube upload authorization

Google Cloud must have the `youtube.upload` scope and the channel owner must be a test user while the OAuth app is in Testing. Run this once in PowerShell, using the OAuth client ID and client secret stored in the private production configuration:

```powershell
.\authorize-youtube.ps1 -ClientId 'your-client-id' -ClientSecret 'your-client-secret'
```

The script opens Google consent, listens only on `http://localhost:8765/oauth2/callback`, and writes an encrypted `youtube-oauth.xml` refresh token for the current Windows account. It does not upload a video. Keep this file private.

To publish the three rendered daily readings to YouTube as **private** videos, run the worker explicitly with:

```powershell
.\run.ps1
```

Each normal run uploads the completed video to Beyond TV and then to YouTube as a private video. Run `authorize-youtube.ps1` once before starting the worker. Use `-SkipYouTube` only when you need to produce a Beyond TV-only run.
