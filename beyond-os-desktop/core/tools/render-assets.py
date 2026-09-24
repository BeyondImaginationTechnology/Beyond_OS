"""Render the Beyond Imagination OS startup artwork; requires Pillow.

The startup mark is the Core v0.2 shield from ``assets``. No font
files are shipped.
"""
import argparse
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

parser = argparse.ArgumentParser()
parser.add_argument("--font", required=True, type=Path)
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
(root / "assets").mkdir(exist_ok=True)
scale = 3
im = Image.new("RGB", (640 * scale, 360 * scale), (9, 13, 22))
d = ImageDraw.Draw(im)

def xy(x, y):
    return round(x * scale), round(y * scale)

asset = Image.open(root / "assets/bit-os-core-logo-v0.2.png").convert("RGBA")
logo_size = 150 * scale
asset.thumbnail((logo_size, logo_size), Image.Resampling.LANCZOS)
logo_x = (640 * scale - asset.width) // 2
logo_y = 24 * scale
im.paste(asset, (logo_x, logo_y), asset)

def text(value, y, size, color):
    font = ImageFont.truetype(str(args.font), size * scale)
    d.text(xy(320, y), value, font=font, fill=color, anchor="mt")
text("Beyond Imagination OS", 193, 29, (245, 247, 255))
text("CORE EDITION  v0.2", 250, 12, (163, 175, 200))
for index in range(3):
    x = 307 + index * 13
    d.ellipse([xy(x - 2, 309), xy(x + 2, 313)], fill=(115 + index * 25, 142, 230))
im = im.resize((640, 360), Image.Resampling.LANCZOS)
im.save(root / "assets/boot.ppm")
preview = Image.new("RGB", (1440, 900), (9, 13, 22))
preview.paste(im, ((1440 - 640) // 2, (900 - 360) // 2))
preview.save(root / "assets/boot-preview.png")
print("Rendered boot.ppm and boot-preview.png")
