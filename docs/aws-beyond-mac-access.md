# Beyond Mac AWS Access Guide

## AWS resources

- Region: `ca-central-1` (Canada Central)
- macOS instance: `macos-dev` (`i-07f198317874fd129`)
- Windows gateway: `Codex` (`i-0d45189d24ff24b5e`)
- Fleet Manager link: <https://ca-central-1.console.aws.amazon.com/systems-manager/fleet-manager/remote-desktop?region=ca-central-1&nodeIds=i-0d45189d24ff24b5e>

## Start and connect

1. Sign in to AWS and select `ca-central-1`.
2. In EC2, start `macos-dev` first and then `Codex` if they are stopped.
3. Wait until both instances pass their status checks.
4. Open the Fleet Manager link above.
5. Connect with the Windows `Administrator` account and the current Windows password. Do not use the original AWS-generated password after it has been reset.
6. Wait 5–10 seconds after the Windows desktop loads.
7. Double-click **Beyond Mac Desktop**.
8. Enter the separate VNC password if prompted.
9. At the macOS lock screen, use the macOS account password if requested.

## Mobile or another computer

- Use Chrome or Edge and sign in to the same AWS account.
- On a phone or tablet, enable **Desktop site** and use landscape orientation.
- A tablet with a keyboard and mouse is much easier than a phone.
- Prefer Fleet Manager over public RDP; Fleet Manager does not require opening an inbound RDP port.

## Troubleshooting

- If Beyond Mac closes immediately, wait 10 seconds and open it again. The encrypted SSH tunnel may still be starting.
- If Fleet Manager rejects the login, confirm that the newer Windows password is being used.
- The VNC password and the macOS account password are different credentials.
- VNC port `5900` is not public. Windows reaches it through an encrypted SSH tunnel on local port `5901`.
- Do not terminate either instance. Stop them when appropriate instead.

Passwords are intentionally not stored in this file.
