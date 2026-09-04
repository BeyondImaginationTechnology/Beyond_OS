# Beyond OS Web 1.0

This is the document root for `os.beyondimagination.co.technology` and is intentionally located at `beyond/os/` to match the StartCP mapping.

Enable the following deployment variables together once the HTTPS subdomain is live:

```text
BEYOND_SESSION_COOKIE_DOMAIN=.beyondimagination.co.technology
BEYOND_OS_ORIGIN=https://os.beyondimagination.co.technology
```

Sessions stay host-only by default. The login redirect only permits the precise HTTPS Beyond OS origin when that variable is configured; arbitrary cross-origin return URLs remain blocked.
