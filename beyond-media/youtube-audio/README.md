# Beyond Music YouTube Audio API

Vendored from `alperensumeroglu/yt-audio-api` and adapted for Beyond Music.

## Endpoints

- `GET /?url=<youtube_url>` returns `{ "token": "..." }`
- `GET /download?token=<token>` returns the converted MP3 file

## Server requirements

- Python 3.8+
- FFmpeg installed on the server
- Python dependencies from `requirements.txt`

## Environment

- `YT_AUDIO_DOWNLOAD_DIR`: optional writable directory for temporary MP3 files.
- `YT_AUDIO_TOKEN_MINUTES`: optional token lifetime, default `5`.
- `YT_AUDIO_MAX_VIDEO_SECONDS`: optional max video length, default `900`.

## iOS configuration

Hostdeal/PHP-only hosting cannot run this converter directly. Deploy this WSGI app on a Python + FFmpeg host, then set `music.youtube.audio_api_base_url` in `var/config/live.php` to that deployed converter URL.

The iOS app should keep using the Beyond PHP proxy:

```xml
<key>YouTubeAudioAPIBaseURL</key>
<string>https://beyondimagination.co.technology/beyond-media/api/youtube-audio.php</string>
```

YouTube search still uses `/beyond-media/api/youtube-search.php` and the existing Beyond TV YouTube API key fallback.
