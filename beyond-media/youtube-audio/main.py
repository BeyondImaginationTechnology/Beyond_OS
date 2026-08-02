"""
Beyond Music YouTube audio converter API.

API contract:
- GET /?url=<youtube_url> returns {"token": "..."}
- GET /download?token=<token> returns the converted MP3

Based on alperensumeroglu/yt-audio-api and adapted for Beyond Media hosting.
"""

from pathlib import Path
from urllib.parse import parse_qs, urlparse
from uuid import uuid4
import secrets
import threading

from flask import Flask, jsonify, request, send_from_directory
import yt_dlp

import access_manager
from constants import (
    ABS_DOWNLOADS_PATH,
    BAD_REQUEST,
    INTERNAL_SERVER_ERROR,
    MAX_VIDEO_SECONDS,
    NOT_FOUND,
    REQUEST_TIMEOUT,
    TOKEN_LENGTH,
    UNAUTHORIZED,
)

app = Flask(__name__)
Path(ABS_DOWNLOADS_PATH).mkdir(parents=True, exist_ok=True)
_token_cleaner_started = False


def _is_youtube_url(value: str) -> bool:
    parsed = urlparse(value)
    host = (parsed.netloc or "").lower()
    if parsed.scheme not in {"http", "https"}:
        return False
    if host == "youtu.be":
        return bool(parsed.path.strip("/"))
    if host == "youtube.com" or host.endswith(".youtube.com"):
        if parsed.path.startswith("/shorts/"):
            return len(parsed.path.split("/")) > 2
        return bool(parse_qs(parsed.query).get("v"))
    return False


def _start_token_cleaner() -> None:
    global _token_cleaner_started
    if _token_cleaner_started:
        return
    threading.Thread(target=access_manager.manage_tokens, daemon=True).start()
    _token_cleaner_started = True


@app.before_request
def before_request():
    _start_token_cleaner()


@app.route("/", methods=["GET"])
def handle_audio_request():
    video_url = request.args.get("url", "").strip()
    if not video_url:
        return jsonify(error="Missing 'url' parameter in request."), BAD_REQUEST
    if not _is_youtube_url(video_url):
        return jsonify(error="Only YouTube video URLs are supported."), BAD_REQUEST

    filename = f"{uuid4()}.mp3"
    output_template = str(Path(ABS_DOWNLOADS_PATH) / filename)
    ydl_opts = {
        "format": "bestaudio/best",
        "outtmpl": output_template,
        "noplaylist": True,
        "quiet": True,
        "postprocessors": [{
            "key": "FFmpegExtractAudio",
            "preferredcodec": "mp3",
            "preferredquality": "192",
        }],
        "match_filter": yt_dlp.utils.match_filter_func(f"duration < {MAX_VIDEO_SECONDS}"),
    }

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            ydl.download([video_url])
    except Exception as exception:
        return jsonify(error="Failed to download or convert audio.", detail=str(exception)), INTERNAL_SERVER_ERROR

    return _generate_token_response(filename)


@app.route("/download", methods=["GET"])
def download_audio():
    token = request.args.get("token", "").strip()
    if not token:
        return jsonify(error="Missing 'token' parameter in request."), BAD_REQUEST
    if not access_manager.has_access(token):
        return jsonify(error="Token is invalid or unknown."), UNAUTHORIZED
    if not access_manager.is_valid(token):
        return jsonify(error="Token has expired."), REQUEST_TIMEOUT

    try:
        filename = access_manager.get_audio_file(token)
        return send_from_directory(ABS_DOWNLOADS_PATH, filename, as_attachment=True)
    except FileNotFoundError:
        return jsonify(error="Requested file could not be found on the server."), NOT_FOUND


def _generate_token_response(filename: str):
    token = secrets.token_urlsafe(TOKEN_LENGTH)
    access_manager.add_token(token, filename)
    return jsonify(token=token)


def main():
    _start_token_cleaner()
    app.run(host="0.0.0.0", port=5000)


if __name__ == "__main__":
    main()
