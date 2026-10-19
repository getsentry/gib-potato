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

# Tag images by a hash of their inputs, so unchanged services can reuse the existing image
hash=$(find "${inputs[@]}" -type f -print0 | LC_ALL=C sort -z | xargs -0 sha256sum | sha256sum | cut -c1-16)
tag="src-${hash}"
echo "${image}:${tag}" > "${state}/${service}.image"

if [[ "${FORCE_BUILD:-false}" != "true" ]] && docker buildx imagetools inspect "${image}:${tag}" > /dev/null 2>&1; then
    echo "${image}:${tag} already exists, skipping build"
    exit 0
fi

args=(
    --builder cloudbuild
    --file "docker/${service}/Dockerfile"
    --cache-from "type=registry,ref=${image}:buildcache"
)
if [[ "$service" == "backend" ]]; then
    args+=(--secret id=SENTRY_AUTH_TOKEN,env=SENTRY_AUTH_TOKEN)
fi

docker buildx build "${args[@]}" \
    --cache-to "type=registry,ref=${image}:buildcache,mode=max,image-manifest=true,oci-mediatypes=true" \
    --tag "${image}:${tag}" \
    --tag "${image}:${COMMIT_SHA}" \
    --tag "${image}:latest" \
    --push \
    .
touch "${state}/${service}.built"

if [[ "$service" == "backend" ]]; then
    # Export the frontend assets for upload, all stages are cached from the build above
    docker buildx build "${args[@]}" \
        --target assets \
        --output "type=local,dest=${state}/assets" \
        .
fi
