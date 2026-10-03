# Beyond Health Android v0.0.2

Native, offline Android companion for API 26 and later.

Open this folder in Android Studio and build the `app` module. The app uses the standard Android SDK and no third-party libraries. It has Today, Calendar, Journal, Practices, Insights, and Settings. Calendar plans breakfast, lunch, and dinner from the shared Beyond Kitchen recipe catalog. Each morning has an available prep-time setting and a breakfast suggestion that fits it. The current week's recipes create an ingredient list. Check-ins, journal notes, and meal plans are stored in private app preferences. The catalog is bundled at build time from `../beyond-kitchen/data/recipes.json`. Android backup and network access are disabled.

This release does not sync with the web app or iOS.
