param(
    [int]$VersionCode = 4,
    [string]$VersionName = '2.2.0'
)

$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$keystorePath = Join-Path $env:USERPROFILE '.android\DailyBreath_Play_Upload.jks'
$expectedSha256 = 'BA:C5:96:B8:FF:A2:28:0D:00:23:84:B6:65:A6:C9:7D:EA:B9:85:C3:FE:12:AC:D3:BB:3E:42:66:88:9B:8F:56'
$keyAlias = 'daily-breath-upload'

if (-not (Test-Path -LiteralPath $keystorePath)) {
    throw "Play upload keystore is missing: $keystorePath"
}

if (-not $env:JAVA_HOME) {
    $env:JAVA_HOME = 'C:\Program Files\Android\Android Studio\jbr'
}
if (-not $env:ANDROID_HOME) {
    $env:ANDROID_HOME = Join-Path $env:LOCALAPPDATA 'Android\Sdk'
}
$env:ANDROID_SDK_ROOT = $env:ANDROID_HOME
$env:PATH = (Join-Path $env:JAVA_HOME 'bin') + ';' + $env:PATH

$storePassword = Read-Host 'Original BOOTSTRAP_KEYSTORE_PASSWORD' -AsSecureString

function ConvertTo-PlainText([Security.SecureString]$secret) {
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secret)
    try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}

try {
    $env:CM_KEYSTORE_PATH = $keystorePath
    $env:CM_KEY_ALIAS = $keyAlias
    $env:CM_KEYSTORE_PASSWORD = ConvertTo-PlainText $storePassword

    $keytool = Join-Path $env:JAVA_HOME 'bin\keytool.exe'
    $certificate = & $keytool -list -v -keystore $keystorePath -storepass:env CM_KEYSTORE_PASSWORD -alias $keyAlias 2>&1
    if ($LASTEXITCODE -ne 0) { throw 'Original bootstrap keystore password was rejected.' }
    if (($certificate -join "`n") -notmatch [regex]::Escape($expectedSha256)) {
        throw 'Keystore certificate does not match the Play Console upload key.'
    }
    Write-Output 'Keystore password accepted; upload certificate matches Google Play.'

    $keyPassword = Read-Host 'Original BOOTSTRAP_KEY_PASSWORD (Enter if same)' -AsSecureString
    $env:CM_KEY_PASSWORD = if ($keyPassword.Length -eq 0) { $env:CM_KEYSTORE_PASSWORD } else { ConvertTo-PlainText $keyPassword }
    $env:CI = 'true'

    Push-Location $projectRoot
    try {
        & .\gradlew.bat --no-daemon --console=plain "-PversionCode=$VersionCode" "-PversionName=$VersionName" bundlePlayRelease
        if ($LASTEXITCODE -ne 0) { throw 'Release bundle build failed.' }
    }
    finally { Pop-Location }

    $bundle = Join-Path $projectRoot 'app\build\outputs\bundle\playRelease\app-play-release.aab'
    $jarsigner = Join-Path $env:JAVA_HOME 'bin\jarsigner.exe'
    $verification = & $jarsigner -verify $bundle 2>&1
    if ($LASTEXITCODE -ne 0 -or ($verification -join "`n") -notmatch 'jar verified') {
        throw 'Release bundle signature verification failed.'
    }
    $bundleCertificate = & $keytool -printcert -jarfile $bundle 2>&1
    if (($bundleCertificate -join "`n") -notmatch [regex]::Escape($expectedSha256)) {
        throw 'Signed bundle certificate does not match the Play Console upload key.'
    }
    Write-Output "Signed Play review bundle: $bundle"
}
finally {
    'CM_KEYSTORE_PATH','CM_KEY_ALIAS','CM_KEYSTORE_PASSWORD','CM_KEY_PASSWORD','CI' | ForEach-Object {
        Remove-Item "Env:$_" -ErrorAction SilentlyContinue
    }
}
