param(
  [string]$SourceDirectory = 'C:\Users\shpctac104ce\.codex\codex-remote-attachments\01a08c21-c352-7841-ab67-12f353a2e7b9\E35DF1BF-FC83-4185-8776-83BA92475058',
  [string]$OutputDirectory = 'C:\Users\shpctac104ce\Desktop\Beyond_OS\DailyBreathApple\AppStoreAssets\Screenshots\from-iPhone-14'
)

Add-Type -AssemblyName System.Drawing

function New-Canvas([int]$Width, [int]$Height) {
  # App Store Connect rejects screenshots encoded with an alpha channel, even
  # when every pixel is opaque. Force a 24-bit RGB bitmap before saving PNG.
  $bitmap = New-Object System.Drawing.Bitmap $Width, $Height, ([System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
  $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
  $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
  $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
  $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
  $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
  return @{ Bitmap = $bitmap; Graphics = $graphics }
}

$iphoneDirectory = Join-Path $OutputDirectory 'iPhone-6.5'
$ipadDirectory = Join-Path $OutputDirectory 'iPad-13'
New-Item -ItemType Directory -Force -Path $iphoneDirectory, $ipadDirectory | Out-Null

$photos = Get-ChildItem $SourceDirectory -Filter '*-Photo-*.jpg' | Sort-Object Name
foreach ($photo in $photos) {
  $source = [System.Drawing.Image]::FromFile($photo.FullName)
  $baseName = [System.IO.Path]::GetFileNameWithoutExtension($photo.Name)

  # 6.5-inch iPhone: scale by width and crop the single-pixel aspect-ratio excess.
  $iphone = New-Canvas 1242 2688
  $scaledHeight = [int][Math]::Ceiling($source.Height * (1242.0 / $source.Width))
  $sourceRect = New-Object System.Drawing.Rectangle 0, 1, $source.Width, ($source.Height - 1)
  $destRect = New-Object System.Drawing.Rectangle 0, 0, 1242, 2688
  $iphone.Graphics.DrawImage($source, $destRect, $sourceRect, [System.Drawing.GraphicsUnit]::Pixel)
  $iphone.Bitmap.Save((Join-Path $iphoneDirectory "$baseName.png"), [System.Drawing.Imaging.ImageFormat]::Png)
  $iphone.Graphics.Dispose(); $iphone.Bitmap.Dispose()

  # 13-inch iPad: retain the full phone capture at iPad height and place it on a
  # neutral, app-store-safe canvas. This avoids stretching phone UI across the iPad layout.
  $ipad = New-Canvas 2064 2752
  $ipad.Graphics.Clear([System.Drawing.Color]::FromArgb(18, 31, 43))
  $scaledWidth = [int][Math]::Round($source.Width * (2752.0 / $source.Height))
  $left = [int][Math]::Floor((2064 - $scaledWidth) / 2.0)
  $ipad.Graphics.DrawImage($source, (New-Object System.Drawing.Rectangle $left, 0, $scaledWidth, 2752))
  $ipad.Bitmap.Save((Join-Path $ipadDirectory "$baseName.png"), [System.Drawing.Imaging.ImageFormat]::Png)
  $ipad.Graphics.Dispose(); $ipad.Bitmap.Dispose()
  $source.Dispose()
}
