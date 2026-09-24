"""Fail if Buildroot silently drops a requested setting or enables root login."""
import os
import re
import sys
from pathlib import Path

CORE_ROOT = Path(__file__).resolve().parents[1]

def default_configs():
    candidates = [
        CORE_ROOT / "out/output/.config",
        CORE_ROOT / "out/installer-output/.config",
    ]
    build_dir = os.environ.get("BEYOND_BUILD_DIR")
    if build_dir:
        base = Path(build_dir)
        candidates.extend((base / "output/.config", base / "installer-output/.config"))
    return tuple(dict.fromkeys(candidates))

def resolved_config(argument=None):
    if argument:
        path = Path(argument)
        if not path.is_absolute() and not path.is_file():
            path = CORE_ROOT / path
        if path.is_file():
            return path
        raise SystemExit(f"resolved Buildroot config does not exist: {path}")
    for path in default_configs():
        if path.is_file():
            return path
    choices = ", ".join(str(path) for path in default_configs())
    raise SystemExit(f"no generated Buildroot config found; checked: {choices}")

def parse(path):
    result = {}
    for line in Path(path).read_text().splitlines():
        if match := re.fullmatch(r"(BR2_[A-Za-z0-9_]+)=(.*)", line):
            result[match[1]] = match[2]
        elif match := re.fullmatch(r"# (BR2_[A-Za-z0-9_]+) is not set", line):
            result[match[1]] = "n"
    return result

if len(sys.argv) not in (2, 3):
    raise SystemExit("usage: verify-config.py REQUESTED_DEFCONFIG [RESOLVED_CONFIG]")

requested = parse(sys.argv[1])
resolved = parse(resolved_config(sys.argv[2] if len(sys.argv) == 3 else None))
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
