# Peoples Trust + Marqeta setup

Beyond Wallet treats Peoples Trust Company as the planned Canadian issuer/BIN
sponsor and Marqeta as the card processor. The public Marqeta sandbox is for
development only and does not establish a Peoples Trust issuing relationship.

## Required server configuration

Set these values as server environment variables or add the equivalent values
under `payments.marqeta` in the protected `live.php` configuration outside the
public web root:

```text
BEYOND_MARQETA_ENVIRONMENT=sandbox
BEYOND_MARQETA_APPLICATION_TOKEN=<sandbox application token>
BEYOND_MARQETA_ADMIN_ACCESS_TOKEN=<sandbox admin access token>
BEYOND_MARQETA_CARD_PRODUCT_TOKEN=<optional token returned by GET /cardproducts>
BEYOND_CARD_ISSUER=peoples_trust
```

Never commit the admin access token. The application reads these values only on
the server. If the card-product token is omitted, the sandbox integration uses
the most recently modified product returned by `/cardproducts`.

## Webhook

Create three independent random values:

```text
BEYOND_MARQETA_WEBHOOK_SECRET=<12-character Marqeta HMAC secret>
BEYOND_MARQETA_WEBHOOK_USERNAME=<12-character basic-auth username>
BEYOND_MARQETA_WEBHOOK_PASSWORD=<12-character basic-auth password>
```

In the Marqeta sandbox dashboard, create a webhook pointing to:

```text
https://beyondimagination.co.technology/beyond-id/api/marqeta-webhook.php
```

Configure HMAC SHA-256, Basic Authentication, and initially subscribe to:

```text
usertransition.*
cardtransition.*
transaction.*
```

The endpoint verifies both Basic Authentication and `X-Marqeta-Signature`.
Webhook events never modify bit$ Rewards or Beyond Cash balances. Provider
reconciliation must be implemented separately before real money is enabled.

## Production gate

Do not set `BEYOND_MARQETA_ENVIRONMENT=production` until Peoples Trust/Marqeta
have approved the program, supplied production credentials and card products,
and confirmed KYC/AML, funding, disclosures, cardholder agreements, support,
disputes, safeguarding and reconciliation responsibilities. Production API
calls also require the deliberate server-side gate:

```text
BEYOND_MARQETA_PRODUCTION_ENABLED=true
```
