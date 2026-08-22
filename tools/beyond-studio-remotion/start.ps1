$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $projectDirectory

$nodeCommand = Get-Command node -ErrorAction SilentlyContinue
$nodePath = if ($nodeCommand) { $nodeCommand.Source } else { Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe' }
if (-not (Test-Path -LiteralPath $nodePath)) {
    throw 'Node.js 20 or newer is required. Install Node.js, then run this script again.'
}

if (-not (Test-Path -LiteralPath 'node_modules')) {
    Write-Host 'Installing the Beyond Studio Remotion bridge...'
    $pnpmCommand = Get-Command pnpm -ErrorAction SilentlyContinue
    if ($pnpmCommand) {
        & $pnpmCommand.Source install
    } else {
        $pnpmScript = Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\node\node_modules\pnpm\bin\pnpm.mjs'
        if (-not (Test-Path -LiteralPath $pnpmScript)) { throw 'pnpm is required to install the render bridge dependencies.' }
        & $nodePath $pnpmScript install
    }
}

& $nodePath server.mjs
