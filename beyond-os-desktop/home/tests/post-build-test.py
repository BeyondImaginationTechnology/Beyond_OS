"""Exercise identity and startup overrides in a disposable target tree.
Usage: python3 tests/post-build-test.py [path-to-bash]
"""
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

root = Path(__file__).resolve().parents[1]
bash = sys.argv[1] if len(sys.argv) > 1 else "bash"
with tempfile.TemporaryDirectory(prefix="beyond-home-hook-") as temporary:
    target = Path(temporary)
    shutil.copytree(root / "overlay", target, dirs_exist_ok=True)
    (target / "usr/lib").mkdir(parents=True, exist_ok=True)
    (target / "etc/os-release").write_text('ID=old-system\n')
    (target / "usr/lib/os-release").write_text('ID=old-system\n')
    (target / "etc/init.d/S40xorg").write_text("legacy display startup\n")
    subprocess.run([bash, (root / "board/x86_64/post-build.sh").as_posix(),
                    target.as_posix()], check=True)
    identity = (target / "etc/os-release").read_text()
    assert 'ID=beyond-os\n' in identity
    assert 'PRETTY_NAME="Beyond OS Home Edition 1.0 (Development)"' in identity
    assert (target / "usr/lib/os-release").read_text() == identity
    assert not (target / "etc/init.d/S40xorg").exists()
    assert (target / "etc/init.d/S99beyond-home").exists()
    assert (target / "home/home/Documents/Welcome.txt").exists()
    assert (target / "etc/issue").read_text().startswith("Beyond OS Home Edition")
print("PASS: target identity, single display startup, and welcome document")
