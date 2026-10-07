param(
  [Parameter(Mandatory = $true)][string]$ClientId,
  [Parameter(Mandatory = $true)][string]$ClientSecret,
  [string]$RedirectUri = 'http://localhost:8765/oauth2/callback',
  [switch]$NoBrowser
)

$ErrorActionPreference = 'Stop'
$callbackUri = [Uri]$RedirectUri
if ($callbackUri.Scheme -ne 'http' -or $callbackUri.Host -ne 'localhost' -or $callbackUri.Port -ne 8765 -or $callbackUri.AbsolutePath -ne '/oauth2/callback') {
  throw 'RedirectUri must be http://localhost:8765/oauth2/callback to match the Google OAuth client.'
}

$scope = 'https://www.googleapis.com/auth/youtube.upload'
$query = [ordered]@{
  client_id = $ClientId
  redirect_uri = $RedirectUri
  response_type = 'code'
  scope = $scope
  access_type = 'offline'
  prompt = 'consent'
  include_granted_scopes = 'true'
}
$authorizeUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' + (($query.GetEnumerator() | ForEach-Object {
  [Uri]::EscapeDataString($_.Key) + '=' + [Uri]::EscapeDataString([string]$_.Value)
}) -join '&')

$listener = [System.Net.HttpListener]::new()
$listener.Prefixes.Add('http://localhost:8765/oauth2/')
$listener.Start()
try {
  if ($NoBrowser) {
    Write-Host 'Open this URL in a browser to authorize Daily Breath YouTube uploads:'
    Write-Output $authorizeUrl
  } else {
    Write-Host 'Opening Google sign-in for the Daily Breath YouTube upload permission…'
    Start-Process $authorizeUrl
  }
  $pending = $listener.BeginGetContext($null, $null)
  if (-not $pending.AsyncWaitHandle.WaitOne([TimeSpan]::FromMinutes(5))) {
    throw 'Timed out waiting for the Google authorization callback.'
  }
  $context = $listener.EndGetContext($pending)
  $parameters = @{}
  foreach ($pair in ($context.Request.Url.Query.TrimStart('?') -split '&')) {
    if ($pair -eq '') { continue }
    $parts = $pair -split '=', 2
    $name = [Uri]::UnescapeDataString($parts[0])
    $value = if ($parts.Count -gt 1) { [Uri]::UnescapeDataString($parts[1].Replace('+', ' ')) } else { '' }
    $parameters[$name] = $value
  }
  $message = '<!doctype html><title>Daily Breath</title><h1>Daily Breath authorization complete</h1><p>You can close this tab and return to PowerShell.</p>'
  $bytes = [Text.Encoding]::UTF8.GetBytes($message)
  $context.Response.ContentType = 'text/html; charset=utf-8'
  $context.Response.ContentLength64 = $bytes.Length
  $context.Response.OutputStream.Write($bytes, 0, $bytes.Length)
  $context.Response.Close()
  if ($parameters['error']) { throw ('Google authorization failed: ' + $parameters['error']) }
  $code = [string]$parameters['code']
  if ([string]::IsNullOrWhiteSpace($code)) { throw 'Google did not return an authorization code.' }

  $token = Invoke-RestMethod -Method Post -Uri 'https://oauth2.googleapis.com/token' -ContentType 'application/x-www-form-urlencoded' -Body @{
    code = $code
    client_id = $ClientId
    client_secret = $ClientSecret
    redirect_uri = $RedirectUri
    grant_type = 'authorization_code'
  }
  if ([string]::IsNullOrWhiteSpace([string]$token.refresh_token)) {
    throw 'Google did not issue a refresh token. Remove Daily Breath access from your Google Account and run this command again.'
  }
  [pscustomobject]@{
    ClientId = $ClientId
    ClientSecret = (ConvertTo-SecureString $ClientSecret -AsPlainText -Force)
    RefreshToken = (ConvertTo-SecureString ([string]$token.refresh_token) -AsPlainText -Force)
    Scope = $scope
    AuthorizedAt = (Get-Date).ToString('o')
  } | Export-Clixml (Join-Path $PSScriptRoot 'youtube-oauth.xml')
  Write-Host 'YouTube authorization saved in youtube-oauth.xml for this Windows account.'
} finally {
  if ($listener.IsListening) { $listener.Stop() }
  $listener.Close()
}
