# Beyond ID 0.1

Beyond ID 0.1 introduces a focused social sign-in experience.

## Included

- Replaces the email/password form on the main sign-in page with Google, GitHub, and Apple.
- Adds GitHub OAuth profile and verified-email handling.
- Adds Sign in with Apple authorization, callback, token validation, and identity handling.
- Keeps unconfigured providers visible in a consistent disabled state.
- Fixes the collapsed social-button layout on the hosted sign-in page.
- Removes Instagram from the active Beyond ID sign-in allowlist; its legacy configuration is retained but no longer used.
- Displays the Beyond ID 0.1 version in sign-in and admin branding.

## Configuration

Set provider credentials in protected `var/config/live.php` under `oauth`, or use the corresponding environment variables documented by `beyond-id/config/social-auth.php`. Apple requires a Services ID and a current signed client-secret JWT.
