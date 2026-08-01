# Beyond Pay Wallet Apple

Native SwiftUI iOS shell for Beyond Pay Wallet.

## Features

- bit$ reward balance with USD/CAD comparison
- gate-free stock market and crypto viewing
- gate-free wallet balance viewing
- gate-free watchlist, top mover, conversion, and public wallet balance surfaces
- gate-free weekly $5-$10 micro investing ideas framed as education, not personal advice
- premium Beyond ID wall for cash, card controls, managing watched addresses, and full activity
- provider-backed CAD/USD cash account placeholders
- Marqeta sandbox card program status
- watch-only BTC/ETH/SOL public address tracking
- wallet activity feed

The app intentionally keeps bit$ Rewards, Beyond Cash, Beyond Card, and crypto watch-only data separated to match the server-side wallet model.

## Build

Generate the Xcode project with XcodeGen:

```sh
xcodegen generate --spec BeyondWalletApple/project.yml
```

Then open `BeyondWalletApple/Beyond Pay Mobile.xcodeproj`.
