param([switch]$InstallTask,[switch]$SkipYouTube,[string]$ContentDate='')
$ErrorActionPreference='Stop'
$root=Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$configPath=Join-Path $PSScriptRoot 'worker-secret.xml'
if(!(Test-Path $configPath)){throw "Create worker-secret.xml with the setup script before running."}
$config=Import-Clixml $configPath
$token=[System.Net.NetworkCredential]::new('', $config.Token).Password
$youtubeConfigPath=Join-Path $PSScriptRoot 'youtube-oauth.xml'
$youtubeConfig=$null
$publishYouTube=-not $SkipYouTube
if($publishYouTube){
  if(!(Test-Path $youtubeConfigPath)){throw 'Run authorize-youtube.ps1 before running the worker. Daily Breath now uploads each completed Beyond TV video to YouTube in the same run.'}
  $youtubeConfig=Import-Clixml $youtubeConfigPath
}
$headers=@{Authorization="Bearer $token"}
$base=$config.BaseUrl.TrimEnd('/')
$vancouver=[TimeZoneInfo]::FindSystemTimeZoneById('Pacific Standard Time')
if([string]::IsNullOrWhiteSpace($ContentDate)){
  $ContentDate=([TimeZoneInfo]::ConvertTime([DateTimeOffset]::UtcNow,$vancouver).Date.AddDays(1).ToString('yyyy-MM-dd'))
}
if($ContentDate -notmatch '^\d{4}-\d{2}-\d{2}$'){throw 'ContentDate must use YYYY-MM-DD.'}
$project=Join-Path $root 'tools\daily-stencil-video'
$remotion=Join-Path $project 'node_modules\.bin\remotion.cmd'
$chrisRenderer=Join-Path $PSScriptRoot 'render-chris-bible.ps1'
if(!(Test-Path $remotion)){throw 'Remotion is not installed locally.'}
function Send-WorkerVideo {
  param([string]$Uri,[string]$Token,[string]$VideoPath,[hashtable]$Fields)
  Add-Type -AssemblyName System.Net.Http
  $client=[System.Net.Http.HttpClient]::new()
  $client.DefaultRequestHeaders.Authorization=[System.Net.Http.Headers.AuthenticationHeaderValue]::new('Bearer',$Token)
  $content=[System.Net.Http.ByteArrayContent]::new([System.IO.File]::ReadAllBytes($VideoPath))
  $content.Headers.ContentType=[System.Net.Http.Headers.MediaTypeHeaderValue]::Parse('application/octet-stream')
  $metadata=[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes(($Fields | ConvertTo-Json -Compress)))
  $content.Headers.Add('X-DailyBreath-Metadata',$metadata)
  try {
    $response=$client.PostAsync($Uri,$content).GetAwaiter().GetResult()
    $body=$response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
    if(-not $response.IsSuccessStatusCode) { throw "Upload failed: $([int]$response.StatusCode) $body" }
  } finally {
    $content.Dispose(); $client.Dispose()
  }
}
function Get-WorkerNarration {
  param([string]$Base,[string]$AudioUrl,[hashtable]$Headers,[string]$Destination)
  if([string]::IsNullOrWhiteSpace($AudioUrl)){throw 'Worker narration URL was missing.'}
  $baseUri=[System.Uri]$Base
  $candidate=[System.Uri]::new($AudioUrl,[System.UriKind]::RelativeOrAbsolute)
  if($candidate.IsAbsoluteUri){
    if($candidate.Scheme -notin @('http','https') -or $candidate.Host -ne $baseUri.Host -or $candidate.Port -ne $baseUri.Port){throw 'Worker narration URL was not on the configured Daily Breath host.'}
    $downloadUri=$candidate
  }else{
    $downloadUri=[System.Uri]::new($baseUri,$AudioUrl)
  }
  $audioPath=[System.Uri]::UnescapeDataString($downloadUri.AbsolutePath)
  if($audioPath -notmatch '^/dailybreath/assets/audio/\d{4}/\d{2}/[A-Za-z0-9._-]+\.mp3$'){throw 'Worker narration URL was not a permitted Daily Breath MP3 URL.'}
  $folder=Split-Path -Parent $Destination
  if(!(Test-Path $folder)){New-Item -ItemType Directory -Path $folder -Force | Out-Null}
  Invoke-WebRequest -UseBasicParsing -Headers $Headers -Uri $downloadUri.AbsoluteUri -OutFile $Destination
  if(!(Test-Path $Destination) -or (Get-Item -LiteralPath $Destination).Length -lt 128){throw 'Worker narration download was invalid.'}
}
function New-AmbientDevotionalBed {
  param([string]$Destination,[int]$DurationSeconds=58)
  if(Test-Path $Destination){return}
  $folder=Split-Path -Parent $Destination
  if(!(Test-Path $folder)){New-Item -ItemType Directory -Path $folder -Force | Out-Null}
  $sampleRate=11025;$sampleCount=$sampleRate*$DurationSeconds;$dataBytes=$sampleCount*2
  $stream=[System.IO.File]::Open($Destination,[System.IO.FileMode]::Create,[System.IO.FileAccess]::Write)
  $writer=[System.IO.BinaryWriter]::new($stream)
  try{
    $writer.Write([Text.Encoding]::ASCII.GetBytes('RIFF'));$writer.Write([int](36+$dataBytes));$writer.Write([Text.Encoding]::ASCII.GetBytes('WAVEfmt '));$writer.Write([int]16);$writer.Write([int16]1);$writer.Write([int16]1);$writer.Write([int]$sampleRate);$writer.Write([int]($sampleRate*2));$writer.Write([int16]2);$writer.Write([int16]16);$writer.Write([Text.Encoding]::ASCII.GetBytes('data'));$writer.Write([int]$dataBytes)
    $roots=@(73.416,65.406,87.307,73.416)
    for($i=0;$i -lt $sampleCount;$i++){
      $time=$i/$sampleRate;$chord=[int]([math]::Floor($time/14)%$roots.Count);$root=$roots[$chord];$fade=[math]::Min(1,[math]::Min($time/3,($DurationSeconds-$time)/4));$pad=0.55*[math]::Sin(2*[math]::PI*$root*$time)+0.27*[math]::Sin(2*[math]::PI*($root*1.4983)*$time)+0.18*[math]::Sin(2*[math]::PI*($root*2)*$time);$pulse=[math]::Pow([math]::Max(0,[math]::Sin(2*[math]::PI*$time/8)),6);$bell=0.10*$pulse*[math]::Sin(2*[math]::PI*($root*4)*$time);$value=[math]::Max(-0.78,[math]::Min(0.78,($pad+$bell)*0.17*$fade));$writer.Write([int16]([math]::Round($value*32767)))
    }
  }finally{$writer.Dispose();$stream.Dispose()}
}
function Get-YouTubeAccessToken {
  param($Config)
  $clientSecret=[System.Net.NetworkCredential]::new('', $Config.ClientSecret).Password
  $refreshToken=[System.Net.NetworkCredential]::new('', $Config.RefreshToken).Password
  $response=Invoke-RestMethod -Method Post -Uri 'https://oauth2.googleapis.com/token' -ContentType 'application/x-www-form-urlencoded' -Body @{
    client_id=[string]$Config.ClientId
    client_secret=$clientSecret
    refresh_token=$refreshToken
    grant_type='refresh_token'
  }
  if([string]::IsNullOrWhiteSpace([string]$response.access_token)){throw 'Google did not return a YouTube access token.'}
  return [string]$response.access_token
}
function Publish-YouTubeVideo {
  param($Config,[string]$VideoPath,[string]$Title,[string]$Description)
  $accessToken=Get-YouTubeAccessToken -Config $Config
  $metadata=@{
    snippet=@{title=$Title;description=$Description;categoryId='22';tags=@('Daily Breath','Verse of the Day','Faith')}
    status=@{privacyStatus='private';selfDeclaredMadeForKids=$false}
  } | ConvertTo-Json -Depth 5 -Compress
  $fileSize=(Get-Item -LiteralPath $VideoPath).Length
  $headers=@{
    Authorization="Bearer $accessToken"
    'X-Upload-Content-Type'='video/mp4'
    'X-Upload-Content-Length'=[string]$fileSize
  }
  $session=Invoke-WebRequest -UseBasicParsing -Method Post -Uri 'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status' -Headers $headers -ContentType 'application/json; charset=UTF-8' -Body $metadata
  $uploadUri=[string]$session.Headers.Location
  if([string]::IsNullOrWhiteSpace($uploadUri)){throw 'YouTube did not provide an upload session.'}
  $bytes=[System.IO.File]::ReadAllBytes($VideoPath)
  $upload=Invoke-RestMethod -Method Put -Uri $uploadUri -Headers @{Authorization="Bearer $accessToken"} -ContentType 'video/mp4' -Body $bytes
  if([string]::IsNullOrWhiteSpace([string]$upload.id)){throw 'YouTube did not return a video ID.'}
  Write-Host "Uploaded private YouTube video: $($upload.id)"
}
Push-Location $project
try {
foreach($tradition in 'bible','torah','quran'){
  $payloadUri="$base/dailybreath/api/local-tv-worker.php?tradition=$tradition&date=$ContentDate"
  try {
    $payload=Invoke-RestMethod -Headers $headers -Uri $payloadUri
  } catch {
    $detail='No response body was returned.'
    $response=$_.Exception.Response
    if($response){
      try {
        $reader=[System.IO.StreamReader]::new($response.GetResponseStream())
        $body=$reader.ReadToEnd();$reader.Dispose()
        if(-not [string]::IsNullOrWhiteSpace($body)){$detail=$body}
      } catch { $detail=$_.Exception.Message }
      throw "Daily Breath worker request failed for $tradition (HTTP $([int]$response.StatusCode)): $detail"
    }
    throw "Daily Breath worker request failed for ${tradition}: $($_.Exception.Message)"
  }
  if(!$payload.ok){throw "Payload failed for $tradition"}
  $audioSegments=@();$hasNarration=$false
  if(-not [string]::IsNullOrWhiteSpace([string]$payload.audio_url)){
    $audioRelative=('generated/dailybreath/{0}-{1}-{2}.mp3' -f $payload.date,$tradition,$payload.voice_id)
    $audioPath=Join-Path $project ('public\'+$audioRelative.Replace('/','\'))
    Get-WorkerNarration -Base $base -AudioUrl ([string]$payload.audio_url) -Headers $headers -Destination $audioPath
    $audioSegments=@(@{audioFile=$audioRelative;startSeconds=0});$hasNarration=$true
  }else{
    $ambientRelative='generated/dailybreath/daily-breath-ambient-devotional.wav'
    New-AmbientDevotionalBed -Destination (Join-Path $project ('public\'+$ambientRelative.Replace('/','\')))
    $audioSegments=@(@{audioFile=$ambientRelative;startSeconds=0;volume=0.34})
    Write-Host "Using original ambient devotional music for $tradition."
  }
  $props=@{brand='Daily Breath';series=$payload.series;title=($payload.kind+' of the Day');subtitle=($payload.reference+' Â· '+$payload.label);direction=$payload.direction;guideName=$payload.guide_name;audioSegments=$audioSegments;beats=@(@{id='intro';label='Daily Breath';startSeconds=0;durationSeconds=7;narration=('Here is today''s '+$payload.kind+'.');onScreenText=$payload.reference;visualPrompt='Opening'},@{id='reading';label=($payload.kind+' of the Day');startSeconds=7;durationSeconds=20;narration=$payload.passage;onScreenText=$payload.passage;visualPrompt='Reading'},@{id='reflection';label='Reflect';startSeconds=27;durationSeconds=12;narration='Carry these words with you today.';onScreenText='One reading. One breath.';visualPrompt='Reflection'},@{id='outro';label='Daily Breath';startSeconds=39;durationSeconds=7;narration='This has been Daily Breath.';onScreenText='Return whenever you need a breath.';visualPrompt='Close'});sourceSeconds=5;outroSeconds=6;sources=@(@{citation=$payload.reference;url='https://beyondimagination.co.technology/dailybreath/';notes='Daily Breath approved reading.'});outroText='Carry this reading with you.';fps=30;width=1920;height=1080;palette=@{background='#10271F';foreground='#FFFDF7';accent='#E2BC63';muted='#DDE4D7'}}
  $propsFile=Join-Path $env:TEMP ("dailybreath-$tradition.json");$output=Join-Path $env:TEMP ("$($payload.date)-$tradition-verse-of-the-day.mp4")
  [System.IO.File]::WriteAllText($propsFile, ($props | ConvertTo-Json -Depth 8), [System.Text.UTF8Encoding]::new($false))
  if($tradition -eq 'bible' -and $hasNarration -and (Test-Path -LiteralPath $chrisRenderer)){
    Write-Host "Rendering Chris with Prayan's Daily Breath narration."
    & $chrisRenderer -AudioPath $audioPath -OutputPath $output -Reference $payload.reference -Passage $payload.passage
  }else{
    & $remotion render src/index.ts DailyBreathStory $output "--props=$propsFile" --codec=h264 --concurrency=2
  }
  if($LASTEXITCODE -ne 0){throw "Render failed for $tradition"}
  # Upload endpoint is enabled with the production deploy; do not expose token in URLs.
  $voiceover=if($hasNarration){if($payload.guide_name){"$($payload.guide_name) Â· ElevenLabs narration"}else{'ElevenLabs narration'}}else{'Original ambient devotional music Â· captioned reading'}
  Send-WorkerVideo -Uri "$base/dailybreath/api/local-tv-worker-upload.php" -Token $token -VideoPath $output -Fields @{tradition=$tradition;date=$payload.date;reference=$payload.reference;title=($payload.kind+' of the Day Â· '+$payload.reference);voiceover=$voiceover}
  if($publishYouTube){
    $title=("Daily Breath Â· {0} Â· {1}" -f $payload.kind,$payload.reference)
    $description=("{0}`n`n{1}`n`nDaily Breath Â· Faith-centered wellness" -f $payload.reference,$payload.passage)
    Publish-YouTubeVideo -Config $youtubeConfig -VideoPath $output -Title $title -Description $description
  }
}
}
finally {
  Pop-Location
}
