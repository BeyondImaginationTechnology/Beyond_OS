"""Render the Beyond orbital mark and startup title; requires Pillow.
The geometry extends assets/images/bos-logo-mark.svg. No font files are shipped.
"""
import argparse
import math
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

# Original Beyond orbital gateway, enlarged from the repository's vector mark.
cx, cy = 320, 105
for angle in (0, math.pi / 3, 2 * math.pi / 3):
    points = []
    for i in range(241):
        t = i * 2 * math.pi / 240
        ex, ey = 65 * math.cos(t), 23 * math.sin(t)
        points.append(xy(cx + ex * math.cos(angle) - ey * math.sin(angle),
                         cy + ex * math.sin(angle) + ey * math.cos(angle)))
    for i in range(240):
        ratio = i / 240
        color = (round(98 + 80 * ratio), round(165 - 56 * ratio), 255)
        d.line([points[i], points[i + 1]], fill=color, width=3 * scale)
d.ellipse([xy(cx - 12, cy - 18), xy(cx + 12, cy + 6)], outline=(215, 209, 255), width=3 * scale)
d.polygon([xy(cx - 5, cy + 2), xy(cx - 13, cy + 24),
           xy(cx + 13, cy + 24), xy(cx + 5, cy + 2)], fill=(9, 13, 22))
d.line([xy(cx - 6, cy + 3), xy(cx - 13, cy + 24), xy(cx + 13, cy + 24),
        xy(cx + 6, cy + 3)], fill=(215, 209, 255), width=3 * scale)

def text(value, y, size, color):
    font = ImageFont.truetype(str(args.font), size * scale)
    d.text(xy(320, y), value, font=font, fill=color, anchor="mt")
text("Beyond OS", 193, 40, (245, 247, 255))
text("HOME EDITION  1.0", 250, 12, (163, 175, 200))
for index in range(3):
    x = 307 + index * 13
    d.ellipse([xy(x - 2, 309), xy(x + 2, 313)], fill=(115 + index * 25, 142, 230))
im = im.resize((640, 360), Image.Resampling.LANCZOS)
im.save(root / "assets/boot.ppm")
preview = Image.new("RGB", (1440, 900), (9, 13, 22))
preview.paste(im, ((1440 - 640) // 2, (900 - 360) // 2))
preview.save(root / "assets/boot-preview.png")
print("Rendered boot.ppm and boot-preview.png")
