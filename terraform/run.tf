resource "google_cloud_run_v2_service" "backend" {
  name     = "backend"
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
      image = "us-central1-docker.pkg.dev/hackweek-gib-potato/backend/backend:latest"

      volume_mounts {
        name       = "cloudsql"
        mount_path = "/cloudsql"
      }

      ports {
        container_port = 8080
      }

      env {
        name  = "APP_NAME"
        value = "gib_potato"
      }

      env {
        name  = "ENVIRONMENT"
        value = "production"
      }

      env {
        name  = "VERSION"
        value = "2023.1"
      }

      env {
        name  = "DEBUG"
        value = "false"
      }

      env {
        name  = "APP_ENCODING"
        value = "UTF-8"
      }

      env {
        name  = "APP_DEFAULT_LOCALE"
        value = "en_US"
      }

      env {
        name  = "APP_DEFAULT_TIMEZONE"
        value = "UTC"
      }

      env {
        name  = "MAIN_DOMAIN"
        value = ".gibpotato.app"
      }

      env {
        name  = "SLACK_REDIRECT_URI"
        value = "https://gibpotato.app/open-id"
      }

      env {
        name  = "LOG_DEBUG_URL"
        value = "file:///var/log/"
      }

      env {

        name  = "LOG_ERROR_URL"
        value = "file:///var/log/"
      }

      env {
        name = "SLACK_TEAM_ID"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_team_id.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "POTATO_CHANNEL"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.potato_channel.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "POTATO_SLACK_USER_ID"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.potato_slack_user_id.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SLACK_CLIENT_ID"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_client_secret.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SLACK_CLIENT_SECRET"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_client_secret.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SLACK_SIGNING_SECRET"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_signing_secret.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SLACK_BOT_USER_OAUTH_TOKEN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_bot_user_oauth_token.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "POTAL_TOKEN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.potal_token.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SECURITY_SALT"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.security_salt.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SENTRY_FRONTEND_DSN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.frontend_dsn.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SENTRY_BACKEND_DSN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.backend_dsn.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "DATABASE_USER"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.database_user.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "DATABASE_PASSWORD"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.database_password.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "DATABASE_NAME"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.database_name.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "DATABASE_SOCKET"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.database_socket.secret_id
            version = "latest"
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
    data.google_secret_manager_secret_version.security_salt,
    data.google_secret_manager_secret_version.slack_client_secret,
    data.google_secret_manager_secret_version.slack_signing_secret,
    data.google_secret_manager_secret_version.slack_bot_user_oauth_token,
    data.google_secret_manager_secret_version.potal_token,
    google_sql_database_instance.db,
  ]
}

resource "google_cloud_run_v2_service" "potal" {
  name     = "potal"
  location = "us-central1"
  ingress  = "INGRESS_TRAFFIC_ALL"

  template {
    containers {
      image = "us-central1-docker.pkg.dev/hackweek-gib-potato/potal/potal:latest"

      ports {
        container_port = 3000
      }

      env {
        name  = "ENVIRONMENT"
        value = "production"
      }

      env {
        name  = "RELEASE"
        value = "2023.2"
      }

      env {
        name  = "POTAL_URL"
        value = "https://gibpotato.app/events"
      }

      env {
        name = "SLACK_BOT_USER_OAUTH_TOKEN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_bot_user_oauth_token.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SENTRY_POTAL_DSN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.potal_dsn.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "SLACK_SIGNING_SECRET"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.slack_signing_secret.secret_id
            version = "latest"
          }
        }
      }

      env {
        name = "POTAL_TOKEN"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.potal_token.secret_id
            version = "latest"
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
    data.google_secret_manager_secret_version.potal_dsn,
  ]
}
