@echo off
setlocal

set "KEYSTORE=%~dp0DailyBreathAndroid\app\upload-keystore.jks"
set "ALIAS=daily-breath-upload"

if exist "%KEYSTORE%" (
  echo Keystore already exists: "%KEYSTORE%"
  exit /b 1
)

where keytool.exe >nul 2>&1
if errorlevel 1 (
  echo keytool.exe was not found on PATH. Install a JDK or add its bin folder to PATH.
  exit /b 1
)

echo Creating "%KEYSTORE%"
echo Use a strong password and keep it in a password manager.
keytool.exe -genkeypair -v -keystore "%KEYSTORE%" -storetype JKS -keyalg RSA -keysize 2048 -validity 10000 -alias "%ALIAS%"

if errorlevel 1 exit /b 1
echo Created "%KEYSTORE%"
echo Alias: %ALIAS%
