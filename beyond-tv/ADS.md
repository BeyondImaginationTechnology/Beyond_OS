# Beyond TV video ads

Beyond TV reserves a five minute commercial break between programs. The web
player uses Google IMA, which accepts a VAST ad tag from Google Ad Manager,
AdSense for Video, or another VAST-compatible provider.

The Android app uses the native Unity Ads SDK first during the same break. Its
Beyond-Tv Unity project uses Google Play Game ID `6197579` and the
`Interstitial_Android` ad unit. The app requests one interstitial at the program
transition; if it does not start, a configured web VAST tag can fill the slot.
Unity Ads does not serve web browsers or WebGL, so the browser player uses VAST.

Configure the production web server with:

```text
BEYOND_TV_VAST_TAG_URL=https://your-ad-server.example/vast-tag
BEYOND_TV_AD_BREAK_SECONDS=300
```

The VAST tag is intentionally delivered to browsers because client-side video
ad players must request it. Do not put an account password or API secret in the
tag value.

When the tag is missing, invalid, blocked, or returns no inventory, the player
keeps the five minute break and offers Bit Runner as an interactive intermission.
The game pauses and hides while an ad plays. An ad impression is only recorded
by the serving ad SDK, not by the break countdown or the game.

## Direct sponsor inventory

Sell the break as publisher inventory, not as a Google Ads advertiser campaign.
The proposed first placement is **Beyond TV / web / between-program break**:
one sponsor video plays at the transition between programs, then Bit Runner
fills the remainder of the five-minute intermission. A sponsor can buy a
time-limited pilot at a negotiated flat fee; report only actual ad starts,
completions, and clicks. Do not promise a minimum audience before it exists.

In Google Ad Manager, create a video ad unit for this placement, then create an
advertiser order with a Sponsorship line item. Set its dates, share of voice,
cost-per-day terms, channel targeting if needed, and the sponsor creative.
Approve the order only after the sponsor agreement and creative are final.
Generate the ad unit's VAST tag and set `BEYOND_TV_VAST_TAG_URL` to that tag on
the web server. Google Ad Manager requires an AdSense account at signup, and
Video Solutions must be enabled for the network before video inventory can
serve. If video inventory is unavailable, use another VAST-compatible ad server
or add a first-party sponsor scheduler; the existing web player accepts either
provider's VAST tag.

The five-minute break is available only while a real viewer is watching. An
always-on test player is for playback QA, not billable impressions.

Unity's dashboard currently lists Apple App Store Game ID `6197578` and
`Interstitial_iOS`. The native Apple AVPlayer client needs a separate iOS SDK
integration before this unit can serve; the WKWebView fallback uses the web
VAST path. Unity's dashboard also shows no configured store IDs or payout
profile. Add those in the dashboard before release, and review the project's
child-directed audience setting for Beyond TV's programming.
