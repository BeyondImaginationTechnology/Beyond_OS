$ErrorActionPreference = 'Stop'
$logDirectory = Join-Path $PSScriptRoot 'logs'
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$logPath = Join-Path $logDirectory ("daily-verse-{0}.log" -f (Get-Date -Format 'yyyy-MM-dd-HHmmss'))

try {
  & (Join-Path $PSScriptRoot 'run.ps1') *>&1 | Tee-Object -FilePath $logPath
  exit $LASTEXITCODE
} catch {
  $_ | Out-String | Tee-Object -FilePath $logPath -Append | Write-Error
  exit 1
}
