"""Fail if Buildroot silently drops a requested setting or enables root login."""
import re
import sys
from pathlib import Path

def parse(path):
    result = {}
    for line in Path(path).read_text().splitlines():
        if match := re.fullmatch(r"(BR2_[A-Za-z0-9_]+)=(.*)", line):
            result[match[1]] = match[2]
        elif match := re.fullmatch(r"# (BR2_[A-Za-z0-9_]+) is not set", line):
            result[match[1]] = "n"
    return result

requested, resolved = map(parse, sys.argv[1:3])
errors = []
for key, expected in requested.items():
    actual = resolved.get(key, "n")
    if expected != actual:
        errors.append(f"{key}: requested {expected}, resolved {actual}")
for key in ("BR2_TARGET_ENABLE_ROOT_LOGIN", "BR2_PACKAGE_DROPBEAR", "BR2_PACKAGE_OPENSSH"):
    if resolved.get(key) == "y":
        errors.append(f"{key} must be disabled in this development profile")
if errors:
    raise SystemExit("\n".join(errors))
print(f"PASS: {len(requested)} requested settings retained; root login and SSH disabled")
