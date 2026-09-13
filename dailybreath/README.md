# Daily Breath Web 2.0

The web interface prompts for English, French, or Spanish on first visit, saves the choice locally and in a cookie, and keeps the language editable from every screen. The scripture reader follows that choice: English uses the World English Bible and Pickthall Quran meaning; French uses Louis Segond 1910 for the Bible and Tanakh; Spanish uses Reina-Valera 1909 for the Bible and Tanakh; and non-English Quran readers use the bundled Arabic Uthmani text rather than falling back to English.

Daily Breath is an installable Beyond OS progressive web app for matched daily Bible, Tanakh, and Quran readings; complete local sacred-text libraries and search; devotionals; guided breathing; encrypted reflection journaling; weekly challenges; activity history; and recovery support.

## Install

Open `/dailybreath/` from the Beyond OS App Store. In a supported browser, use the **Install Daily Breath** prompt or the browser’s “Add to Home Screen” command. The installed app launches in its own standalone window and includes shortcuts for Today, Scripture, Breathe, Journal, and Weekly Challenge.

The app requires HTTPS in production for service workers and installation. Localhost is permitted for development.

## Persistence

- Reflection journal entries are encrypted on the server and attached to the signed-in Beyond ID.
- Breathing sessions and database-backed weekly challenge progress are persisted in the Beyond OS database.
- Bundled recovery challenge progress uses `dailybreath_challenge_progress`, created defensively at runtime and included in `sql/dailybreath_web_1_2.sql` for production migrations.
- Faith tradition, theme, and reduced-motion preferences remain on the current device.

## Content and safety

The Verse or Ayah of the Day uses the existing dated recovery theme and selects the same passage when available or a related Torah/Quran passage. Scripture editions reuse the version-pinned mobile resources: World English Bible, Louis Segond 1910, Reina-Valera 1909, and the Hebrew Tanakh via eBible.org; Pickthall’s English Quran meaning via Project Gutenberg; and the Arabic Uthmani Quran text under the attribution in `data/QURAN_JSON_LICENSE.txt`. Recovery content is general faith-centered wellness support, not medical care, and the support page links to official 988 and SAMHSA resources.
