#!/usr/bin/env bash
set -euo pipefail

# Build the seven official BIT OS flavour tracks with isolated Buildroot
# output directories. Run this script on a Linux build host.
desktop_source=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
action=${1:-configure}
case "$action" in
  configure|build|installer|legal-info) ;;
  *) echo "Usage: bash build-all.sh [configure|build|installer|legal-info]" >&2; exit 2 ;;
esac

build_root=${BEYOND_BUILD_ROOT:-"$desktop_source/out/flavours"}
mkdir -p "$build_root"
manifest="$build_root/release-manifest.tsv"
printf 'flavour\tiso\tusb-image\tsha256-manifest\n' > "$manifest"

run_tree() {
  local flavour=$1
  local tree=$2
  local source="$desktop_source/$tree"
  local output="$build_root/$flavour"
  echo "==> $flavour ($tree): $action"
  BEYOND_BUILD_DIR="$output" bash "$source/build.sh" "$action"
  if [[ "$action" == installer && "$tree" == core && "$flavour" != core ]]; then
    local images="$output/installer-output/images"
    # The shared Core builder deliberately keeps its stable internal names;
    # release bundles get the selected flavour name at the orchestration edge.
    cp "$images/bit-os-core-0.2-installer.img" "$images/bit-os-$flavour-1.0-installer.img"
    cp "$images/bitCoreos.iso" "$images/bit${flavour^}os.iso"
    sha256sum "$images/bit-os-$flavour-1.0-installer.img" "$images/bit${flavour^}os.iso" > "$images/SHA256SUMS"
  fi
  if [[ "$action" == installer ]]; then
    local images="$output/installer-output/images"
    local iso image sums
    case "$flavour" in
      core) iso="$images/bitCoreos.iso"; image="$images/bit-os-core-0.2-installer.img" ;;
      home) iso="$images/bitHomeos.iso"; image="$images/bit-os-home-1.0-installer.img" ;;
      cyber) iso="$images/bitCyberos.iso"; image="$images/bit-os-cyber-1.0-installer.img" ;;
      *) iso="$images/bit${flavour^}os.iso"; image="$images/bit-os-$flavour-1.0-installer.img" ;;
    esac
    sums="$images/SHA256SUMS"
    test -s "$iso" || { echo "Missing ISO for $flavour: $iso" >&2; exit 1; }
    test -s "$image" || { echo "Missing USB image for $flavour: $image" >&2; exit 1; }
    test -s "$sums" || { echo "Missing checksum manifest for $flavour: $sums" >&2; exit 1; }
    printf '%s\t%s\t%s\t%s\n' "$flavour" "$iso" "$image" "$sums" >> "$manifest"
  fi
}

# Core is the release gate and must complete before any derived profile starts.
run_tree core core
run_tree home home
run_tree creator core
run_tree academy core
run_tree cyber cyber
run_tree sentinel core
run_tree gaming core

echo "Completed $action for all seven BIT OS flavours. Outputs: $build_root"
if [[ "$action" == installer ]]; then
  echo "Shared Windows installer: $desktop_source/windows-installer/build.ps1"
  echo "Release manifest: $manifest"
fi
