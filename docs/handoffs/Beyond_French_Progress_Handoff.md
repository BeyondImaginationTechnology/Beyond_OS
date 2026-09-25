# Beyond French — Deployment Cleanup Handoff

**Status:** Paused at deployed-server inspection due to usage-limit reset  
**Intended collaborator:** rosiergreg@gmail.com

## Resume point

Codex identified two deployed copies of Beyond French:

- Web app: `beyond/beyond-french`
- iOS bundle: `beyond/BeyondFrenchApple`

The server file listing was repaired, and inspection had reached the deployed audio directories. The visible web-app location was:

`beyond/beyond-french/assets/audio`

Its visible subfolders included `2026`, `french`, `lessons`, and a multilingual folder.

## Next action

1. Finish inspecting the audio/media subdirectories in both deployed copies.
2. Verify each selection before deletion.
3. Delete only deployed `.mp3` files and clearly unrelated media that belong to this cleanup.
4. Leave application code, configuration, directories, and all other assets untouched.
5. Confirm the two deployed copies still load correctly after cleanup.

## Safety constraint

Do not bulk-delete the audio directories. Select only verified deployed `.mp3` or unrelated media files. If ownership or purpose is uncertain, leave the file in place for review.

## Source context

This handoff was reconstructed from the saved progress message and screenshot captured on August 28, 2026.
