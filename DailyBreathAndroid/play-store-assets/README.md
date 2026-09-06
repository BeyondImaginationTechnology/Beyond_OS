# DailyBreath Google Play assets

Upload the files as follows:

- `common/dailybreath-app-icon-512.png` — App icon
- `common/dailybreath-feature-graphic-1024x500.png` — Feature graphic
- `phone/*.png` — Phone screenshots, in numbered order
- `tablet/*.png` — Tablet screenshots, in numbered order (optional but recommended)

The generated phone screenshots are 1242 × 2484 (2:1), and the tablet
screenshots are 2064 × 2752. All screenshots are opaque 24-bit PNG files.

Regenerate the set with:

```powershell
python DailyBreathAndroid/scripts/build_play_store_assets.py
```
