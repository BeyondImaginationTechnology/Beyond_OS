# Beyond Baby Names web app

The production web companion for Beyond Baby Names 1.0.

Features include smart local recommendations, search and filters, name stories, swipe decisions, a private favorites shortlist, couple matches, twin pair ideas, a private family-name rhythm and initials preview, and installable/offline PWA support. Personal choices are stored in the browser with `localStorage`; no shortlist or family-name data is sent to the server.

Open `/beyond-baby-names/` through the repository’s PHP web server. The app has no front-end build step or third-party runtime dependency.

Couple Mode uses the same-origin `/beyond-baby-names/api/couples.php` endpoint.
The creator or partner bearer credential is kept in `sessionStorage`; local
preferences and the family-name preview remain in `localStorage`.
