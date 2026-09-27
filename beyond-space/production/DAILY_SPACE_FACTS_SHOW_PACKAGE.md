# Beyond Space TV: Daily Space Facts

## Reusable opener

`BeyondSpaceTV_DailySpaceFacts_Intro_10sec.mp4` is the reusable 10-second vertical opener. It uses original show artwork, a custom synthesized instrumental motif, playful Comic Sans MS lettering, and text directly over the artwork without text panels. The ElevenLabs voice selected is “Liam - Viral Short-Form Storyteller,” with the spoken tag “Beyond Space TV! Daily Space Facts — let's explore!” The new audio is generated in the ElevenLabs account; download it as `daily-space-facts-theme-voice.wav` before finalizing the opener. The checked-in/local WAV is still the previous system-voice render until replaced.

`daily-space-facts-theme-lyrics.txt` contains the spoken tag. `daily-space-facts-theme-original.wav` is the instrumental bed. The script creates the instrumental again when run and uses the narration WAV already in this folder.

## Source credits

`BeyondSpaceTV_DailySpaceFacts_Credits_Episode_1_55_pluto.mp4` is an 8-second Pluto example credits video with sources over the space artwork and no text panels.

To make a credits card for another episode:

1. Copy `daily-space-facts-credits-template.json` and replace the episode number, fact title, and source title/URL fields.
2. Run `render_daily_space_facts_package.py --credits-json PATH_TO_YOUR_JSON`.
3. Set `--credits-seconds` if the source list needs more reading time.

The generated videos are 1080×1920 H.264/AAC, 25 fps. The opener and credits are separate assets so the series can reuse the same intro and append each episode's own source list.

## Episode 1/55 source record

See `episode-01-pluto-sources.json`. The linked NASA pages support the Pluto year, five known moons, and the Pluto–Charon shared-center orbital description.
