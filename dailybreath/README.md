# Daily Breath Web 2.0

The web interface prompts for English, French, or Spanish on first visit, saves the choice locally and in a cookie, and keeps the language editable from every screen. The scripture reader follows that choice: English uses the World English Bible and Pickthall Quran meaning; French uses Louis Segond 1910 for the Bible and Tanakh; Spanish uses Reina-Valera 1909 for the Bible and Tanakh; and non-English Quran readers use the bundled Arabic Uthmani text rather than falling back to English.

Daily Breath is an installable Beyond OS progressive web app for matched daily Bible, Tanakh, and Quran readings; complete local sacred-text libraries and search; devotionals; guided breathing; encrypted reflection journaling; weekly challenges; activity history; and recovery support.

Daily Breath has product-specific legal pages at `/dailybreath/privacy.php`, `/dailybreath/terms.php`, and `/dailybreath/data-controls.php`. Keep these documents, native store disclosures, and actual data handling synchronized whenever a feature changes.

## Web 2.3 daily content

The admin-only daily content editor is at `/dailybreath/admin/daily-content.php` and uses the existing DAILYBREATH_ADMIN_PASSWORD session. Editors can save a Bible, Tanakh, or Quran reading as a draft, preview desktop and mobile cards, listen to a browser speech preview, and review podcast details and social captions. Only the explicit **Approve & publish** action makes a reading public. AI tools are not called from this feature; keep any provider keys server-side and never paste them into browser code.

Published readings appear on the home page and have a clean public page at `/dailybreath/daily.php`. That page keeps the passage available as selectable HTML, supports share and listen actions, and can download a PNG share card. Browser speech preview is not an MP3 export; attach a reviewed audio file before adding a new enclosure to the podcast feed.

The `dailybreath_daily_content` table is created defensively by the web app when first needed. The MySQL schema is also available in `../sql/dailybreath_web_2_3.sql`. A visitor with no saved theme follows the seasonal setting; a saved theme is preserved. The seasonal palette is Fall from September through November, Forest Dark from December through February, and Forest Light from March through August. The Bible, Tanakh, and Quran artwork previews in Settings select the matching tradition and enable the optional scripture artwork.

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
