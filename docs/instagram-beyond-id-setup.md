# Instagram sign-in for Beyond ID

Beyond ID uses **Instagram API with Instagram Login**, not Facebook Login. It supports Instagram professional accounts (Business and Creator). Personal Instagram accounts cannot use this OAuth flow.

## Meta developer setup

1. Open the app in the [Meta App Dashboard](https://developers.facebook.com/apps/) and add the **Instagram API** use case.
2. In **API setup with Instagram login**, add this exact OAuth redirect URI:

   `https://beyondimagination.co.technology/beyond-id/auth/oauth-callback.php?provider=instagram`

3. Enable the `instagram_business_basic` permission. Beyond ID does not request publishing or messaging permissions.
4. While the Meta app is in development mode, add each test Instagram professional account as an Instagram tester. Complete Meta App Review before enabling sign-in for the public.
5. Add the Instagram App ID and App Secret to the protected production configuration at `var/config/live.php`:

```php
'oauth' => [
    'instagram' => [
        'app_id' => 'YOUR_INSTAGRAM_APP_ID',
        'app_secret' => 'YOUR_INSTAGRAM_APP_SECRET',
        'graph_version' => 'v26.0',
    ],
],
```

Environment variables `BEYOND_INSTAGRAM_APP_ID`, `BEYOND_INSTAGRAM_APP_SECRET`, and `BEYOND_INSTAGRAM_GRAPH_VERSION` can be used instead. Never commit live secrets.

## Account behavior

Instagram does not return a verified email in this flow. A first-time Instagram user must therefore either:

- sign in to an existing Beyond ID and explicitly link Instagram; or
- enter an email for a new Beyond ID and verify it before Instagram sign-in is allowed.

Beyond ID stores the Instagram account identifier and username, but does not store the short-lived Instagram access token.
