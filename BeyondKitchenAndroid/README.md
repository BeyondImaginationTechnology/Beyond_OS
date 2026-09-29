# Beyond Kitchen for Android

Native Java companion for Beyond Kitchen 0.0.1. The app bundles the shared
`beyond-kitchen/data/recipes.json` catalog and recipe photos through its Gradle asset source sets;
favorites are stored locally in `SharedPreferences`.
The daily pick has a five-card recipe carousel that works offline.

Build a debug APK with `gradlew.bat assembleDebug`. The project follows the
repository Android toolchain: Java 17, Android Gradle Plugin 9.3, and Android
SDK 37.
