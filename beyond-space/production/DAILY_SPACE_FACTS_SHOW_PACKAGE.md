# Beyond Space TV: Daily Space Facts

## Reusable opener

`BeyondSpaceTV_DailySpaceFacts_Intro_10sec.mp4` is the reusable 10-second vertical opener. It uses original show artwork, a custom synthesized instrumental motif, playful Comic Sans MS lettering, and text directly over the artwork without text panels.

The spoken tag matches the screen copy: “Beyond Space TV! Daily Space Facts! Look up — let wonder begin!” Its lines are separated to create a more deliberate welcome. The approved ElevenLabs take uses “Oliver - Playful, Vibrant and Optimistic” and runs about 5.20 seconds. The source MP3 is `BeyondSpaceTV_intro_narration_oliver_2026-09-27.mp3`; the production WAV is a 48 kHz stereo conversion used by the render script. The previous system-voice WAV is preserved as `daily-space-facts-theme-voice-previous.wav`.

`daily-space-facts-theme-lyrics.txt` contains the spoken tag and delivery notes. `daily-space-facts-theme-original.wav` is the instrumental bed. The script creates the instrumental again when run and uses the narration WAV already in this folder.

## 60-second Pluto episode

BeyondSpaceTV_Pluto_Episode_01_60sec_vertical.mp4 combines the 10-second opener and 50-second vertical episode. BeyondSpaceTV_Pluto_Episode_01_60sec_square.mp4 does the same for the square format. Both run 60 seconds. The source and production credits are in BeyondSpaceTV_Pluto_Episode_01_caption.txt for the post caption, so the credits card is not appended to the episode.

## Source credits

`BeyondSpaceTV_DailySpaceFacts_Credits_Episode_1_55_pluto.mp4` is an 8-second Pluto example credits video with sources over the space artwork and no text panels.

To make a credits card for another episode:

1. Copy `daily-space-facts-credits-template.json` and replace the episode number, fact title, and source title/URL fields.
2. Run `render_daily_space_facts_package.py --credits-json PATH_TO_YOUR_JSON`.
3. Set `--credits-seconds` if the source list needs more reading time.

The generated videos are 1080×1920 H.264/AAC, 25 fps. The opener and credits are separate assets so the series can reuse the same intro and append each episode's own source list.

## Episode 1/55 source record

See `episode-01-pluto-sources.json`. The linked NASA pages support the Pluto year, five known moons, and the Pluto–Charon shared-center orbital description.

