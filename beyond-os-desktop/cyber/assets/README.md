# Boot artwork

The startup screen uses the orbital gateway already established in
`assets/images/bos-logo-mark.svg` at the repository root, with BIT OS Cyber
Edition 1.0 typography. `boot.ppm` is the actual RGB framebuffer asset.
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


`home-preview.png` was captured from the compiled SDL2 desktop running in hidden
screenshot mode on Windows. It demonstrates the native renderer, not a booted
Linux image. Temporary development binaries are not included in this folder.
