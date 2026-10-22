#!/usr/bin/env bash
# Deploys the image of a service to Cloud Run, unless it is already running it.
# Usage: deploy.sh <service>
set -euo pipefail

service=$1
region=us-central1
state=/workspace/.cloudbuild
image=$(cat "${state}/${service}.image")

if [[ ! -f "${state}/${service}.built" ]]; then
    read -r current ready created < <(
        gcloud run services describe "$service" \
            --region "$region" \
            --format 'value(spec.template.spec.containers[0].image,status.latestReadyRevisionName,status.latestCreatedRevisionName)'
    )
    if [[ "$current" == "$image" && "$ready" == "$created" ]]; then
        echo "${service} is already running ${image}, skipping deploy"
        exit 0
    fi
fi

gcloud run deploy "$service" --image "$image" --region "$region"
