from pathlib import Path
import json
import textwrap

from PIL import Image, ImageDraw, ImageFont
from reportlab.lib.colors import HexColor
from reportlab.lib.pagesizes import letter
from reportlab.pdfbase.pdfmetrics import stringWidth
from reportlab.pdfgen import canvas

ROOT = Path(__file__).resolve().parents[1]
DATE = "2026-08-16"
PDF_PATH = ROOT / "output" / "pdf" / f"daily-breath-recovery-weekly-{DATE}.pdf"
PNG_PATH = ROOT / "output" / "png" / f"daily-breath-recovery-weekly-{DATE}-instagram.png"


def entries(name):
    return json.loads((ROOT / "dailybreath" / "data" / name).read_text(encoding="utf-8"))["entries"]


verse = next(item for item in entries("daily-verses.json") if item.get("schedule_date") == DATE)
devotional = next(
    item for item in entries("daily-devotionals.json")
    if item.get("schedule_date") == DATE and item.get("schedule_role") == "primary"
)
challenge = next(item for item in entries("recovery-challenges.json") if item["starts_on"] <= DATE <= item["ends_on"])


def clean(value):
    return str(value).replace("—", "-").replace("–", "-")


def wrap_pdf(pdf, text, font, size, width):
    words = clean(text).split()
    lines, line = [], ""
    for word in words:
        candidate = f"{line} {word}".strip()
        if line and stringWidth(candidate, font, size) > width:
            lines.append(line)
            line = word
        else:
            line = candidate
    if line:
        lines.append(line)
    return lines


def draw_pdf():
    PDF_PATH.parent.mkdir(parents=True, exist_ok=True)
    pdf = canvas.Canvas(str(PDF_PATH), pagesize=letter)
    width, height = letter
    pdf.setFillColor(HexColor("#071d14"))
    pdf.rect(0, 0, width, height, fill=1, stroke=0)
    pdf.setFillColor(HexColor("#d0a34c"))
    pdf.rect(48, height - 58, 42, 4, fill=1, stroke=0)
    pdf.setFont("Helvetica-Bold", 12)
    pdf.drawString(48, height - 88, "DAILY BREATH")
    pdf.setFillColor(HexColor("#b9d1c2"))
    pdf.setFont("Helvetica-Bold", 9)
    pdf.drawRightString(width - 48, height - 88, "AUGUST 16, 2026")
    pdf.setFillColor(HexColor("#fffaf0"))
    pdf.setFont("Times-Bold", 34)
    pdf.drawString(48, height - 133, "Recovery Weekly")

    y = height - 177
    pdf.setFillColor(HexColor("#173f2c"))
    pdf.roundRect(48, y - 176, width - 96, 176, 16, fill=1, stroke=0)
    pdf.setFillColor(HexColor("#f0cb77"))
    pdf.setFont("Helvetica-Bold", 10)
    pdf.drawString(68, y - 28, "VERSE FOR THE WEEK")
    pdf.setFillColor(HexColor("#ffffff"))
    pdf.setFont("Times-Roman", 19)
    line_y = y - 58
    for line in wrap_pdf(pdf, f'"{verse["text"]}"', "Times-Roman", 19, width - 136)[:4]:
        pdf.drawString(68, line_y, line)
        line_y -= 24
    pdf.setFillColor(HexColor("#f0cb77"))
    pdf.setFont("Helvetica-Bold", 10)
    pdf.drawString(68, y - 154, clean(verse["reference"]))

    y -= 220
    pdf.setFillColor(HexColor("#f0cb77"))
    pdf.setFont("Helvetica-Bold", 10)
    pdf.drawString(48, y, "RECOVERY REFLECTION")
    pdf.setFillColor(HexColor("#ffffff"))
    pdf.setFont("Helvetica-Bold", 18)
    pdf.drawString(48, y - 28, clean(devotional["title"]))
    pdf.setFillColor(HexColor("#c8d8cf"))
    pdf.setFont("Helvetica", 12)
    line_y = y - 53
    for line in wrap_pdf(pdf, devotional["excerpt"], "Helvetica", 12, width - 96)[:4]:
        pdf.drawString(48, line_y, line)
        line_y -= 17

    box_y = 95
    pdf.setFillColor(HexColor("#2d694b"))
    pdf.roundRect(48, box_y, width - 96, 176, 16, fill=1, stroke=0)
    pdf.setFillColor(HexColor("#f4d98e"))
    pdf.setFont("Helvetica-Bold", 10)
    pdf.drawString(68, box_y + 146, "THIS WEEK'S CHALLENGE")
    pdf.setFillColor(HexColor("#ffffff"))
    pdf.setFont("Helvetica-Bold", 17)
    pdf.drawString(68, box_y + 120, clean(challenge["title"]))
    pdf.setFillColor(HexColor("#dce9e0"))
    pdf.setFont("Helvetica", 10)
    line_y = box_y + 98
    for line in wrap_pdf(pdf, challenge["description"], "Helvetica", 10, width - 136)[:2]:
        pdf.drawString(68, line_y, line)
        line_y -= 14
    for step in challenge["steps"][:3]:
        pdf.drawString(76, line_y - 3, "- " + clean(step))
        line_y -= 17
    pdf.setFillColor(HexColor("#91b7a0"))
    pdf.setFont("Helvetica-Bold", 8)
    pdf.drawString(48, 52, "BREATHE THROUGH THE CRAVING. YOU ARE NOT ALONE.")
    pdf.showPage()
    pdf.save()


