from pathlib import Path
from PIL import Image, ImageDraw, ImageFont, ImageFilter


ROOT = Path(__file__).resolve().parent
SIZE = (1080, 1350)

NAVY = "#071B4B"
DEEP_NAVY = "#041334"
COBALT = "#0D3D93"
CREAM = "#FFF3D1"
PALE_BLUE = "#BFEAFF"
WARM_GOLD = "#FFD476"
WHITE = "#FFFDF5"
ORANGE = "#E87E38"

FONT_ROUNDED = r"C:\Windows\Fonts\ARLRDBD.TTF"
FONT_BOLD = r"C:\Windows\Fonts\segoeuib.ttf"
FONT_REGULAR = r"C:\Windows\Fonts\segoeui.ttf"


def font(path: str, size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(path, size=size)


def load_background(index: int) -> Image.Image:
    image = Image.open(ROOT / f"slide-{index}-background.webp").convert("RGB")
    return image.resize(SIZE, Image.Resampling.LANCZOS).convert("RGBA")


def rounded_panel(layer: Image.Image, box, radius=40, fill=(4, 19, 52, 205), outline=None, width=3):
    draw = ImageDraw.Draw(layer)
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def centered(draw: ImageDraw.ImageDraw, text: str, y: int, fnt, fill, stroke=0, stroke_fill=None):
    box = draw.multiline_textbbox((0, 0), text, font=fnt, spacing=4, align="center", stroke_width=stroke)
    x = (SIZE[0] - (box[2] - box[0])) // 2
    draw.multiline_text((x, y), text, font=fnt, fill=fill, spacing=4, align="center",
                        stroke_width=stroke, stroke_fill=stroke_fill)


def footer(base: Image.Image, number: str):
    overlay = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    rounded_panel(overlay, (72, 1248, 1008, 1318), radius=34, fill=(4, 19, 52, 220),
                  outline=(255, 243, 209, 100), width=2)
    d = ImageDraw.Draw(overlay)
    d.text((110, 1264), "BEYOND SPACE", font=font(FONT_BOLD, 28), fill=CREAM)
    right = f"DAILY SPACE • {number}"
    rb = d.textbbox((0, 0), right, font=font(FONT_BOLD, 25))
    d.text((970 - (rb[2] - rb[0]), 1266), right, font=font(FONT_BOLD, 25), fill=PALE_BLUE)
    base.alpha_composite(overlay)


def slide_one():
    base = load_background(1)
    shadow = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    rounded_panel(shadow, (214, 86, 866, 180), radius=47, fill=(0, 0, 0, 145))
    shadow = shadow.filter(ImageFilter.GaussianBlur(16))
    base.alpha_composite(shadow)

    overlay = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    rounded_panel(overlay, (200, 72, 880, 168), radius=48, fill=(255, 212, 118, 245),
                  outline=(255, 255, 255, 130), width=3)
    rounded_panel(overlay, (286, 474, 794, 548), radius=37, fill=(4, 19, 52, 205),
                  outline=(191, 234, 255, 130), width=2)
    d = ImageDraw.Draw(overlay)
    centered(d, "DAILY SPACE FACT 4/55", 96, font(FONT_BOLD, 39), NAVY)
    centered(d, "MARS SUNSETS\nGLOW BLUE", 216, font(FONT_ROUNDED, 92), WHITE, 8, DEEP_NAVY)
    centered(d, "Opposite of Earth skies", 492, font(FONT_BOLD, 34), PALE_BLUE)
    base.alpha_composite(overlay)
    footer(base, "4/55")
    base.convert("RGB").save(ROOT / "daily-space-4-of-55-slide-1.png", optimize=True)


def slide_two():
    base = load_background(2)
    overlay = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    rounded_panel(overlay, (72, 72, 1008, 485), radius=48, fill=(4, 19, 52, 218),
                  outline=(191, 234, 255, 135), width=3)
    d = ImageDraw.Draw(overlay)
    d.text((116, 108), "HOW IT WORKS", font=font(FONT_ROUNDED, 67), fill=WHITE,
           stroke_width=3, stroke_fill=COBALT)
    d.line((116, 200, 964, 200), fill=(191, 234, 255, 110), width=3)
    bullet_font = font(FONT_BOLD, 42)
    facts = [
        "Fine dust fills thin Martian air",
        "Blue light travels through the dust",
        "The wider sky glows yellow-orange",
    ]
    for idx, fact in enumerate(facts):
        y = 236 + idx * 78
        d.ellipse((116, y + 12, 138, y + 34), fill=WARM_GOLD)
        d.text((160, y), fact, font=bullet_font, fill=CREAM)
    base.alpha_composite(overlay)
    footer(base, "4/55")
    base.convert("RGB").save(ROOT / "daily-space-4-of-55-slide-2.png", optimize=True)


def slide_three():
    base = load_background(3)
    overlay = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    rounded_panel(overlay, (72, 84, 736, 716), radius=48, fill=(4, 19, 52, 220),
                  outline=(255, 212, 118, 130), width=3)
    d = ImageDraw.Draw(overlay)
    d.text((116, 124), "WHY IT\nMATTERS", font=font(FONT_ROUNDED, 75), fill=WHITE,
           spacing=0, stroke_width=3, stroke_fill=COBALT)
    d.line((116, 304, 682, 304), fill=(255, 212, 118, 150), width=3)
    body = "Sunlight meets fine dust.\n\nBlue stays close to the Sun,\nwhile warm colors spread wide.\n\nRovers use twilight to study\ndust height and Martian clouds."
    d.multiline_text((116, 334), body, font=font(FONT_BOLD, 34), fill=CREAM,
                     spacing=6, align="left")
    base.alpha_composite(overlay)
    footer(base, "4/55")
    base.convert("RGB").save(ROOT / "daily-space-4-of-55-slide-3.png", optimize=True)


def slide_four():
    base = load_background(4)
    overlay = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    rounded_panel(overlay, (72, 72, 1008, 498), radius=50, fill=(4, 19, 52, 218),
                  outline=(191, 234, 255, 135), width=3)
    rounded_panel(overlay, (182, 540, 898, 624), radius=42, fill=(255, 212, 118, 240),
                  outline=(255, 255, 255, 125), width=3)
    d = ImageDraw.Draw(overlay)
    centered(d, "ON MARS, SUNSET\nIS COOL BLUE.", 116, font(FONT_ROUNDED, 76), WHITE, 5, DEEP_NAVY)
    centered(d, "Would you watch a blue sunset?", 368, font(FONT_BOLD, 38), PALE_BLUE)
    centered(d, "Explore more with Beyond Space", 562, font(FONT_BOLD, 36), NAVY)
    base.alpha_composite(overlay)
    footer(base, "4/55")
    base.convert("RGB").save(ROOT / "daily-space-4-of-55-slide-4.png", optimize=True)


def contact_sheet():
    thumb_w, thumb_h = 432, 540
    sheet = Image.new("RGB", (thumb_w * 4, thumb_h), DEEP_NAVY)
    for i in range(1, 5):
        slide = Image.open(ROOT / f"daily-space-4-of-55-slide-{i}.png").convert("RGB")
        sheet.paste(slide.resize((thumb_w, thumb_h), Image.Resampling.LANCZOS), ((i - 1) * thumb_w, 0))
    sheet.save(ROOT / "daily-space-4-of-55-contact-sheet.png", optimize=True)


if __name__ == "__main__":
    slide_one()
    slide_two()
    slide_three()
    slide_four()
    contact_sheet()
