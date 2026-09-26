"""Render Home boot artwork from the Core jaguar mark; requires Pillow and NumPy."""
import argparse
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont

parser = argparse.ArgumentParser()
parser.add_argument("--font", required=True, type=Path)
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
(root / "assets").mkdir(exist_ok=True)
core_mark = root.parent / "core/assets/bit-os-core-logo-v0.2.png"
home_mark = root / "assets/bit-os-home-logo-v0.1.png"

# Preserve the Core mark except for its blue light. Silver metal and black
# jaguar detail stay unchanged while blue/cyan light becomes emerald green.
hsv = np.array(Image.open(core_mark).convert("RGB").convert("HSV"))
hue, saturation, value = hsv[:, :, 0], hsv[:, :, 1], hsv[:, :, 2]
blue_light = (hue >= 115) & (hue <= 190) & (saturation >= 35) & (value >= 55)
hue[blue_light] = np.clip(hue[blue_light].astype(np.int16) - 55, 0, 255).astype(np.uint8)
mark = Image.fromarray(hsv, "HSV").convert("RGBA")
# The Core source has an opaque square backdrop. Mask just outside its
# hexagonal frame so the mark blends into the boot screen without a tile.
mask = Image.new("L", mark.size, 0)
mask_draw = ImageDraw.Draw(mask)
w, h = mark.size
mask_draw.polygon([(round(x * w), round(y * h)) for x, y in
                   ((.5, .07), (.865, .265), (.865, .65),
                    (.5, .86), (.135, .65), (.135, .265))], fill=255)
mark.putalpha(mask.filter(ImageFilter.GaussianBlur(3)))
mark.save(home_mark)

scale = 3
im = Image.new("RGB", (640 * scale, 360 * scale), (9, 13, 22))
d = ImageDraw.Draw(im)

def xy(x, y):
    return round(x * scale), round(y * scale)

logo_size = 150 * scale
mark.thumbnail((logo_size, logo_size), Image.Resampling.LANCZOS)
im.paste(mark, ((640 * scale - mark.width) // 2, 24 * scale), mark)

def text(value, y, size, color):
    font = ImageFont.truetype(str(args.font), size * scale)
    d.text(xy(320, y), value, font=font, fill=color, anchor="mt")
text("Beyond Imagination OS", 193, 29, (245, 247, 255))
text("HOME EDITION  v0.1", 250, 12, (163, 175, 200))
for index in range(3):
    x = 307 + index * 13
    d.ellipse([xy(x - 2, 309), xy(x + 2, 313)], fill=(79, 185 + index * 18, 147))
im = im.resize((640, 360), Image.Resampling.LANCZOS)
im.save(root / "assets/boot.ppm")
preview = Image.new("RGB", (1440, 900), (9, 13, 22))
preview.paste(im, ((1440 - 640) // 2, (900 - 360) // 2))
preview.save(root / "assets/boot-preview.png")
print("Rendered Home jaguar mark, boot.ppm and boot-preview.png")
