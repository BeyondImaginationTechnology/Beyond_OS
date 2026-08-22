# DailyBreath Web 1.2

DailyBreath is an installable Beyond OS progressive web app for daily Scripture, narrated Bible reading, devotionals, guided breathing, encrypted reflection journaling, weekly challenges, activity history, and recovery support.

## Install

Open `/dailybreath/` from the Beyond OS App Store. In a supported browser, use the **Install DailyBreath** prompt or the browser’s “Add to Home Screen” command. The installed app launches in its own standalone window and includes shortcuts for Today, Breathe, Journal, and Weekly Challenge.

The app requires HTTPS in production for service workers and installation. Localhost is permitted for development.

## Persistence

- Reflection journal entries are encrypted on the server and attached to the signed-in Beyond ID.
- Breathing sessions and database-backed weekly challenge progress are persisted in the Beyond OS database.
- Bundled recovery challenge progress uses `dailybreath_challenge_progress`, created defensively at runtime and included in `sql/dailybreath_web_1_2.sql` for production migrations.
- Theme, narration speed, and reduced-motion preferences remain in browser storage on the current device.

## Content and safety

The Verse of the Day and devotional use current dated content with bundled recovery fallbacks. Bible text is the public-domain World English Bible (WEBP). Recovery content is general faith-centered wellness support, not medical care, and the support page links to official 988 and SAMHSA resources.
