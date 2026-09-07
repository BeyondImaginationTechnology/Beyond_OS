# BIT OS Core validation

Status: **source scaffold created; no Core image has been built or booted.**

The Core tree has a separate external identifier, package, defconfig names,
UEFI artifact names and installer entry. It shares upstream pins with the
first-generation BIT OS editions but must receive its own clean-build and boot
evidence.

Next run on an x86-64 Linux build host:

```sh
bash build.sh configure
bash build.sh build
bash build.sh installer
python3 tests/post-build-test.py
```

Then complete the UEFI and disposable-disk checks in `RELEASE.md`.