# Beyond French - African Expansion - Remotion Promo

Real Remotion project for your Egyptian Night poster.

## Install
```bash
npm install
```

## Preview in studio
```bash
npm start
# opens http://localhost:3000 with 2 compositions:
# - AfricanExpansion (1080x1920 vertical Reels)
# - AfricanExpansionFeed (1080x1350 feed)
```

## Render MP4
```bash
# Reels vertical
npx remotion render src/index.ts AfricanExpansion out/africa-reels.mp4 --codec=h264

# Feed
npx remotion render src/index.ts AfricanExpansionFeed out/africa-feed.mp4 --codec=h264

# With your poster as background (put egyptian_night_poster.webp in public/)
# Replace the background div with <Img src={staticFile('egyptian_night_poster.webp')} />
```

## Customize
- Edit `src/AfricanExpansion.tsx` languages array for Lingala, Darija, Masri, Swahili
- Change flags, colors, icons
- Add audio: import {Audio} and <Audio src={staticFile('music.mp3')} />
- The cards use spring() for true Remotion physics - not fake CSS

## Export for Insta
- Codec h264, 30fps
- Reels: 1080x1920
- Feed: 1080x1350
