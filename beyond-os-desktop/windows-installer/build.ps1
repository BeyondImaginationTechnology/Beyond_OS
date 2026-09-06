$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$out = Join-Path $root 'dist'
New-Item -ItemType Directory -Force -Path $out | Out-Null
$csc = Join-Path $env:WINDIR 'Microsoft.NET\Framework64\v4.0.30319\csc.exe'
if (-not (Test-Path $csc)) { throw 'Windows .NET Framework C# compiler was not found.' }
$args = @('/nologo','/target:winexe','/platform:anycpu','/optimize+',('/out:' + (Join-Path $out 'BITOSInstaller.exe')),('/win32manifest:' + (Join-Path $root 'app.manifest')),'/r:System.Windows.Forms.dll','/r:System.Drawing.dll','/r:System.Management.dll',(Join-Path $root 'Program.cs'))
& $csc @args
if ($LASTEXITCODE -ne 0) { throw 'Compiler failed.' }
Get-Item (Join-Path $out 'BITOSInstaller.exe') | Select-Object FullName,Length,LastWriteTime