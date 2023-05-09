resource "google_secret_manager_secret" "security_salt" {
  secret_id = "security_salt"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "security_salt" {
  secret = google_secret_manager_secret.security_salt.name
}

resource "google_secret_manager_secret_iam_member" "security_salt" {
  secret_id  = google_secret_manager_secret.security_salt.id
  role       = "roles/secretmanager.secretAccessor"
  member     = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
  depends_on = [google_secret_manager_secret.security_salt]
}

resource "google_secret_manager_secret" "slack_client_secret" {
  secret_id = "slack_client_secret"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "slack_client_secret" {
  secret = google_secret_manager_secret.slack_client_secret.name
}

resource "google_secret_manager_secret_iam_member" "slack_client_secret" {
  secret_id  = google_secret_manager_secret.slack_client_secret.id
  role       = "roles/secretmanager.secretAccessor"
  member     = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
  depends_on = [google_secret_manager_secret.slack_client_secret]
}

resource "google_secret_manager_secret" "slack_signing_secret" {
  secret_id = "slack_signing_secret"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "slack_signing_secret" {
  secret = google_secret_manager_secret.slack_signing_secret.name
}

resource "google_secret_manager_secret_iam_member" "slack_signing_secret" {
  secret_id  = google_secret_manager_secret.slack_signing_secret.id
  role       = "roles/secretmanager.secretAccessor"
  member     = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
  depends_on = [google_secret_manager_secret.slack_signing_secret]
}

resource "google_secret_manager_secret" "slack_bot_user_oauth_token" {
  secret_id = "slack_bot_user_oauth_token"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "slack_bot_user_oauth_token" {
  secret = google_secret_manager_secret.slack_bot_user_oauth_token.name
}

resource "google_secret_manager_secret_iam_member" "slack_bot_user_oauth_token" {
  secret_id  = google_secret_manager_secret.slack_bot_user_oauth_token.id
  role       = "roles/secretmanager.secretAccessor"
  member     = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
  depends_on = [google_secret_manager_secret.slack_bot_user_oauth_token]
}

resource "google_secret_manager_secret" "potal_token" {
  secret_id = "potal_token"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "potal_token" {
  secret = google_secret_manager_secret.potal_token.name
}

resource "google_secret_manager_secret_iam_member" "potal_token" {
  secret_id  = google_secret_manager_secret.potal_token.id
  role       = "roles/secretmanager.secretAccessor"
  member     = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
  depends_on = [google_secret_manager_secret.slack_signing_secret]
}
