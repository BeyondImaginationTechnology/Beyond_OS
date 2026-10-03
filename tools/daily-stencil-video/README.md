# Beyond Tattoo Daily Stencil Pack Video

Reusable Remotion Reel: 10 seconds, 1080×1080, 60fps, sized for Instagram feed posts.

Change only `public/daily-stencil.json` and the two referenced images each day:
- Main stencil artwork
- Studio transfer/template image
- Collection name
- Stencil title
- Date
- Suggested placement
- Download URL / QR code
- Optional caption/audio

Branding, Atomic Bit watermark, transitions, timing and layout remain locked.

## Commands
```bash
npm install
npm run studio
npm run still
npm run render
```

The hosted Daily Studio renders MP4s in the browser, so its PHP server does
not need Node, Chromium, or FFmpeg. Rebuild the committed browser bundle after
changing this composition:

```bash
npm run build:browser
```

The bundle is written to
`server/admin/daily-studio/assets/beyond-tattoo-remotion-renderer.js`.
Output: `out/daily-stencil-pack.mp4`

## Daily Breath video

`public/daily-breath-video.json` is the source of truth for the daily guided
breathing video: copy, phase durations and animation scales, cycle count,
palette, dimensions, frame rate, and publishing timezone. The Remotion
composition reads this file directly. Preview or render it locally with:

```bash
npm run render:breath
```

### Daily Breath 60-second story

The Remotion `DailyBreathStory` composition uses a fixed 60-second, 1080×1920,
30 FPS timeline. Its six editable narrative beats occupy 0:00–0:50; source
credits run 0:50–0:55 and the branded Daily Breath outro runs 0:55–1:00.
`public/daily-breath-story.json` is a complete example/default props file and
can be rendered locally with:

```bash
npm run render:story
```

In Daily Studio, open DailyBreath → **60-second Story Builder**. Enter a topic
and up to four source blocks in `citation | URL | excerpt or notes` format.
Generation uses only the supplied excerpt/notes (URLs are credited but not
fetched), returns the beat narration, concise screen text, and visual-production
prompts, and requires an editor to review the draft before downloading the
story brief or rendering an MP4. The Remotion export is a motion-graphics
template; visual prompts are provided for a separate image-production step and
are not represented as generated images in the exported video.

The existing `server/cron/daily-studio-daily.php` worker also renders one video
for the current date in the configured timezone. It saves the public MP4 at
`/dailybreath/assets/videos/breathing/YYYY-MM-DD.mp4` and atomically updates
`/dailybreath/assets/videos/breathing/latest.json` with its URL and duration.
The dated file is left untouched on repeat runs unless the JSON configuration
has changed; a private lock prevents concurrent renders. The output directory
is ignored by Git.

On the production host, install Node.js and this project's production
dependencies in the checked-out Remotion project:

```bash
cd tools/daily-stencil-video
npm ci --omit=dev
```

Ensure PHP CLI has `proc_open` enabled and can execute
`node_modules/.bin/remotion`. Configure the hosting control panel to run the
existing daily worker once after midnight in the desired publishing timezone;
the scheduler's local timezone must match the JSON `timezone`, and its `PATH`
must include the Node.js executable. The worker's existing `BEYOND_VAR_PATH`
private-storage configuration is used for its render lock. Remotion must be
able to launch its browser renderer and write to the public Daily Breath assets
directory. Failed renders are logged and reported to standard error; they do
not suppress the worker's existing schedule report.
