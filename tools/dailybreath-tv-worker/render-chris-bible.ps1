param(
  [Parameter(Mandatory=$true)][string]$AudioPath,
  [Parameter(Mandatory=$true)][string]$OutputPath,
  [Parameter(Mandatory=$true)][string]$Reference,
  [string]$Passage = ''
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$template = Join-Path $root 'dailybreath\assets\videos\DB_Christian_StudyV20_Chris_Speaking_Test.blend'
$script = Join-Path $PSScriptRoot 'render-chris-daily.py'

$blender = 'C:\Program Files\Blender Foundation\Blender 5.2\blender.exe'
if (!(Test-Path -LiteralPath $blender)) {
  $found = Get-Command blender, blender.exe -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Path -First 1
  if ($found) { $blender = $found }
}

if (!(Test-Path -LiteralPath $blender)) { throw "Blender executable was not found. Install Blender 5.2 or add blender.exe to PATH." }
if (!(Test-Path -LiteralPath $template)) { throw "Chris presenter V20 template is missing at $template" }
if (!(Test-Path -LiteralPath $script)) { throw "Chris daily render script is missing at $script" }
if (!(Test-Path -LiteralPath $AudioPath)) { throw "Chris narration audio is missing at $AudioPath" }

$arguments = @(
  '--background', $template,
  '--python', $script,
  '--',
  '--audio', $AudioPath,
  '--output', $OutputPath,
  '--reference', $Reference,
  '--passage', $Passage
)

Write-Host "Rendering Chris Morning Feature in Blender 5.2 with DB_Christian_StudyV20_Chris_Speaking_Test.blend..."
& $blender @arguments
if ($LASTEXITCODE -ne 0) { throw "Blender could not render Chris's Morning Verse video (exit code $LASTEXITCODE)." }
if (!(Test-Path -LiteralPath $OutputPath) -or (Get-Item -LiteralPath $OutputPath).Length -lt 1024) {
  throw "Chris's Blender render was not created at $OutputPath."
}
Write-Host "Successfully rendered Chris Morning Feature video in Blender V20: $OutputPath"
