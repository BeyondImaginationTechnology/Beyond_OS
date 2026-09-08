param(
    [string]$BaseUrl = 'https://beyondimagination.co.technology/beyond-baby-names/api/couples.php'
)

$ErrorActionPreference = 'Stop'

function Invoke-CoupleApi {
    param(
        [hashtable]$Body,
        [string]$Token = ''
    )
    $headers = @{ Accept = 'application/json' }
    if ($Token) { $headers.Authorization = "Bearer $Token" }
    Invoke-RestMethod -Method Post -Uri $BaseUrl -Headers $headers `
        -ContentType 'application/json' -Body ($Body | ConvertTo-Json -Compress)
}

$creator = $null
try {
    $creator = Invoke-CoupleApi -Body @{ action = 'create'; displayName = 'API Test A' }
    $partner = Invoke-CoupleApi -Body @{
        action = 'join'
        inviteCode = $creator.inviteCode
        displayName = 'API Test B'
    }

    Invoke-CoupleApi -Token $creator.memberToken -Body @{
        action = 'savePick'; name = 'Luna'; decision = 'love'
    } | Out-Null
    $beforeMatch = Invoke-CoupleApi -Token $creator.memberToken -Body @{ action = 'state' }
    if ($beforeMatch.matches.Count -ne 0) {
        throw 'A one-sided pick was disclosed as a match.'
    }

    Invoke-CoupleApi -Token $partner.memberToken -Body @{
        action = 'savePick'; name = 'Luna'; decision = 'love'
    } | Out-Null
    Invoke-CoupleApi -Token $partner.memberToken -Body @{
        action = 'savePick'; name = 'Ezra'; decision = 'love'
    } | Out-Null

    $creatorState = Invoke-CoupleApi -Token $creator.memberToken -Body @{ action = 'state' }
    if ($creatorState.matches -notcontains 'Luna') {
        throw 'A mutual love was not returned as a match.'
    }
    if ($creatorState.matches -contains 'Ezra' -or $creatorState.ownPicks.name -contains 'Ezra') {
        throw 'The partner-only pick leaked to the creator.'
    }

    try {
        Invoke-CoupleApi -Body @{
            action = 'join'
            inviteCode = $creator.inviteCode
            displayName = 'API Test C'
        } | Out-Null
        throw 'A third member was allowed to join.'
    } catch {
        if ($_.Exception.Response.StatusCode.value__ -ne 409) { throw }
    }

    Write-Output "Couple Mode smoke test passed for space $($creator.coupleId)."
} finally {
    if ($creator) {
        Invoke-CoupleApi -Token $creator.memberToken -Body @{ action = 'close' } | Out-Null
    }
}
