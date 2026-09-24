#!/bin/sh
# Revalidate that the published candidate files are reachable and match SHA256SUMS.
set -eu

base_url=${1:-https://os.beyondimagination.co.technology/releases/core/0.2}
command -v curl >/dev/null 2>&1 || { echo "Missing host tool: curl" >&2; exit 1; }
command -v sha256sum >/dev/null 2>&1 || { echo "Missing host tool: sha256sum" >&2; exit 1; }

work_dir=$(mktemp -d)
cleanup() { rm -rf "$work_dir"; }
trap cleanup EXIT HUP INT TERM

fetch() {
    url=$1
    output=$2
    status=$(curl --silent --show-error --location --retry 3 --retry-all-errors \
      --output "$output" --write-out '%{http_code}' "$url" || true)
    case "$status" in
        2??) ;;
        *) echo "BLOCKED: $url returned HTTP $status" >&2; exit 1 ;;
    esac
}

fetch "$base_url/SHA256SUMS" "$work_dir/SHA256SUMS"
while IFS='  ' read -r digest name; do
    [ -n "$digest" ] || continue
    case "$name" in
        bitCoreos.iso|bit-os-core-0.2-installer.img) ;;
        *) continue ;;
    esac
    fetch "$base_url/$name" "$work_dir/$name"
done < "$work_dir/SHA256SUMS"

(cd "$work_dir" && sha256sum -c SHA256SUMS)
echo "PASS: release artifacts are reachable and match SHA256SUMS"
