#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
export GIT_TERMINAL_PROMPT=0

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPOSITORY_ROOT="$(cd -- "${SCRIPT_DIR}/.." && pwd)"
ACCOUNT_ROOT="$(cd -- "${REPOSITORY_ROOT}/.." && pwd)"
PUBLIC_ROOT="${BEYOND_PUBLIC_ROOT:-${ACCOUNT_ROOT}/public_html}"
PRIVATE_ROOT="${BEYOND_VAR_PATH:-${ACCOUNT_ROOT}/var}"

[[ -d "${REPOSITORY_ROOT}/.git" ]] || { echo "Repository metadata is unavailable." >&2; exit 1; }
[[ -d "${PUBLIC_ROOT}" ]] || { echo "Public web root does not exist: ${PUBLIC_ROOT}" >&2; exit 1; }
command -v git >/dev/null 2>&1 || { echo "git is unavailable." >&2; exit 1; }
command -v rsync >/dev/null 2>&1 || { echo "rsync is unavailable." >&2; exit 1; }

cd "${REPOSITORY_ROOT}"
CURRENT_BRANCH="$(git symbolic-ref --quiet --short HEAD || true)"
[[ "${CURRENT_BRANCH}" == "main" ]] || { echo "Refusing to deploy branch '${CURRENT_BRANCH:-detached}'. Expected main." >&2; exit 1; }
[[ -z "$(git status --porcelain)" ]] || { echo "Refusing to deploy a repository with local changes or untracked files." >&2; exit 1; }

git fetch --prune origin main
git merge --ff-only origin/main

rsync -a --delay-updates \
  --exclude='/.git/' --exclude='/.github/' --exclude='/.cache/' \
  --exclude='/var/' --exclude='/config/live.php' \
  --exclude='/docs/' --exclude='/tools/' --exclude='/sql/' --exclude='/exports/' \
  --exclude='/AppStoreAssets/' --exclude='/*Apple/' --exclude='/*Android/' \
  --exclude='/.gitattributes' --exclude='/.gitignore' \
  --exclude='/README.md' --exclude='/CONTRIBUTING.md' --exclude='/SECURITY.md' --exclude='/LICENSE' \
  --exclude='/*.csr' --exclude='/azure-pipelines*.yml' \
  "${REPOSITORY_ROOT}/" "${PUBLIC_ROOT}/"

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
