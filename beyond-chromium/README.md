# Beyond Chromium

The controller-first desktop browser for Beyond OS, powered by Chromium. It opens the live Beyond OS home page by default while retaining normal keyboard and mouse browsing.

## Run

1. Install Node.js 20 or newer.
2. Run `npm install`.
3. Run `npm start`.
4. Pair a controller with Windows over Bluetooth or connect it by USB.
5. Press any controller button while Beyond Chromium is focused.

## Development

Run `npm run check` to validate the desktop process, preload bridge and controller interface scripts.

## Default controls

| Control | Action |
| --- | --- |
| Left stick | Move pointer |
| Right stick | Scroll |
| A / Cross | Click |
| B / Circle | Back |
| X / Square | On-screen keyboard |
| Y / Triangle | Focus address bar |
| Menu / Options | Refresh |

Keyboard and mouse controls continue to work normally.
