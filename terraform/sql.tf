resource "google_sql_database" "db" {
  name     = "gib-potato"
  instance = google_sql_database_instance.db.name
}

resource "google_sql_database_instance" "db" {
  name             = "gib-potato-db"
  region           = "us-central1"
  database_version = "POSTGRES_14"
  settings {
    tier = "db-f1-micro"
  }

  deletion_protection = "true"
}
