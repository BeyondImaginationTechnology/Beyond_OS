#!/usr/bin/env python3
"""Send a minimal SMTP delivery check from the DailyBreath CI environment."""

from __future__ import annotations

import os
import smtplib
from email.message import EmailMessage


def value(*names: str, default: str = "") -> str:
    for name in names:
        candidate = os.environ.get(name, "").strip()
        if candidate and not candidate.startswith("$("):
            return candidate
    return default


def main() -> int:
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
    message["Subject"] = "DailyBreath SMTP delivery test"
    message["From"] = sender
    message["To"] = recipient
    message.set_content("Hello word")

    implicit_tls = security in {"ssl", "true", "1", "implicit"}
    if implicit_tls:
        client: smtplib.SMTP = smtplib.SMTP_SSL(host, port, timeout=30)
    else:
        client = smtplib.SMTP(host, port, timeout=30)
        client.ehlo()
        if security not in {"none", "plain", "false", "0"}:
            client.starttls()
            client.ehlo()

    with client:
        client.login(username, password)
        client.send_message(message)

    print(f"Sent SMTP delivery test to {recipient} via {host}:{port}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
