"""Render the reusable Beyond Space TV Daily Space Facts opener and source credits."""
from __future__ import annotations

import argparse
import json
import math
import re
import shutil
import struct
import subprocess
import wave
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent
FFMPEG = Path.home() / "AppData/Local/Temp/beyond-space-video-tools/imageio_ffmpeg/binaries/ffmpeg-win-x86_64-v7.1.exe"
BG = ROOT / "daily-space-facts-universe-bg.png"
RATE = 48000
SIZE = (1080, 1920)
FONT = Path("C:/Windows/Fonts/comic.ttf")
FONT_BOLD = Path("C:/Windows/Fonts/comicbd.ttf")


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(str(FONT_BOLD if bold else FONT), size)


def centered(draw: ImageDraw.ImageDraw, text: str, y: int, f: ImageFont.FreeTypeFont,
             fill: str, stroke: str | None = None, sw: int = 0) -> None:
    box = draw.textbbox((0, 0), text, font=f, stroke_width=sw)
    x = (SIZE[0] - (box[2] - box[0])) // 2
    draw.text((x, y), text, font=f, fill=fill, stroke_width=sw, stroke_fill=stroke)


def render_intro_overlay(path: Path) -> None:
    image = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    draw = ImageDraw.Draw(image)
    # Type sits directly on the artwork; a bold outline keeps it readable without panels.
    centered(draw, "BEYOND SPACE TV", 562, font(78, True), "#FFE07A", "#101B50", 11)
    centered(draw, "DAILY SPACE", 730, font(82, True), "white", "#101B50", 10)
    centered(draw, "FACTS", 836, font(112, True), "#FFE07A", "#101B50", 11)
    centered(draw, "LOOK UP. LET WONDER BEGIN!", 1000, font(34, True), "#C9EEFF", "#101B50", 6)
    image.save(path)


def wrap_url(url: str, max_chars: int = 56) -> list[str]:
    if len(url) <= max_chars:
        return [url]
    chunks: list[str] = []
    rest = url
    while len(rest) > max_chars:
        cut = rest.rfind("/", 0, max_chars)
        if cut < 20:
            cut = max_chars
        chunks.append(rest[:cut + (cut < len(rest))])
        rest = rest[cut + (cut < len(rest)):]
    if rest:
        chunks.append(rest)
    return chunks


def render_credits_overlay(data: dict, path: Path) -> None:
    image = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    draw = ImageDraw.Draw(image)
    centered(draw, "BEYOND SPACE TV  •  DAILY SPACE FACTS", 120, font(34, True), "#FFE07A", "#071332", 7)
    centered(draw, "SCIENCE SOURCES", 238, font(68, True), "white", "#071332", 8)
    episode = str(data.get("episode_number", "{{EPISODE_NUMBER}}"))
    title = str(data.get("episode_title", "{{FACT_TITLE}}"))
    centered(draw, f"EPISODE {episode}  •  {title.upper()}", 360, font(31, True), "#A9E9FF", "#071332", 6)
    sources = data.get("sources", [])
    y = 466
    for index, source in enumerate(sources[:4], start=1):
        label = str(source.get("title", f"{{{{SOURCE_{index}_TITLE}}}}"))
        url = str(source.get("url", f"{{{{SOURCE_{index}_URL}}}}"))
        draw.text((80, y - 2), f"{index}.", font=font(30, True), fill="#FFE07A", stroke_width=4, stroke_fill="#071332")
        draw.text((136, y - 2), label, font=font(32, True), fill="white", stroke_width=5, stroke_fill="#071332")
        url_lines = wrap_url(url, 62)
        for line_no, line in enumerate(url_lines):
            draw.text((136, y + 48 + line_no * 36), line, font=font(21), fill="#A9E9FF", stroke_width=3, stroke_fill="#071332")
        y += max(240, 155 + len(url_lines) * 38)
    centered(draw, str(data.get("footer", "Source links shown for fact checking  •  Beyond Space TV")), 1690, font(25, True), "#FFE07A", "#071332", 5)
    image.save(path)


