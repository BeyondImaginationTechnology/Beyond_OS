# Daily Breath local TV worker

Run once while signed in:

```powershell
.\setup-secret.ps1 -Token 'the-token-from-private-live-config'
schtasks /Create /TN "Beyond Daily Breath TV" /SC DAILY /ST 05:40 /TR "powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\Users\Greg\Documents\Beyond_OS\tools\dailybreath-tv-worker\run.ps1" /RL LIMITED /F
```

`worker-secret.xml` is encrypted with Windows DPAPI for the current Windows account. The server endpoint and upload endpoint require the same value from private `dailybreath.local_worker_token`.