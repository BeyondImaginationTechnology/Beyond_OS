# Boot artwork

The startup screen uses the orbital gateway already established in
`assets/images/bos-logo-mark.svg` at the repository root, with Beyond OS Home
v0.1 typography. `boot.ppm` is the actual RGB framebuffer asset.
`boot-preview.png` shows it centered on a 1440 × 900 display; it is an artwork
preview, not evidence of a booted VM. The three dots are decorative, not a
reported progress value.

Regenerate with Pillow and an installed sans-serif font:

```sh
python3 tools/render-assets.py --font /usr/share/fonts/truetype/dejavu/DejaVuSans.ttf
```

The current raster was rendered using the locally installed Segoe UI font.
Font binaries are not included. Artwork follows the repository's
`CONTENT_RIGHTS.md`; source code follows `LICENSE`.


`home-preview.png` shows the earlier Home prototype. A v0.1 dashboard capture
will be recorded after the new native shell has compiled and booted in the VM.
