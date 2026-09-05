# Beyond OS 1.0 Build Map

Beyond Imagination Technology 3.0 is the public ecosystem. Beyond OS 1.0 is the operating layer behind it.

| Track | Initial deliverable | Current repository location |
| --- | --- | --- |
| Web | Protected command-center overview | `beyond/os/` |
| Desktop | Independent Linux Home Edition build and native desktop | `beyond-os-desktop/home/` |
| Mobile | AOSP community plan plus Android companion | `beyond-os-mobile/`, `BeyondOSAndroid/` |
| iOS | Native companion shell | `BeyondOSApple/` |

## Shared identity

Beyond ID is the identity layer. Production hosting must enable `BEYOND_SESSION_COOKIE_DOMAIN=.beyondimagination.co.technology` and `BEYOND_OS_ORIGIN=https://os.beyondimagination.co.technology` together before `os.beyondimagination.co.technology` is live. The repository keeps host-only sessions as the default until that deployment policy is approved.

## Release order

1. Deploy Beyond OS Web to a staging subdomain and validate Beyond ID handoff.
2. Freeze Web visual tokens and reuse them in the native companion apps.
3. Compile the independent Home Linux image and test its boot and native desktop in QEMU.
4. Select exactly one AOSP device after its upstream maintainability is verified.
5. Run iOS companion QA independently of any jailbreak work.
