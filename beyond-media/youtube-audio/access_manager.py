"""
Token management for temporary converted audio files.
"""

from datetime import datetime, timedelta
from pathlib import Path
import time

from constants import ABS_DOWNLOADS_PATH, EXPIRY_TIME_MINUTES

allowed_tokens = {}
audio_files = {}


def add_token(token: str, filename: str) -> None:
    expiry = datetime.now() + timedelta(minutes=EXPIRY_TIME_MINUTES)
    allowed_tokens[token] = expiry
    audio_files[token] = filename


def has_access(token: str) -> bool:
    return token in allowed_tokens


def is_valid(token: str) -> bool:
    return allowed_tokens[token] >= datetime.now()


def get_audio_file(token: str) -> str:
    return audio_files[token]


def remove_expired_tokens() -> list:
    expired = []
    files_to_remove = []

    for token in list(allowed_tokens.keys()):
        if not is_valid(token):
            expired.append(token)
            files_to_remove.append(audio_files.pop(token, None))

    for token in expired:
        allowed_tokens.pop(token, None)

    return [name for name in files_to_remove if name]


def delete_expired_files(files: list) -> None:
    for file_name in files:
        try:
            (Path(ABS_DOWNLOADS_PATH) / file_name).unlink(missing_ok=True)
        except Exception as exception:
            print(f"Failed to delete expired audio file '{file_name}': {exception}")


def manage_tokens() -> None:
    while True:
        delete_expired_files(remove_expired_tokens())
        time.sleep(10)
