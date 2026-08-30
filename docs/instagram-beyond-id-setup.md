# Instagram sign-in for Beyond ID

Beyond ID uses **Instagram API with Instagram Login**, not Facebook Login. It supports Instagram professional accounts (Business and Creator). Personal Instagram accounts cannot use this OAuth flow.

## Meta developer setup

1. Open the app in the [Meta App Dashboard](https://developers.facebook.com/apps/) and add the **Instagram API** use case.
2. In **API setup with Instagram login**, add this exact OAuth redirect URI:

   `https://beyondimagination.co.technology/beyond-id/auth/oauth-callback.php`

   The Instagram callback intentionally has no query string. Beyond ID identifies the provider from the short-lived, server-side OAuth flow stored in the user's session. The value in Meta must exactly match the value sent during authorization and token exchange.

3. Enable the `instagram_business_basic` permission. Beyond ID does not request publishing or messaging permissions.
4. While the Meta app is in development mode, add each test Instagram professional account as an Instagram tester and accept the invitation from that Instagram account.
5. Use **Standard Access** only for professional accounts owned or managed by the app team. Request **Advanced Access** and complete Meta App Review before offering sign-in to professional accounts outside the app team.
6. Add the Instagram App ID and App Secret to the protected production configuration at `var/config/live.php`:

```php
'oauth' => [
    'instagram' => [
        'app_id' => 'YOUR_INSTAGRAM_APP_ID',
        'app_secret' => 'YOUR_INSTAGRAM_APP_SECRET',
    ],
],
```

Environment variables `BEYOND_INSTAGRAM_APP_ID` and `BEYOND_INSTAGRAM_APP_SECRET` can be used instead. Never commit live secrets.

## Account behavior

Instagram does not return a verified email in this flow. A first-time Instagram user must therefore either:

- sign in to an existing Beyond ID and explicitly link Instagram; or
- enter an email for a new Beyond ID and verify it before Instagram sign-in is allowed.

Beyond ID stores the Instagram account identifier and username, but does not store the short-lived Instagram access token.

## Production verification checklist

- The Beyond ID login page shows an active **Continue with Instagram** button.
- Starting the flow reaches Instagram with `instagram_business_basic` and the exact callback URL above.
- A Business or Creator test account can consent and return to Beyond ID.
- The callback fetches the profile from `https://graph.instagram.com/me?fields=user_id,username,account_type`; do not replace `/me` with the returned `user_id` path.
- A signed-in Beyond ID can link Instagram from **Dashboard → Security**.
- A first-time Instagram user is asked for a verified Beyond ID email because Instagram does not provide one.
- Cancelling or denying consent returns a readable error and does not create or link an account.
- The Meta app remains limited to app-role/test accounts until Advanced Access is approved.
