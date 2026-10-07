param(
  [Parameter(Mandatory=$true)][string]$AudioPath,
  [Parameter(Mandatory=$true)][string]$OutputPath,
  [Parameter(Mandatory=$true)][string]$Reference,
  [string]$Passage = ''
)

$ErrorActionPreference = 'Stop'
$blender = 'C:\Program Files\Blender Foundation\Blender 5.2\blender.exe'
$template = Join-Path (Split-Path -Parent (Split-Path -Parent $PSScriptRoot)) 'dailybreath\assets\videos\DB_Christian_StudyV14_Morning_Verse_Timeline.blend'
$script = Join-Path $PSScriptRoot 'render-chris-daily.py'

if (!(Test-Path -LiteralPath $blender)) { throw 'Blender 5.2 is not installed at the configured path.' }
if (!(Test-Path -LiteralPath $template)) { throw 'Chris presenter template is missing.' }
if (!(Test-Path -LiteralPath $script)) { throw 'Chris daily render script is missing.' }
if (!(Test-Path -LiteralPath $AudioPath)) { throw 'Chris narration MP3 is missing.' }

$arguments = @(
  '--background', $template,
  '--python', $script,
  '--',
  '--audio', $AudioPath,
  '--output', $OutputPath,
  '--reference', $Reference,
  '--passage', $Passage
)

& $blender @arguments
if ($LASTEXITCODE -ne 0) { throw "Blender could not render Chris's Bible verse video (exit $LASTEXITCODE)." }
if (!(Test-Path -LiteralPath $OutputPath) -or (Get-Item -LiteralPath $OutputPath).Length -lt 1024) {
  throw "Chris's render was not created at $OutputPath."
}
