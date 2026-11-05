#!/usr/bin/env bash
# Uploads the frontend assets exported by build.sh, if the backend was built.
set -euo pipefail

assets=/workspace/.cloudbuild/assets

if [[ ! -d "$assets" ]]; then
    echo "Backend was not built, skipping asset upload"
    exit 0
fi

gcloud storage cp "${assets}"/assets/* "${assets}/manifest.json" gs://gib-potato-assets/
