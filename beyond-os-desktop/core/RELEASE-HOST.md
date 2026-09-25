# Core v0.2 release-host handoff

The release host must serve the candidate files as static objects. A proxy or
application error on `/releases/` is a release blocker.

## Publish

From the clean Linux build output:

```sh
cd out/installer-output/images
gzip -c bit-os-core-0.2-installer.img > bit-os-core-0.2-installer.img.gz
gzip -c bitCoreos.iso > bitCoreos.iso.gz
sha256sum bitCoreos.iso.gz bit-os-core-0.2-installer.img.gz coreOS.exe > SHA256SUMS
```

Copy all three files into the host's static document root at:

```text
releases/core/0.2/
```

The directory must be readable by the web server and must not be routed to a
dynamic application handler. Confirm from an external network:

```sh
curl --fail --location --head https://os.beyondimagination.co.technology/releases/core/0.2/SHA256SUMS
curl --fail --location --head https://os.beyondimagination.co.technology/releases/core/0.2/bitCoreos.iso.gz
curl --fail --location --head https://os.beyondimagination.co.technology/releases/core/0.2/bit-os-core-0.2-installer.img.gz
curl --fail --location --head https://os.beyondimagination.co.technology/releases/core/0.2/coreOS.exe
```

Then run `tests/verify-release-artifacts.sh`. Do not publish if any request is
not HTTP 200 or if the hashes do not match.

## Diagnose HTTP 500

Check the reverse-proxy and application error logs, confirm the files exist in
the configured document root, and verify that `/releases/` is handled by the
static-file location. A CDN cache must be purged after replacing a failed
object. The repository cannot mark this gate passed while these checks fail.
