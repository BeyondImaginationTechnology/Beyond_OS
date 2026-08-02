"""
Configuration for the Beyond Music YouTube audio converter.

Based on alperensumeroglu/yt-audio-api, adapted for deployment inside the
Beyond Media project.
"""

from pathlib import Path
import os

ROOT_DIRECTORY = Path(__file__).resolve().parent
DOWNLOADS_DIRECTORY = os.getenv("YT_AUDIO_DOWNLOAD_DIR", str(ROOT_DIRECTORY / "downloads"))
ABS_DOWNLOADS_PATH = str(Path(DOWNLOADS_DIRECTORY).resolve())

REQUEST_TIMEOUT = 408
UNAUTHORIZED = 401
NOT_FOUND = 404
BAD_REQUEST = 400
INTERNAL_SERVER_ERROR = 500

EXPIRY_TIME_MINUTES = int(os.getenv("YT_AUDIO_TOKEN_MINUTES", "5"))
TOKEN_LENGTH = int(os.getenv("YT_AUDIO_TOKEN_LENGTH", "20"))
MAX_VIDEO_SECONDS = int(os.getenv("YT_AUDIO_MAX_VIDEO_SECONDS", "900"))
