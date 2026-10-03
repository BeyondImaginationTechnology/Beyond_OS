"""Import the owner-selected comedy-collection files into Beyond TV's catalog.

The source inventory supplies exact Archive filenames. Editorial titles are
listed here because automated filename cleanup loses titles such as The Sandlot.
This script records source provenance and unverified rights; it does not certify
distribution permission.
"""

import csv
import json
import re
import unicodedata
from pathlib import Path
from urllib.parse import quote
from urllib.request import urlopen


ROOT = Path(__file__).resolve().parents[1]
CATALOG_PATH = ROOT / "beyond-tv/data/catalog.json"
CURATION_PATH = ROOT / "beyond-tv/data/comedy-collection-curation-2026-09-29.csv"
ARCHIVE_ID = "comedy-collection"
DETAILS_URL = f"https://archive.org/details/{ARCHIVE_ID}"

# Source index from the 241-file curation sheet -> editorial display title.
TITLES = {
    1: "The Sandlot", 3: "13 Going on 30", 10: "A Million Ways to Die in the West",
    15: "A Man Called Otto", 16: "Anora", 18: "Absolutely Anything",
    20: "After the Sunset", 29: "Asteroid City", 32: "Babylon",
    33: "Be Kind Rewind", 35: "Bernie", 36: "Better Nate Than Ever",
    38: "Blast from the Past", 39: "Blazing Saddles", 41: "Bottle Rocket",
    45: "Charlie's Angels", 47: "Cirque du Freak: The Vampire's Assistant",
    51: "Daddy's Home", 52: "Daddy's Home 2", 54: "Dazed and Confused",
    55: "Diary of a Wimpy Kid", 57: "Doc Hollywood", 58: "Dogma",
    59: "Don't Be a Menace to South Central While Drinking Your Juice in the Hood",
    64: "EuroTrip", 69: "Fly Me to the Moon", 71: "Get Shorty",
    73: "Groundhog Day", 74: "Guarding Tess", 76: "Heathers",
    78: "Holes", 83: "Hustle", 84: "I Heart Huckabees",
    86: "Ingrid Goes West", 89: "Jawbreaker", 90: "The Jewel of the Nile",
    91: "Joe Versus the Volcano", 92: "Johnny Dangerously", 94: "Kung Fu Hustle",
    97: "Lars and the Real Girl", 99: "Licorice Pizza", 102: "Man on the Moon",
    105: "Mean Girls", 107: "Memoirs of an Invisible Man",
    111: "Moonrise Kingdom", 113: "Mr. Deeds", 127: "No Hard Feelings",
    128: "Novocaine", 131: "Pineapple Express", 134: "Police Story",
    136: "Practical Magic", 141: "The Return of the Pink Panther",
    142: "Revenge of the Pink Panther", 144: "Ride On", 145: "Rob-B-Hood",
    148: "Romancing the Stone", 149: "Roofman", 150: "Rush Hour",
    151: "Rush Hour 2", 152: "Rush Hour 3", 153: "Rushmore",
    154: "SLC Punk", 159: "Shanghai Knights", 160: "Shanghai Noon",
    167: "Strangers with Candy", 168: "Stripes", 172: "The Addams Family",
    174: "The Bad News Bears", 177: "The Big Hit",
    181: "The Devil Wears Prada", 184: "The Grand Budapest Hotel",
    192: "The Naked Gun 2½: The Smell of Fear",
    193: "The Naked Gun: From the Files of Police Squad!",
    194: "The Peanut Butter Falcon", 196: "The Polka King",
    197: "The Producers", 198: "The Purple Rose of Cairo",
    206: "The War of the Roses", 209: "The Hitman's Wife's Bodyguard",
    211: "The Birdcage", 216: "The Onion Movie", 221: "This Is Spinal Tap",
    224: "Twins", 226: "Uncle Buck", 233: "What Women Want",
    235: "White Men Can't Jump", 241: "You've Got Mail",
}


def slugify(title):
    ascii_title = unicodedata.normalize("NFKD", title).encode("ascii", "ignore").decode()
    return re.sub(r"^-|-$", "", re.sub(r"[^a-z0-9]+", "-", ascii_title.lower()))


def runtime_label(seconds):
    minutes = round(seconds / 60)
    return f"{minutes // 60} hr {minutes % 60} min" if minutes else "Feature film"


with CURATION_PATH.open(encoding="utf-8-sig", newline="") as handle:
    rows = [row for row in csv.DictReader(handle) if row["editorial_decision"] == "candidate_rights_pending"]
assert len(rows) == len(TITLES) == 87, "The 87-file selection changed; review the title map."

with urlopen(f"https://archive.org/metadata/{ARCHIVE_ID}", timeout=30) as response:
    metadata = json.load(response)
files = {item["name"]: item for item in metadata.get("files", []) if "name" in item}
catalog = json.loads(CATALOG_PATH.read_text(encoding="utf-8"))
existing_slugs = {item.get("slug") for item in catalog}
existing_releases = {(str(item.get("title", "")).casefold(), str(item.get("year", ""))) for item in catalog}
entries = []

for row in rows:
    index = int(row["source_index"])
    title = TITLES[index]
    year = row["filename_year"]
    filename = row["file_name"]
    source = files.get(filename)
    if source is None and "\ufffd" in filename:
        pattern = re.compile("^" + re.escape(filename).replace("\\ufffd", ".") + "$")
        matches = [item for name, item in files.items() if pattern.match(name)]
        if len(matches) == 1:
            source = matches[0]
            filename = source["name"]
    if not source or not filename.lower().endswith(".mp4") or not int(source.get("size", 0)):
        raise RuntimeError(f"Archive original missing or unsuitable: {filename}")
    if (title.casefold(), year) in existing_releases:
        raise RuntimeError(f"Duplicate catalog release: {title} ({year})")
    slug = slugify(title)
    if slug in existing_slugs:
        slug = f"{slug}-{year}"
    if slug in existing_slugs:
        raise RuntimeError(f"Duplicate slug: {slug}")
    existing_slugs.add(slug)
    channel = row["channel_candidate"]
    genre = {
        "beyond-comedy": "Comedy",
        "beyond-family": "Family",
        "beyond-after-dark": "Dark Comedy · Drama",
        "classic-cinema": "Movies",
    }[channel]
    try:
        seconds = float(source.get("length", 0))
    except (TypeError, ValueError):
        seconds = 0
    entries.append({
        "slug": slug,
        "type": "movie",
        "title": title,
        "subtitle": "Source testing · Rights unverified",
        "description": f"{title} ({year}) from the Internet Archive collection.",
        "icon": "🎬",
        "gradient": "linear-gradient(135deg,#301b49,#593176 55%,#c37083)",
        "rating": "NR",
        "year": year,
        "genre": genre,
        "runtime": runtime_label(seconds),
        "duration": round(seconds),
        "source_type": "direct_video",
        "video_url": f"https://archive.org/download/{ARCHIVE_ID}/{quote(filename, safe='')}",
        "archive_id": ARCHIVE_ID,
        "archive_file": filename,
        "source_label": "Internet Archive · Testing · Rights unverified",
        "source_bookmark": "kareneliot",
        "candidate_url": DETAILS_URL,
        "source_url": DETAILS_URL,
        "channel_slug": channel,
        "rights_status": "unverified",
        "source_review_status": "testing",
        "approved_by": "owner",
        "approved_at": "2026-09-30",
        "new_addition": True,
    })

CATALOG_PATH.write_text(json.dumps(entries + catalog, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
print(f"Imported {len(entries)} Archive files into Beyond TV's public catalog.")
