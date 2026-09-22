$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$androidApp = Join-Path $projectRoot 'DailyBreathAndroid\app'
if (-not (Test-Path -LiteralPath $androidApp)) {
    throw "Could not find DailyBreathAndroid\app under $projectRoot. Run this script from the workspace root."
}

$keystorePath = Join-Path $androidApp 'upload-keystore.jks'
$alias = 'daily-breath-upload'

if (Test-Path -LiteralPath $keystorePath) {
    throw "Keystore already exists at $keystorePath. Rename or remove it only if you are certain it is not needed."
}

$keytool = Get-Command keytool.exe -ErrorAction SilentlyContinue
if (-not $keytool) {
    throw 'keytool.exe was not found. Install/select a JDK, then run this script again.'
}

Write-Host "Creating $keystorePath"
Write-Host 'Use a strong password and store it in a password manager. Do not commit the JKS or password files.'

& $keytool.Source `
    -genkeypair `
    -v `
    -keystore $keystorePath `
    -alias $alias `
    -keyalg RSA `
    -keysize 2048 `
    -validity 10000 `
    -storetype JKS

if ($LASTEXITCODE -ne 0) {
    throw "keytool failed with exit code $LASTEXITCODE."
}

Write-Host "Created: $keystorePath"
Write-Host "Alias:   $alias"
Write-Host 'Keep the keystore password and key password available for Gradle/Play Console setup.'
