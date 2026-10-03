#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
export GIT_TERMINAL_PROMPT=0

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPOSITORY_ROOT="$(cd -- "${SCRIPT_DIR}/.." && pwd)"
ACCOUNT_ROOT="$(cd -- "${REPOSITORY_ROOT}/.." && pwd)"
DEFAULT_PUBLIC_ROOT="${ACCOUNT_ROOT}/public_html"
if [[ "$(basename -- "${REPOSITORY_ROOT}")" == "www" ]]; then
  DEFAULT_PUBLIC_ROOT="${REPOSITORY_ROOT}"
fi
PUBLIC_ROOT="${BEYOND_PUBLIC_ROOT:-${DEFAULT_PUBLIC_ROOT}}"
PRIVATE_ROOT="${BEYOND_VAR_PATH:-${ACCOUNT_ROOT}/var}"

[[ -d "${REPOSITORY_ROOT}/.git" ]] || { echo "Repository metadata is unavailable." >&2; exit 1; }
[[ -d "${PUBLIC_ROOT}" ]] || { echo "Public web root does not exist: ${PUBLIC_ROOT}" >&2; exit 1; }
command -v git >/dev/null 2>&1 || { echo "git is unavailable." >&2; exit 1; }
if [[ "$(cd -- "${PUBLIC_ROOT}" && pwd)" != "${REPOSITORY_ROOT}" ]]; then
  command -v rsync >/dev/null 2>&1 || { echo "rsync is unavailable." >&2; exit 1; }
fi

cd "${REPOSITORY_ROOT}"
CURRENT_BRANCH="$(git symbolic-ref --quiet --short HEAD || true)"
[[ "${CURRENT_BRANCH}" == "main" ]] || { echo "Refusing to deploy branch '${CURRENT_BRANCH:-detached}'. Expected main." >&2; exit 1; }
# Generated lesson narration is runtime data and intentionally lives outside Git.
# Keep the safety check strict for tracked edits and every other untracked path.
TRACKED_CHANGES="$(git diff --name-only; git diff --cached --name-only)"
[[ -z "${TRACKED_CHANGES}" ]] || { echo "Refusing to deploy a repository with tracked local changes." >&2; exit 1; }

git fetch --prune origin main
git merge --ff-only origin/main

# Check after the fast-forward so newly added ignore rules can account for
# host-side convenience files without deleting or staging them.
UNEXPECTED_UNTRACKED="$(git ls-files --others --exclude-standard | grep -v '^dailybreath/assets/audio/' || true)"
if [[ -n "${UNEXPECTED_UNTRACKED}" ]]; then
  printf 'Refusing to deploy unexpected untracked files:\n%s\n' "${UNEXPECTED_UNTRACKED}" >&2
  exit 1
fi

# Git respects the private umask for newly checked-out files and directories.
# Make only tracked content web-readable; ignored config and runtime data stay private.
while IFS= read -r -d '' tracked_file; do
  tracked_dir="${tracked_file%/*}"
  while [[ "${tracked_dir}" != "${tracked_file}" ]]; do
    printf '%s\0' "${tracked_dir}"
    [[ "${tracked_dir}" == */* ]] || break
    tracked_dir="${tracked_dir%/*}"
  done
done < <(git ls-files -z) | sort -zu | xargs -0 -r chmod a+rx
git ls-files -z | xargs -0 -r chmod a+r

if [[ "$(cd -- "${PUBLIC_ROOT}" && pwd)" != "${REPOSITORY_ROOT}" ]]; then
  rsync -a --delay-updates \
    --exclude='/.git/' --exclude='/.github/' --exclude='/.cache/' \
    --exclude='/var/' --exclude='/.tmp-dailybreath-var/' --exclude='/config/live.php' \
    --exclude='/docs/' --exclude='/tools/' --exclude='/sql/' --exclude='/exports/' --exclude='/outputs/' \
    --exclude='/AppStoreAssets/' --exclude='/*Apple/' --exclude='/*Android/' \
    --exclude='/.gitattributes' --exclude='/.gitignore' \
    --exclude='/README.md' --exclude='/CONTRIBUTING.md' --exclude='/SECURITY.md' --exclude='/LICENSE' \
    --exclude='/*.csr' --exclude='/azure-pipelines*.yml' \
    "${REPOSITORY_ROOT}/" "${PUBLIC_ROOT}/"
fi

# The private umask protects deployment state, but Git may create newly checked-out
# public assets with those same restrictive permissions. Set the public assets this
# deploy depends on to web-readable mode after checkout.
for asset in \
  assets/icons/apple-continue-button.png \
  assets/icons/github-invertocat-white.png \
  beyond-tattoo/manifest.php \
  beyond-tattoo/service-worker.js \
  beyond-tattoo/offline.html \
  beyond-tattoo/assets/css/responsive.css \
  beyond-tattoo/assets/js/pwa.js \
  beyond-tattoo/assets/icons/beyond-tattoo-192.png \
  beyond-tattoo/assets/icons/beyond-tattoo-512.png \
  beyond-tattoo/downloads/tattoo-procedure-consent-bc.pdf \
  dailybreath/assets/js/web-app.js \
  dailybreath/manifest.webmanifest \
  dailybreath/service-worker.js \
  dailybreath/assets/css/bible-forest.css \
  dailybreath/assets/css/tanakh-forest.css \
  dailybreath/assets/css/quran-forest.css \
  dailybreath/assets/images/bible-forest-landscape.png \
  dailybreath/assets/images/bible-forest-portrait.png \
  dailybreath/assets/images/tanakh-forest-landscape.png \
  dailybreath/assets/images/tanakh-forest-portrait.png \
  dailybreath/assets/images/quran-forest-landscape.png \
  dailybreath/assets/images/quran-forest-portrait.png; do
  ASSET_PATH="${PUBLIC_ROOT}/${asset}"
  [[ -f "${ASSET_PATH}" ]] || { echo "Required public asset is missing: ${ASSET_PATH}" >&2; exit 1; }
  chmod 0644 "${ASSET_PATH}"
done

DEPLOY_COMMIT="$(git rev-parse HEAD)"
DEPLOYED_AT="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
DEPLOY_STATE_DIR="${PRIVATE_ROOT}/deployments"
mkdir -p "${DEPLOY_STATE_DIR}"
chmod 700 "${DEPLOY_STATE_DIR}"
STATUS_TMP="$(mktemp "${DEPLOY_STATE_DIR}/.status.XXXXXX")"
printf '{\n  "result": "success",\n  "message": "Deployment completed successfully.",\n  "branch": "main",\n  "commit": "%s",\n  "requested_at": "",\n  "started_at": "%s",\n  "finished_at": "%s"\n}\n' "${DEPLOY_COMMIT}" "${DEPLOYED_AT}" "${DEPLOYED_AT}" > "${STATUS_TMP}"
chmod 600 "${STATUS_TMP}"
mv -f "${STATUS_TMP}" "${DEPLOY_STATE_DIR}/status.json"

echo "Deployed ${DEPLOY_COMMIT:0:12} from main to ${PUBLIC_ROOT}."
