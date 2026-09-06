from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont


ROOT = Path(__file__).resolve().parents[2]
ANDROID = ROOT / "DailyBreathAndroid"
APPLE = ROOT / "DailyBreathApple"
OUTPUT = ANDROID / "play-store-assets"

ICON_SOURCE = (
    APPLE
    / "Resources"
    / "Assets.xcassets"
    / "AppIcon.appiconset"
    / "AppIcon-1024.png"
)
BACKGROUND_SOURCE = ANDROID / "play-store-source" / "feature-background.png"
PHONE_SOURCE = APPLE / "AppStoreAssets" / "Screenshots" / "iPhone-6.5"
TABLET_SOURCE = APPLE / "AppStoreAssets" / "Screenshots" / "iPad-13"


def cover(image: Image.Image, size: tuple[int, int]) -> Image.Image:
    scale = max(size[0] / image.width, size[1] / image.height)
    resized = image.resize(
        (round(image.width * scale), round(image.height * scale)),
        Image.Resampling.LANCZOS,
    )
    left = (resized.width - size[0]) // 2
    top = (resized.height - size[1]) // 2
    return resized.crop((left, top, left + size[0], top + size[1]))


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    name = "arialbd.ttf" if bold else "arial.ttf"
    return ImageFont.truetype(str(Path("C:/Windows/Fonts") / name), size)


def build_icon() -> None:
    destination = OUTPUT / "common" / "dailybreath-app-icon-512.png"
    destination.parent.mkdir(parents=True, exist_ok=True)
    icon = Image.open(ICON_SOURCE).convert("RGBA")
    icon.resize((512, 512), Image.Resampling.LANCZOS).save(
        destination, "PNG", optimize=True
    )


def rounded_icon(size: int) -> Image.Image:
    icon = Image.open(ICON_SOURCE).convert("RGBA").resize(
        (size, size), Image.Resampling.LANCZOS
    )
    mask = Image.new("L", (size, size), 0)
    ImageDraw.Draw(mask).rounded_rectangle(
        (0, 0, size - 1, size - 1), radius=round(size * 0.19), fill=255
    )
    icon.putalpha(mask)
    return icon


def build_feature_graphic() -> None:
    destination = OUTPUT / "common" / "dailybreath-feature-graphic-1024x500.png"
    destination.parent.mkdir(parents=True, exist_ok=True)

    background = cover(Image.open(BACKGROUND_SOURCE).convert("RGB"), (1024, 500))
    veil = Image.new("RGBA", background.size, (2, 30, 20, 54))
    canvas = Image.alpha_composite(background.convert("RGBA"), veil)

    icon_size = 238
    icon_x, icon_y = 112, 131
    shadow = Image.new("RGBA", canvas.size, (0, 0, 0, 0))
    shadow_shape = Image.new("L", (icon_size, icon_size), 0)
    ImageDraw.Draw(shadow_shape).rounded_rectangle(
        (0, 0, icon_size - 1, icon_size - 1), radius=45, fill=180
    )
    shadow_shape = shadow_shape.filter(ImageFilter.GaussianBlur(15))
    shadow_color = Image.new("RGBA", (icon_size, icon_size), (0, 0, 0, 125))
    shadow_color.putalpha(shadow_shape)
    shadow.alpha_composite(shadow_color, (icon_x + 4, icon_y + 12))
    canvas = Image.alpha_composite(canvas, shadow)
    canvas.alpha_composite(rounded_icon(icon_size), (icon_x, icon_y))

    draw = ImageDraw.Draw(canvas)
    ivory = (251, 246, 230, 255)
    gold = (225, 179, 83, 255)
    draw.text((405, 185), "DailyBreath", font=font(69, bold=True), fill=ivory)
    draw.text(
        (410, 278),
        "Faith  •  Growth  •  Peace  •  Purpose",
        font=font(22, bold=True),
        fill=gold,
    )
    canvas.convert("RGB").save(destination, "PNG", optimize=True)


def build_phone_screenshots() -> None:
    destination_dir = OUTPUT / "phone"
    destination_dir.mkdir(parents=True, exist_ok=True)
    for source in sorted(PHONE_SOURCE.glob("*.png")):
        image = Image.open(source).convert("RGB")
        # Replace the iPhone camera pill in the source marketing frame with a
        # small, neutral Android-style centered punch-hole camera.
        draw = ImageDraw.Draw(image)
        screen_color = image.getpixel((image.width // 2, 330))
        draw.rounded_rectangle((470, 252, 772, 314), radius=31, fill=screen_color)
        camera_x, camera_y, camera_radius = image.width // 2, 282, 13
        draw.ellipse(
            (
                camera_x - camera_radius,
                camera_y - camera_radius,
                camera_x + camera_radius,
                camera_y + camera_radius,
            ),
            fill=(38, 40, 39),
        )
        # Play screenshots may be no more than 2:1. Preserve the designed top edge
        # and remove only the unused lower margin from the 1242x2688 Apple canvas.
        height = min(image.height, image.width * 2)
        image.crop((0, 0, image.width, height)).save(
            destination_dir / source.name, "PNG", optimize=True
        )


def build_tablet_screenshots() -> None:
    destination_dir = OUTPUT / "tablet"
    destination_dir.mkdir(parents=True, exist_ok=True)
    for source in sorted(TABLET_SOURCE.glob("*.png")):
        Image.open(source).convert("RGB").save(
            destination_dir / source.name, "PNG", optimize=True
        )


def main() -> None:
    build_icon()
    build_feature_graphic()
    build_phone_screenshots()
    build_tablet_screenshots()


if __name__ == "__main__":
    main()
