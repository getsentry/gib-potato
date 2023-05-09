resource "google_cloud_run_v2_service" "web" {
  name     = "gib-potato-web"
  location = "us-central1"
  ingress  = "INGRESS_TRAFFIC_ALL"

  template {
    scaling {
      max_instance_count = 3
    }

    volumes {
      name = "cloudsql"
      cloud_sql_instance {
        instances = [google_sql_database_instance.db.connection_name]
      }
    }

    containers {
      image = "ghcr.io/getsentry/gib-potato-potal:main"

      env {
        name = "APP_NAME"
      }

      env {
        name = "ENVIRONMENT"
      }

      env {
        name = "VERSION"
      }

      env {
        name = "DEBUG"
      }

      env {
        name = "APP_ENCODING"
      }

      env {
        name = "APP_DEFAULT_LOCALE"
      }

      env {
        name = "APP_DEFAULT_TIMEZONE"
      }

      env {
        name = "MAIN_DOMAIN"
      }

      env {
        name = "DATABASE_URL"
      }

      env {
        name = "SLACK_CLIENT_ID"
      }

      env {
        name = "SLACK_TEAM_ID"
      }

      env {
        name = "SLACK_REDIRECT_URI"
      }

      env {
        name = "SENTRY_DSN"
      }

      env {
        name = "SENTRY_MONITOR_ID"
      }

      env {
        name = "POTATO_CHANNEL"
      }

      env {
        name = "POTATO_SLACK_USER_ID"
      }

      env {
        name = "SECURITY_SALT"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.security_salt.secret_id
            version = "1"
          }
        }
      }

      env {
        name = "SLACK_CLIENT_SECRET"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_client_secret.secret_id
            version = "1"
          }
        }
      }

      env {
        name = "SLACK_SIGNING_SECRET"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_signing_secret.secret_id
            version = "1"
          }
        }
      }

      env {
        name = "SLACK_BOT_USER_OAUTH_TOKEN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_bot_user_oauth_token.secret_id
            version = "1"
          }
        }
      }

      env {
        name = "POTAL_TOKEN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.potal_token.secret_id
            version = "1"
          }
        }
      }

      volume_mounts {
        name       = "cloudsql"
        mount_path = "/cloudsql"
      }
    }
  }

  traffic {
    type    = "TRAFFIC_TARGET_ALLOCATION_TYPE_LATEST"
    percent = 100
  }

  depends_on = [
    data.google_secret_manager_secret_version.security_salt,
    data.google_secret_manager_secret_version.slack_client_secret,
    data.google_secret_manager_secret_version.slack_signing_secret,
    data.google_secret_manager_secret_version.slack_bot_user_oauth_token,
    data.google_secret_manager_secret_version.potal_token,
    google_sql_database_instance.db,

  ]
}

resource "google_cloud_run_v2_service" "potal" {
  name     = "gib-potato-potal"
  location = "us-central1"
  ingress  = "INGRESS_TRAFFIC_ALL"

  template {
    containers {
      image = "ghcr.io/getsentry/gib-potato-potal:main"

      env {
        name = "ENVIRONMENT"
      }

      env {
        name = "RELEASE"
      }

      env {
        name = "POTAL_URL"
      }

      env {
        name = "SLACK_SIGNING_SECRET"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_signing_secret.secret_id
            version = "1"
          }
        }
      }

      env {
        name = "POTAL_TOKEN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.potal_token.secret_id
            version = "1"
          }
        }
      }
    }
  }

  traffic {
    type    = "TRAFFIC_TARGET_ALLOCATION_TYPE_LATEST"
    percent = 100
  }

  depends_on = [
    data.google_secret_manager_secret_version.slack_signing_secret,
    data.google_secret_manager_secret_version.potal_token,
  ]
}
