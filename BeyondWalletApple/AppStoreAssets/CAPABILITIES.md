# Beyond Wallet App Store Capabilities

Recommended starting setup for Apple Developer > Identifiers > Beyond Wallet.

## Enable Now

- Sign in with Apple: use if Beyond ID login supports Apple account auth.
- Associated Domains: use for Beyond ID universal links and web-to-app handoff.
  - `applinks:beyondimagination.co.technology`
- Push Notifications: enable only when wallet alerts, market alerts, or security notifications are ready.

## Usually Not Needed At Launch

- Apple Pay Payment Processing: only needed if the app directly accepts Apple Pay payments.
- Wallet: only needed if issuing Apple Wallet passes.
- NFC Tag Reading / HCE: only needed for NFC card or tap flows.
- Keychain Sharing: only needed if credentials must be shared across multiple Beyond apps in the same access group.
- In-App Purchase: only needed if Beyond ID premium is sold inside the iOS app.

## Capability Requests To Defer

- Finance/payment-specific requests should wait until regulated provider onboarding, KYC/AML, cardholder agreements, funding, and reconciliation flows are production-ready.
- Crypto features should remain watch-only unless a licensed partner flow is approved and reviewed.
