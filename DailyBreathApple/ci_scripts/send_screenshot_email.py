#!/usr/bin/env python3
"""Email the generated DailyBreath App Store screenshots from CI.

All credentials are supplied as secret environment variables by Azure DevOps.
This script deliberately prints only non-sensitive delivery metadata.
"""

from __future__ import annotations

import os
import smtplib
import sys
from email.message import EmailMessage
from pathlib import Path


def value(*names: str, default: str = "") -> str:
    for name in names:
        candidate = os.environ.get(name, "").strip()
        if candidate and not candidate.startswith("$("):
            return candidate
    return default


def main() -> int:
    archive = Path(sys.argv[1])
    if not archive.is_file():
        raise FileNotFoundError(f"Screenshot archive was not found: {archive}")

    host = value("SMTP_HOST", "SMTP_SERVER")
    username = value("SMTP_USERNAME", "SMTP_USER")
    password = value("SMTP_PASSWORD", "SMTP_PASS")
    sender = value("SMTP_FROM", default=username)
    recipient = value("SCREENSHOT_EMAIL_TO", "SMTP_TO", default="admin@beyondimagination.co.technology")
    port = int(value("SMTP_PORT", default="465"))
    security = value("SMTP_SECURITY", "SMTP_USE_SSL", default="ssl").lower()

    missing = [
        label
        for label, setting in (("SMTP host", host), ("SMTP username", username), ("SMTP password", password), ("SMTP sender", sender))
        if not setting
    ]
    if missing:
        raise RuntimeError("Missing required CI email settings: " + ", ".join(missing))

    message = EmailMessage()
    message["Subject"] = "DailyBreath App Store screenshots — build " + value("CI_BUILD_NUMBER", default="unknown")
    message["From"] = sender
    message["To"] = recipient
    message.set_content(
        "DailyBreath iPhone and iPad App Store screenshots are attached.\n"
        "The same ZIP is available in the Azure Pipeline artifact named dailybreath-app-store-screenshots."
    )
    message.add_attachment(archive.read_bytes(), maintype="application", subtype="zip", filename=archive.name)

    implicit_tls = security in {"ssl", "true", "1", "implicit"}
    client: smtplib.SMTP
    if implicit_tls:
        client = smtplib.SMTP_SSL(host, port, timeout=30)
    else:
        client = smtplib.SMTP(host, port, timeout=30)
        client.ehlo()
        if security not in {"none", "plain", "false", "0"}:
            client.starttls()
            client.ehlo()

    with client:
        client.login(username, password)
        client.send_message(message)

    print(f"Sent {archive.name} to {recipient} via {host}:{port}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
