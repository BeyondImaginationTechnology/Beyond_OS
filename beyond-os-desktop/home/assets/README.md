# Boot artwork

The startup screen matches Core's jaguar-eye shield, layout, and title. Home
uses an emerald green light variant of Core's blue mark, saved as
`bit-os-home-logo-v0.2.png`, with Home v0.2 edition typography. `boot.ppm` is
the actual RGB framebuffer asset.
`boot-preview.png` shows it centered on a 1440 × 900 display; it is an artwork
preview, not evidence of a booted VM. The three dots are decorative, not a
reported progress value.

Regenerate with Pillow and an installed sans-serif font:

```sh
python3 tools/render-assets.py --font /usr/share/fonts/truetype/dejavu/DejaVuSans.ttf
```

Rendering requires Pillow and NumPy and reads Core's
`assets/bit-os-core-logo-v0.2.png`. The current raster was rendered using the
locally installed Segoe UI font.
Font binaries are not included. Artwork follows the repository's
`CONTENT_RIGHTS.md`; source code follows `LICENSE`.


`wallpaper-home-movie-night-v0.2.png` is the generated 1586 × 992 source for
the default, people-free movie-night living room. It leaves a quiet wall area
for desktop icons and includes a couch, soft blanket, popcorn, leafy plants,
and a gentle television glow. The runtime image is its 1280 × 800
`wallpaper-home-movie-night-v0.2.bmp` build asset.

`wallpaper-home-v0.1.png` and `wallpaper-home-v0.1.bmp` preserve the optional
Amazon rainforest canopy and river at sunrise. The Start menu's Wallpaper
choice previews both backgrounds and saves the selected scene in
`~/.config/beyond-home/wallpaper`. Set `BEYOND_WALLPAPER` to a BMP path for a
temporary developer preview.

`home-preview.png` is an earlier Home prototype capture. It does not show this
v0.1 wallpaper or dashboard.
