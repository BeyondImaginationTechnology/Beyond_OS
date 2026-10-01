# Beyond Tattoo operations

## Autumn Ink stylesheets

`stylesheets.php` is the public landing page for the October 1–31 Autumn Ink series. Each release has exactly one dedicated image asset. The catalog in `data/autumn-ink-stylesheets.json` maps each numbered release to its date, title, guide details and a single file under `assets/stylesheets/`. The calendar marks a release available only after its date and when that file exists; add each new release to the JSON and upload its single asset to publish it. The same asset powers the preview, download and print view.

## Version 1.2 nightly stencil publication

The 55-drop catalog is a schedule, not a claim that all files exist. The public web library and Apple API scan the actual asset folders and expose a drop only when it is approved, its release date has arrived, a preview exists, and either `stencil-print-ready.png` or the official `stencil-outline.png` exists.

1. Open Beyond Studio → Beyond Tattoo → 55-drop assets.
2. Choose an asset role and upload one file or a filename-mapped batch. Supported roles are preview, official outline stencil, print master, transfer PNG, printable PDF, reference artwork, placement mockup, packaging, lore card, and style card.
3. Repeat until the drop has at least its preview and print master. Other files are optional and appear automatically when present.
4. Add the approval description, style, recommended body placements, and difficulty when useful. Review the files, confirm Beyond Tattoo has publishing permission, then select **Approve drop**.
5. An approved future drop remains private until its scheduled Vancouver release date. A draft is never returned by the storefront, ZIP download, library API, or Apple apps.
6. Use **Published** to return a drop to draft immediately if a correction is needed.

Uploaded files live under `uploads/stencil-library/<collection>/<drop>/`; bundled release assets live under `assets/stencils/`. `config/stencil-day.php` resolves the latest released, approved asset automatically. Apple clients refresh `api/library.php`, so nightly additions do not require a new App Store build.

Every upload is size/type checked, requires at least 600px on each image side, and records a SHA-256 hash so the same role cannot be accidentally assigned to two drops. Approval records `approved_at`, the approving administrator, and a rights-confirmation flag in `metadata.json`. Replacing any file automatically returns the drop to draft for another review.

## Legacy JSON import

Tattoo profiles, tattoos, healing metadata, beta signups, studio opportunities, and invitations now use the shared Beyond ID database. The previous repository contains no legacy users or tattoos and one beta signup. After deployment migrations run, execute `php tools/migrate-beyond-tattoo-json.php` once to copy that signup. The command stops for manual identity review if legacy users or tattoos are ever present.
