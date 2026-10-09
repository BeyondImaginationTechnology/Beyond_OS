#!/usr/bin/env bash
set -Eeuo pipefail
umask 022
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
if [[ -n "${CURRENT_BRANCH}" && "${CURRENT_BRANCH}" != "main" ]]; then
  git checkout -f main || true
fi

# Discard tracked local edits on production host so fast-forward merge never fails
TRACKED_CHANGES="$(git diff --name-only; git diff --cached --name-only)"
if [[ -n "${TRACKED_CHANGES}" ]]; then
  echo "Cleaning local tracked changes before fast-forwarding to origin/main..."
  git reset --hard HEAD || true
fi

PREV_COMMIT="$(git rev-parse HEAD 2>/dev/null || echo "000000000000")"

# Fetch latest main branch from GitHub
git fetch --prune origin main
git reset --hard origin/main

NEW_COMMIT="$(git rev-parse HEAD)"
DEPLOYED_AT="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"

# Make tracked content web-readable
git ls-files -z | xargs -0 -r chmod a+r 2>/dev/null || true

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

# Set public permissions for required assets
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
  if [[ -f "${ASSET_PATH}" ]]; then
    chmod 0644 "${ASSET_PATH}" 2>/dev/null || true
  fi
done

if [[ -d "${PUBLIC_ROOT}/beyond-tv/assets/media" ]]; then
  find "${PUBLIC_ROOT}/beyond-tv/assets/media" -type f -name '*.mp4' -exec chmod 0644 {} + 2>/dev/null || true
fi

DEPLOY_STATE_DIR="${PRIVATE_ROOT}/deployments"
mkdir -p "${DEPLOY_STATE_DIR}"
chmod 700 "${DEPLOY_STATE_DIR}" 2>/dev/null || true

# Construct detailed deployment stdout report for StartCP pop-up window
echo "============================================================"
echo "✦ BEYOND OS STARTCP DEPLOYMENT REPORT ✦"
echo "============================================================"
echo "Timestamp:     ${DEPLOYED_AT}"
echo "Public Root:   ${PUBLIC_ROOT}"
echo "Branch:        main"
echo "Previous HEAD: ${PREV_COMMIT:0:12}"
echo "Deployed HEAD: ${NEW_COMMIT:0:12}"
echo "Commit Author: $(git log -1 --pretty=format:'%an <%ae>')"
echo "Commit Date:   $(git log -1 --pretty=format:'%cd')"
echo "Commit Msg:    $(git log -1 --pretty=format:'%s')"
echo "============================================================"
echo "Detailed list of files deployed / updated:"
echo "------------------------------------------------------------"

if [[ "${PREV_COMMIT}" != "${NEW_COMMIT}" && "${PREV_COMMIT}" != "000000000000" ]]; then
  git diff --name-status "${PREV_COMMIT}" "${NEW_COMMIT}" || true
  echo "------------------------------------------------------------"
  echo "Summary statistics:"
  git diff --stat "${PREV_COMMIT}" "${NEW_COMMIT}" || true
else
  echo "All files up to date with origin/main."
  echo "------------------------------------------------------------"
  git log -1 --stat || true
fi

echo "============================================================"
echo "✅ DEPLOYMENT COMPLETED SUCCESSFULLY"
echo "============================================================"

STATUS_TMP="$(mktemp "${DEPLOY_STATE_DIR}/.status.XXXXXX")"
printf '{\n  "result": "success",\n  "message": "Deployment completed successfully.",\n  "branch": "main",\n  "commit": "%s",\n  "requested_at": "",\n  "started_at": "%s",\n  "finished_at": "%s",\n  "stdout": "%s"\n}\n' "${NEW_COMMIT}" "${DEPLOYED_AT}" "${DEPLOYED_AT}" "Deployed commit ${NEW_COMMIT:0:12}" > "${STATUS_TMP}"
chmod 600 "${STATUS_TMP}" 2>/dev/null || true
mv -f "${STATUS_TMP}" "${DEPLOY_STATE_DIR}/status.json"