def font(name, size):
    path = Path("C:/Windows/Fonts") / name
    return ImageFont.truetype(str(path), size)


def wrap_image(draw, text, face, width):
    words, lines, line = clean(text).split(), [], ""
    for word in words:
        candidate = f"{line} {word}".strip()
        if line and draw.textbbox((0, 0), candidate, font=face)[2] > width:
            lines.append(line)
            line = word
        else:
            line = candidate
    if line:
        lines.append(line)
    return lines


def draw_png():
    PNG_PATH.parent.mkdir(parents=True, exist_ok=True)
    image = Image.new("RGB", (1080, 1080), "#071d14")
    draw = ImageDraw.Draw(image)
    gold, white, muted, green = "#d0a34c", "#fffaf0", "#c8d8cf", "#2d694b"
    draw.rectangle((70, 70, 150, 78), fill=gold)
    draw.text((70, 100), "DAILY BREATH", font=font("arialbd.ttf", 25), fill=gold)
    date_text = "AUGUST 16, 2026"
    date_face = font("arialbd.ttf", 17)
    date_width = draw.textbbox((0, 0), date_text, font=date_face)[2]
    draw.text((1010 - date_width, 106), date_text, font=date_face, fill="#b9d1c2")
    draw.text((70, 154), "Recovery Weekly", font=font("georgiab.ttf", 64), fill=white)
    draw.rounded_rectangle((70, 260, 1010, 540), radius=30, fill="#173f2c")
    draw.text((108, 294), "VERSE FOR THE WEEK", font=font("arialbd.ttf", 18), fill="#f0cb77")
    y = 345
    verse_face = font("georgia.ttf", 31)
    for line in wrap_image(draw, f'"{verse["text"]}"', verse_face, 864)[:4]:
        draw.text((108, y), line, font=verse_face, fill="#ffffff")
        y += 41
    draw.text((108, 500), clean(verse["reference"]), font=font("arialbd.ttf", 19), fill="#f0cb77")
    draw.text((70, 582), "RECOVERY REFLECTION", font=font("arialbd.ttf", 18), fill="#f0cb77")
    draw.text((70, 624), clean(devotional["title"]), font=font("arialbd.ttf", 29), fill="#ffffff")
    y = 675
    body_face = font("arial.ttf", 21)
    for line in wrap_image(draw, devotional["excerpt"], body_face, 940)[:3]:
        draw.text((70, y), line, font=body_face, fill=muted)
        y += 31
    draw.rounded_rectangle((70, 790, 1010, 982), radius=26, fill=green)
    draw.text((102, 820), "THIS WEEK'S CHALLENGE", font=font("arialbd.ttf", 17), fill="#f4d98e")
    draw.text((102, 858), clean(challenge["title"]), font=font("arialbd.ttf", 28), fill="#ffffff")
    y = 903
    for line in wrap_image(draw, challenge["description"], font("arial.ttf", 19), 876)[:2]:
        draw.text((102, y), line, font=font("arial.ttf", 19), fill="#dce9e0")
        y += 27
    draw.text((70, 1028), "BREATHE THROUGH THE CRAVING. YOU ARE NOT ALONE.", font=font("arialbd.ttf", 15), fill="#91b7a0")
    image.save(PNG_PATH, format="PNG", optimize=True)


draw_pdf()
draw_png()
print(json.dumps({"pdf": str(PDF_PATH), "png": str(PNG_PATH)}))