def make_music(path: Path, duration: float = 10.0) -> None:
    """Compose an original, royalty-free-by-construction synth fanfare; no samples."""
    chord_steps = [0, 5, 9, 7, 0]  # C, F, Am, G, C
    melody = [0, 4, 7, 12, 7, 4, 2, 7, 9, 12, 16, 12, 14, 11, 7, 4, 0, 7, 12, 19]
    bpm = 120
    beat = 60.0 / bpm
    total = int(RATE * duration)
    with wave.open(str(path), "wb") as wav:
        wav.setnchannels(2)
        wav.setsampwidth(2)
        wav.setframerate(RATE)
        block = bytearray()
        for n in range(total):
            t = n / RATE
            beat_pos = t / beat
            chord_i = min(int(beat_pos // 4), len(chord_steps) - 1)
            root = 130.8128 * (2 ** (chord_steps[chord_i] / 12))
            chord_env = min(1.0, (beat_pos % 4) * 2.0 + .18)
            pad = sum(math.sin(2 * math.pi * root * ratio * t) for ratio in (1, 1.25, 1.5)) / 3
            pad += .25 * math.sin(2 * math.pi * root * .5 * t)
            note_i = min(int(beat_pos * 2), len(melody) - 1)
            note_freq = 523.251 * (2 ** (melody[note_i] / 12))
            note_phase = (beat_pos * 2) % 1
            env = min(1.0, note_phase * 14) * max(.03, 1 - note_phase * .72)
            lead = math.sin(2 * math.pi * note_freq * t) + .25 * math.sin(4 * math.pi * note_freq * t)
            # A soft low drum on each downbeat gives the sting a broadcast pulse.
            beat_fraction = beat_pos % 1
            kick = 0.0
            if beat_fraction < .14:
                kick = math.sin(2 * math.pi * (78 - 35 * beat_fraction / .14) * t) * (1 - beat_fraction / .14)
            sparkle = .0
            if abs((t % 1.0) - .015) < .012:
                sparkle = math.sin(2 * math.pi * 1760 * t) * max(0, 1 - abs((t % 1.0) - .015) / .012)
            fade = min(1, t / .45, max(.02, (duration - t) / .75))
            sample = (pad * .16 * chord_env + lead * .13 * env + kick * .12 + sparkle * .035) * fade
            sample = max(-.96, min(.96, sample))
            packed = struct.pack("<h", int(sample * 32767))
            block.extend(packed)
            block.extend(packed)
            if len(block) >= 65536:
                wav.writeframesraw(block)
                block.clear()
        if block:
            wav.writeframesraw(block)


def render_video(background: Path, overlay: Path, audio_inputs: list[Path], output: Path,
                 seconds: float, voice: bool = False) -> None:
    args = [str(FFMPEG), "-y", "-loop", "1", "-framerate", "25", "-i", str(background),
            "-loop", "1", "-framerate", "25", "-i", str(overlay)]
    for audio in audio_inputs:
        args += ["-i", str(audio)]
    if voice:
        graph = (
            "[0:v]scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,"
            f"zoompan=z='min(1.045,1+on*0.00018)':d={int(seconds * 25)}:s=1080x1920:fps=25,format=yuv420p[bg];"
            "[1:v]format=rgba,fade=t=in:st=0.25:d=0.7:alpha=1[fg];"
            "[bg][fg]overlay=shortest=1:format=auto[v];"
            "[2:a]volume=0.58[m];[3:a]adelay=2500|2500,volume=1.05[vx];"
            "[m][vx]amix=inputs=2:duration=first:normalize=0,loudnorm=I=-16:TP=-1.5:LRA=8[a]"
        )
        maps = ["-map", "[v]", "-map", "[a]"]
    else:
        graph = (
            "[0:v]scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,"
            f"zoompan=z='min(1.035,1+on*0.00014)':d={int(seconds * 25)}:s=1080x1920:fps=25,format=yuv420p[bg];"
            "[1:v]format=rgba[fg];[bg][fg]overlay=shortest=1:format=auto[v];"
            "[2:a]afade=t=out:st=" + str(max(0, seconds - 1.0)) + ":d=1,volume=0.28,loudnorm=I=-20:TP=-2:LRA=9[a]"
        )
        maps = ["-map", "[v]", "-map", "[a]"]
    args += ["-filter_complex", graph, *maps, "-t", str(seconds), "-r", "25",
             "-c:v", "libx264", "-preset", "medium", "-crf", "18", "-pix_fmt", "yuv420p",
             "-c:a", "aac", "-b:a", "192k", "-movflags", "+faststart", str(output)]
    subprocess.run(args, check=True)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--credits-json", type=Path, default=ROOT / "episode-01-pluto-sources.json")
    parser.add_argument("--credits-seconds", type=float, default=8)
    args = parser.parse_args()
    if not FFMPEG.exists() or not BG.exists():
        raise FileNotFoundError("Required local ffmpeg binary or show background is missing.")
    credits = json.loads(args.credits_json.read_text(encoding="utf-8"))
    intro_overlay = ROOT / "daily-space-facts-intro-overlay.png"
    credits_overlay = ROOT / "daily-space-facts-credits-preview.png"
    music = ROOT / "daily-space-facts-theme-original.wav"
    voice = ROOT / "daily-space-facts-theme-voice.wav"
    mixed = ROOT / "daily-space-facts-theme-mix.wav"
    if not voice.exists():
        raise FileNotFoundError("Add the ElevenLabs narration as daily-space-facts-theme-voice.wav before rendering.")
    render_intro_overlay(intro_overlay)
    render_credits_overlay(credits, credits_overlay)
    make_music(music)
    subprocess.run([str(FFMPEG), "-y", "-i", str(music), "-i", str(voice), "-filter_complex",
                    "[0:a]volume=0.62[m];[1:a]adelay=2500|2500,volume=1.0[v];"
                    "[m][v]amix=inputs=2:duration=first:normalize=0,loudnorm=I=-16:TP=-1.5:LRA=8[a]",
                    "-map", "[a]", "-c:a", "pcm_s16le", "-ar", "48000", str(mixed)], check=True)
    intro = ROOT / "BeyondSpaceTV_DailySpaceFacts_Intro_10sec.mp4"
    safe_episode = re.sub(r"[^A-Za-z0-9_-]+", "_", str(credits.get("episode_number", "XX"))).strip("_")
    safe_title = re.sub(r"[^a-z0-9-]+", "-", str(credits.get("episode_title", "credits")).lower()).strip("-")
    credits_out = ROOT / f"BeyondSpaceTV_DailySpaceFacts_Credits_Episode_{safe_episode}_{safe_title}.mp4"
    render_video(BG, intro_overlay, [music, voice], intro, 10, voice=True)
    render_video(BG, credits_overlay, [music], credits_out, args.credits_seconds)
    print(f"INTRO={intro}")
    print(f"CREDITS={credits_out}")


if __name__ == "__main__":
    main()
