# French audio asset regeneration

`tools/regenerate-french-assets.mjs` is the single batch entry point for the
Beyond French web app, BeyondFrenchApple, FrenchQuestApple, and the reviewed
Euro-African expansion.

The batch is intentionally fail-closed:

- `ELEVENLABS_API_KEY` is supplied only through the shell and is never written
  to the repository.
- `ELEVENLABS_COMMERCIAL_LICENSE=1` is required as an explicit acknowledgement
  that the current ElevenLabs account permits commercial use.
- The saved Studio voice (`hpp4J3VqNfWAUOO0d1Us`) is used for French and
  Spanish unless overridden. **Wesly** (`PEjMkBhSB6492eADs4Ew`) is used for
  Haitian Kreyòl and Lingala, and **Nicole – Rich and Expressive**
  (`mrDMz4sYNCz18XYFpmyV`) for Jamaican Patois. Override any locale with its
  `ELEVENLABS_VOICE_*` variable when a different approved speaker is required.
- Euro-African tracks additionally require `ELEVENLABS_VOICE_AR_MA`,
  `ELEVENLABS_VOICE_AR_EG`, and `ELEVENLABS_VOICE_SW_KE`.

Preview the scope without writing files:

```powershell
$env:ELEVENLABS_COMMERCIAL_LICENSE = '1'
# Optional overrides for the saved Studio voices:
# $env:ELEVENLABS_VOICE_FR_FR = '<French voice id>'
# $env:ELEVENLABS_VOICE_ES_ES = '<native Spanish voice id>'
& 'C:\Users\fresh\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe' tools/regenerate-french-assets.mjs --scope=lessons,quest --dry-run
```

Generate the selected scope after setting the API key and all required voice
IDs:

```powershell
& 'C:\Users\fresh\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe' tools/regenerate-french-assets.mjs --scope=lessons,quest,dictionary
```

Use `--scope=africa` only after `beyond-french/data/africa-expansion.json`
contains records marked `native_reviewed: true` and the Arabic/Swahili voice
IDs are set. The script writes a provenance manifest to
`docs/french-assets-commercial-manifest.json` after a successful run.

The manifest records provider, model, voice IDs, output format, and the
commercial acknowledgement. It does not itself grant rights; retain the
provider terms and any voice-specific permissions with the release record.
