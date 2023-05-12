resource "google_sql_database" "db" {
  name     = "gib-potato"
  instance = google_sql_database_instance.db.name
}

resource "google_sql_database_instance" "db" {
  name             = "gib-potato-db"
  region           = "us-central1"
  database_version = "POSTGRES_14"

  settings {
    tier = "db-custom-1-3840"
    availability_type = "REGIONAL"

    backup_configuration {
      enabled = true
      point_in_time_recovery_enabled = true

      backup_retention_settings {
        retained_backups = 7
      }
    }

    insights_config {
      query_insights_enabled = true
    }
  }

  deletion_protection = "true"
}

resource "google_sql_user" "user" {
  name       = "gib-potato"
  instance   = google_sql_database_instance.db.name
  password   = data.google_secret_manager_secret_version.database_password.secret_data
  depends_on = [google_secret_manager_secret.database_password]
}
