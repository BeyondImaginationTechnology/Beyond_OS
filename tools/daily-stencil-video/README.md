# Beyond Tattoo Daily Stencil Pack Video

Reusable Remotion Reel: 10 seconds, 1080×1920, 60fps.

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
