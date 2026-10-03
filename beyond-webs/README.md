# Beyond Webs App v0.0.1

This folder is the document root for `hosting.beyondimagination.co.technology`.

Beyond Webs v0.0.1 is the web VPS request app. Every offered session is on a machine with BIT OS installed. Signed-in users can choose a BIT OS flavour and proposed machine size, save or update one request in the shared Beyond ID database, and view its status in their account. Saving a request does not provision a machine, start usage metering, or charge the user. Rates and availability must be confirmed before a future provisioning flow is enabled. No game or paid third-party software is included.

The request API is `api/session.php`. It accepts authenticated GET and authenticated JSON POST with a session CSRF token. The database table is created by the matching MySQL or SQLite `20260930_01_beyond_webs_requests` migration in `beyond-id/database/migrations/`. If automatic migrations are disabled, apply that migration before deploying this app. Requests stay in `requested` status until a separate provisioning integration is added.

## Host configuration

1. Create an HTTPS subdomain for `hosting.beyondimagination.co.technology` in the hosting control panel.
2. Map its document root to the deployed `beyond-webs/` folder.
3. The production Beyond host shares sessions automatically. Set this optional PHP environment variable on the primary Beyond ID host when an explicit deployment value is preferred:

   ```text
   BEYOND_WEBS_ORIGIN=https://hosting.beyondimagination.co.technology
   ```

4. Confirm that `https://hosting.beyondimagination.co.technology/` loads and that **Continue with Beyond ID** returns to the same subdomain after sign-in.

Beyond ID defaults to this exact HTTPS host and also restricts an explicit `BEYOND_WEBS_ORIGIN` to it. This prevents sign-in returns from going to another external site.
