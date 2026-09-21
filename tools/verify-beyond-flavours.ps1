$ErrorActionPreference = 'Stop'
$catalogPath = Join-Path $PSScriptRoot '..\beyond-os-desktop\flavours\catalog.json'
$profilesPath = Join-Path $PSScriptRoot '..\beyond-os-desktop\flavours\profiles.json'
$catalog = Get-Content -LiteralPath $catalogPath -Raw | ConvertFrom-Json
$profiles = Get-Content -LiteralPath $profilesPath -Raw | ConvertFrom-Json

if ($catalog.product -ne 'Beyond Imagination OS') { throw 'Unexpected product name.' }
if ($catalog.flavours.Count -ne 7) { throw "Expected 7 flavours, found $($catalog.flavours.Count)." }

$expected = @('home', 'core', 'creator', 'academy', 'cyber', 'sentinel', 'gaming')
$actual = @($catalog.flavours | ForEach-Object { $_.id })
if (@(Compare-Object $expected $actual).Count -ne 0) { throw 'Flavour IDs do not match the official seven-flavour catalog.' }

foreach ($flavour in $catalog.flavours) {
    if ([string]::IsNullOrWhiteSpace($flavour.name) -or [string]::IsNullOrWhiteSpace($flavour.purpose)) {
        throw "Flavour $($flavour.id) is missing required metadata."
    }
    if ($flavour.status -eq 'buildable' -and [string]::IsNullOrWhiteSpace($flavour.buildTree)) {
        throw "Buildable flavour $($flavour.id) is missing its build tree."
    }
    if ($flavour.status -eq 'profile-defined' -and [string]::IsNullOrWhiteSpace($flavour.base)) {
        throw "Profile-defined flavour $($flavour.id) is missing its base flavour."
    }
    if ($flavour.status -eq 'profile-defined') {
        $profile = $profiles.PSObject.Properties[$flavour.profile]
        if ($null -eq $profile) { throw "Missing profile definition for $($flavour.id)." }
    }
}

Write-Output "PASS: $($catalog.product) catalog contains 7 validated flavours."
