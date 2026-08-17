# Beyond French 1.1.2

Native iOS polish build focused on daily lesson clarity and voice playback.

## Changes

- Removes the Today screen metrics row that showed dictionary words, module count, and correct practice count.
- Adds speaker controls to each Today language card for French, Kreyol, Patois, and Spanish.
- Improves local voice playback with spoken-audio session handling, higher-quality device voice selection, and language-specific fallbacks.
- Clears answer fields after correct responses and adds smoother animated transitions into the next practice prompt or Academy lesson.
- Expands themes beyond accent colors with distinct page and card backgrounds for Classic, Ocean, Sunrise, and Garden.
- Updates native app metadata to marketing version 1.1.2, build 2.

## Notes

Azure-hosted narration should stay server-side so Speech keys are never shipped in the iOS app. This build keeps Apple speech as the native fallback while preparing the UI for richer generated audio.
