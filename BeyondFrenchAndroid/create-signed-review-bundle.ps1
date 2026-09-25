$ErrorActionPreference = 'Stop'

$projectRoot = $PSScriptRoot
$keystorePath = 'C:\Users\Greg\Documents\keystore\beyondfrench'
$existingBundle = Join-Path $projectRoot 'app\release\app-release.aab'
$bundleOutput = Join-Path $projectRoot 'app\build\outputs\bundle\release\app-release.aab'
$reviewBundle = Join-Path $projectRoot 'app\release\app-release-v1.2.0-2.aab'
$javaHome = 'C:\Program Files\Android\Android Studio\jbr'
$gradle = 'C:\Users\Greg\.gradle\wrapper\dists\gradle-9.7.1-bin\1w1c7tv4s851m17nbqdsro2tv\gradle-9.7.1\bin\gradle.bat'
$keytool = Join-Path $javaHome 'bin\keytool.exe'
$jarsigner = Join-Path $javaHome 'bin\jarsigner.exe'

foreach ($requiredPath in @($keystorePath, $existingBundle, $gradle)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "Required release file is missing: $requiredPath"
    }
}
if (Test-Path -LiteralPath $reviewBundle) {
    throw "The review bundle destination already exists: $reviewBundle"
}

$storeSecure = Read-Host 'Enter the existing Beyond French keystore password' -AsSecureString
$keySecure = Read-Host 'Enter the Beyond French signing key password' -AsSecureString
$storePointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($storeSecure)
$keyPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($keySecure)

try {
    $env:CM_KEYSTORE_PATH = $keystorePath
    $env:CM_KEY_ALIAS = 'BEYONDFR'
    $env:CM_KEYSTORE_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($storePointer)
    $env:CM_KEY_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($keyPointer)

    & $keytool -list -keystore $keystorePath -storepass:env CM_KEYSTORE_PASSWORD -alias $env:CM_KEY_ALIAS *> $null
    if ($LASTEXITCODE -ne 0) { throw 'The keystore password or key alias was rejected.' }

    Push-Location $projectRoot
    try {
        & $gradle ':app:bundleRelease' '--offline'
        if ($LASTEXITCODE -ne 0) { throw 'The signed release bundle build failed.' }
    }
    finally {
        Pop-Location
    }

    $previousCertificate = (& $keytool -printcert -jarfile $existingBundle 2>$null | Out-String)
    $newCertificate = (& $keytool -printcert -jarfile $bundleOutput 2>$null | Out-String)
    $previousFingerprint = [regex]::Match($previousCertificate, 'SHA256:\s*([0-9A-F:]+)', 'IgnoreCase').Groups[1].Value
    $newFingerprint = [regex]::Match($newCertificate, 'SHA256:\s*([0-9A-F:]+)', 'IgnoreCase').Groups[1].Value
    if (-not $previousFingerprint -or $previousFingerprint -ne $newFingerprint) {
        throw 'The new bundle signing certificate does not match the existing Beyond French bundle.'
    }

    & $jarsigner -verify $bundleOutput *> $null
    if ($LASTEXITCODE -ne 0) { throw 'The new bundle signature could not be verified.' }

    Copy-Item -LiteralPath $bundleOutput -Destination $reviewBundle
    Write-Output "Signed review bundle: $reviewBundle"
    Write-Output "Version code 2 · version name 1.2.0 · SHA-256 signing fingerprint $newFingerprint"
}
finally {
    foreach ($name in @('CM_KEYSTORE_PATH', 'CM_KEY_ALIAS', 'CM_KEYSTORE_PASSWORD', 'CM_KEY_PASSWORD')) {
        Remove-Item -Path "Env:$name" -ErrorAction SilentlyContinue
    }
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($storePointer)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($keyPointer)
}
