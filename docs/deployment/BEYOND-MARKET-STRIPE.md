# Beyond Market Stripe activation

Beyond Market uses Stripe-hosted Checkout. Product names and prices are read from the local `listings` table on the server; the browser cannot choose the amount charged.

## 1. Add protected credentials

In `$BEYOND_VAR_PATH/config/live.php`, complete the existing Stripe section:

```php
'stripe' => [
    'public_key' => 'pk_live_...',
    'secret_key' => 'sk_live_...',
    'webhook_secret' => 'whsec_...',
    // Keep any existing Academy Stripe entries here too.
],
```

The public key is retained for other Stripe surfaces. Beyond Market hosted Checkout uses the secret key and webhook secret on the server.

## 2. Register the webhook

Create a Stripe webhook endpoint pointing to:

```text
https://YOUR-DOMAIN/beyond-sell/stripe-webhook.php
```

Subscribe it to:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `checkout.session.expired`

Copy that endpoint's `whsec_...` signing secret into `stripe.webhook_secret`. The Academy webhook secret is separate.

## 3. Confirm prerequisites

- Import `sql/beyond-os-2.1-beta-database.sql` if the marketplace tables are not installed.
- Ensure PHP 8.1+, PDO MySQL, JSON, mbstring, and cURL are enabled.
- Set `app.url` to the public HTTPS origin in the protected configuration.
- Keep `$BEYOND_VAR_PATH` outside the public web root.

## 4. Test before going live

Use Stripe test keys first. Publish a cash-priced digital listing, buy it with a separate Beyond ID, and confirm:

1. Stripe redirects back to the confirmation page.
2. The order changes from `pending_payment` to `paid`.
3. The payment appears in **My Orders**.
4. A paid digital asset can be downloaded only by its buyer.
5. Replaying the same webhook does not duplicate fulfillment or decrement inventory twice.

Switch all three Stripe values to live-mode credentials only after the test flow passes.
