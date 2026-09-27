# Beyond TV video ads

Beyond TV reserves a five minute commercial break between programs. The web
player uses Google IMA, which accepts a VAST ad tag from Google Ad Manager,
AdSense for Video, or another VAST-compatible provider.

Configure the production web server with:

```text
BEYOND_TV_VAST_TAG_URL=https://your-ad-server.example/vast-tag
BEYOND_TV_AD_BREAK_SECONDS=300
```

The VAST tag is intentionally delivered to browsers because client-side video
ad players must request it. Do not put an account password or API secret in the
tag value.

When the tag is missing, invalid, blocked, or returns no inventory, the player
fills the remainder of the break with the branded Beyond TV intermission. This
keeps the schedule moving without claiming an ad impression that was not
served.
