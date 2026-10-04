param([switch]$InstallTask)
$ErrorActionPreference='Stop'
$root=Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$configPath=Join-Path $PSScriptRoot 'worker-secret.xml'
if(!(Test-Path $configPath)){throw "Create worker-secret.xml with the setup script before running."}
$config=Import-Clixml $configPath
$token=[System.Net.NetworkCredential]::new('', $config.Token).Password
$headers=@{Authorization="Bearer $token"}
$base=$config.BaseUrl.TrimEnd('/')
$project=Join-Path $root 'tools\daily-stencil-video'
$remotion=Join-Path $project 'node_modules\.bin\remotion.cmd'
if(!(Test-Path $remotion)){throw 'Remotion is not installed locally.'}
foreach($tradition in 'bible','torah','quran'){
  $payload=Invoke-RestMethod -Headers $headers -Uri "$base/dailybreath/api/local-tv-worker.php?tradition=$tradition"
  if(!$payload.ok){throw "Payload failed for $tradition"}
  $props=@{brand='Daily Breath';series=$payload.series;title=($payload.kind+' of the Day');subtitle=($payload.reference+' · '+$payload.label);direction=$payload.direction;beats=@(@{id='intro';label='Daily Breath';startSeconds=0;durationSeconds=7;narration=('Here is today''s '+$payload.kind+'.');onScreenText=$payload.reference;visualPrompt='Opening'},@{id='reading';label=($payload.kind+' of the Day');startSeconds=7;durationSeconds=20;narration=$payload.passage;onScreenText=$payload.passage;visualPrompt='Reading'},@{id='reflection';label='Reflect';startSeconds=27;durationSeconds=12;narration='Carry these words with you today.';onScreenText='One reading. One breath.';visualPrompt='Reflection'},@{id='outro';label='Daily Breath';startSeconds=39;durationSeconds=7;narration='This has been Daily Breath.';onScreenText='Return whenever you need a breath.';visualPrompt='Close'});sourceSeconds=5;outroSeconds=6;sources=@(@{citation=$payload.reference;url='https://beyondimagination.co.technology/dailybreath/';notes='Daily Breath approved reading.'});outroText='Carry this reading with you.';fps=30;width=1920;height=1080;palette=@{background='#10271F';foreground='#FFFDF7';accent='#E2BC63';muted='#DDE4D7'}}
  $propsFile=Join-Path $env:TEMP ("dailybreath-$tradition.json");$output=Join-Path $env:TEMP ("$($payload.date)-$tradition-verse-of-the-day.mp4")
  $props|ConvertTo-Json -Depth 8|Set-Content -Encoding utf8 $propsFile
  & $remotion render src/index.ts DailyBreathStory $output "--props=$propsFile" --codec=h264 --concurrency=2
  if($LASTEXITCODE -ne 0){throw "Render failed for $tradition"}
  # Upload endpoint is enabled with the production deploy; do not expose token in URLs.
  Invoke-RestMethod -Method Post -Headers $headers -Uri "$base/dailybreath/api/local-tv-worker-upload.php" -Form @{video=Get-Item $output;tradition=$tradition;date=$payload.date;reference=$payload.reference;title=($payload.kind+' of the Day · '+$payload.reference)} | Out-Null
}