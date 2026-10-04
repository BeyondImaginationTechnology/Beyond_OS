# Daily Breath TV devotional cron

Run the existing Daily Studio worker hourly in the `America/Vancouver` timezone:

```text
15 * * * * /usr/bin/php /path/to/Beyond_OS/server/cron/daily-studio-daily.php
```

The worker performs three independent jobs:

1. It renders the daily guided breathing video.
2. It renders three 57-second daily reading segments: Bible Verse, Tanakh Passage, and Quran Ayah of the Day.
3. It renders the Daily Breath TV devotional from approved English Bible content.

The devotional worker uses the approved Daily Content record first. If there is no approved record, it uses the existing published devotional and local Verse of the Day. Draft content is never rendered.

The daily reading segments and devotional are written to:

```text
dailybreath/assets/videos/daily-breath-tv/YYYY-MM-DD-bible-verse-of-the-day.mp4
dailybreath/assets/videos/daily-breath-tv/YYYY-MM-DD-torah-verse-of-the-day.mp4
dailybreath/assets/videos/daily-breath-tv/YYYY-MM-DD-quran-verse-of-the-day.mp4
dailybreath/assets/videos/daily-breath-tv/YYYY-MM-DD-bible-devotional.mp4
```

When a render succeeds, `dailybreath/assets/videos/daily-breath-tv/rotation.json` is atomically updated with the current episode. The Bible, Tanakh, and Quran workers use their respective daily reading, label, source citation, palette, language, and ElevenLabs voice. Each worker reuses saved daily narration when it is already available. Otherwise it creates the MP3 through the private ElevenLabs configuration, saves it under `dailybreath/assets/audio`, and stages a nonpublic copy only for Remotion rendering. The hourly cadence lets an editor publish or revise a reading during the day; the worker rerenders when the approved content’s `updated_at` is newer than the MP4.

The production host needs Node.js, the Remotion dependencies in `tools/daily-stencil-video`, PHP CLI, `proc_open`, permission to write the Daily Breath TV asset directory, and private ElevenLabs configuration for `narration.elevenlabs.api_key` plus the `en-US`, `he-IL`, and `ar-SA` voice IDs in `config/live.php`.
