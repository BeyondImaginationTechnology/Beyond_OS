# Daily Breath local TV worker

Run once while signed in:

```powershell
.\setup-secret.ps1 -Token 'the-token-from-private-live-config'
schtasks /Create /TN "Beyond Daily Breath TV" /SC DAILY /ST 05:40 /TR "powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\Users\Greg\Documents\Beyond_OS\tools\dailybreath-tv-worker\run.ps1" /RL LIMITED /F
```

`worker-secret.xml` is encrypted with Windows DPAPI for the current Windows account. The server endpoint and upload endpoint require the same value from private `dailybreath.local_worker_token`.

## YouTube upload authorization

Google Cloud must have the `youtube.upload` scope and the channel owner must be a test user while the OAuth app is in Testing. Run this once in PowerShell, using the OAuth client ID and client secret stored in the private production configuration:

```powershell
.\authorize-youtube.ps1 -ClientId 'your-client-id' -ClientSecret 'your-client-secret'
```

The script opens Google consent, listens only on `http://localhost:8765/oauth2/callback`, and writes an encrypted `youtube-oauth.xml` refresh token for the current Windows account. It does not upload a video. Keep this file private.

To publish the three rendered daily readings to YouTube as **private** videos, run the worker explicitly with:

```powershell
.\run.ps1 -UploadYouTube
```

The normal scheduled task does not publish to YouTube. Add `-UploadYouTube` to its task command only after reviewing a successful private upload.
