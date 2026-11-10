#!/usr/bin/env bash
# Builds and pushes the image of a service, unless an image for the same inputs already exists.
# Usage: build.sh <service>
set -euo pipefail

service=$1
image="us-central1-docker.pkg.dev/${PROJECT_ID}/${service}/${service}"
state=/workspace/.cloudbuild
mkdir -p "$state"

case "$service" in
    potal)
        inputs=(potal docker/potal)
        ;;
    shopato)
        inputs=(shopato docker/shopato)
        ;;
    backend)
        # Keep in sync with the COPY instructions in docker/backend/Dockerfile
        inputs=(
            bin config resources src templates webroot frontend index.php
            composer.json composer.lock package.json package-lock.json vite.config.js
            docker/backend
        )
        ;;
    *)
        echo "Unknown service: $service" >&2
        exit 1
        ;;
esac

# The build cache and image tags rotate every ISO week, so the first build of a week starts
# from scratch and picks up new base images and system packages without a manual rebuild
epoch=$(date -u +%G-W%V)
cache="${image}:buildcache-${epoch}"

# Tag images by a hash of their inputs, so unchanged services can reuse the existing image
hash=$(
    {
        echo "$epoch"
        find "${inputs[@]}" -type f -print0 | LC_ALL=C sort -z | xargs -0 sha256sum
    } | sha256sum | cut -c1-16
)
tag="src-${hash}"
echo "${image}:${tag}" > "${state}/${service}.image"

args=(
    --builder cloudbuild
    --file "docker/${service}/Dockerfile"
    --cache-from "type=registry,ref=${cache}"
)
if [[ "$service" == "backend" ]]; then
    args+=(--secret id=SENTRY_AUTH_TOKEN,env=SENTRY_AUTH_TOKEN)
fi

if [[ "${FORCE_BUILD:-false}" != "true" ]] && docker buildx imagetools inspect "${image}:${tag}" > /dev/null 2>&1; then
    echo "${image}:${tag} already exists, skipping build"
else
    force_args=()
    if [[ "${FORCE_BUILD:-false}" == "true" ]]; then
        force_args=(--no-cache --pull)
    fi

    docker buildx build "${args[@]}" "${force_args[@]}" \
        --cache-to "type=registry,ref=${cache},mode=max,image-manifest=true,oci-mediatypes=true" \
        --tag "${image}:${tag}" \
        --tag "${image}:${COMMIT_SHA}" \
        --tag "${image}:latest" \
        --push \
        .
    touch "${state}/${service}.built"
fi

if [[ "$service" == "backend" ]]; then
    # Export the frontend assets for upload, also when the build was skipped, so a failed
    # export or upload is retried on the next run. Stages come from the cache.
    docker buildx build "${args[@]}" \
        --target assets \
        --output "type=local,dest=${state}/assets" \
        .
fi
