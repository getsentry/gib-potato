resource "google_secret_manager_secret" "security_salt" {
  secret_id = "security_salt"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "security_salt" {
  secret = google_secret_manager_secret.slack_client_secret.name
}

resource "google_secret_manager_secret_iam_member" "security_salt" {
  secret_id = google_secret_manager_secret.security_salt.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
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
  secret_id = google_secret_manager_secret.slack_client_secret.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "slack_client_id" {
  secret_id = "slack_client_id"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "slack_client_id" {
  secret = google_secret_manager_secret.slack_client_secret.name
}

resource "google_secret_manager_secret_iam_member" "slack_client_id" {
  secret_id = google_secret_manager_secret.slack_client_id.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
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
  secret_id = google_secret_manager_secret.slack_signing_secret.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
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
  secret_id = google_secret_manager_secret.slack_bot_user_oauth_token.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
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
  secret_id = google_secret_manager_secret.potal_token.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "database_user" {
  secret_id = "database_user"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "database_user" {
  secret = google_secret_manager_secret.database_user.name
}

resource "google_secret_manager_secret_iam_member" "database_user" {
  secret_id = google_secret_manager_secret.database_user.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "database_password" {
  secret_id = "database_password"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "database_password" {
  secret = google_secret_manager_secret.database_password.name
}

resource "google_secret_manager_secret_iam_member" "database_password" {
  secret_id = google_secret_manager_secret.database_password.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "database_name" {
  secret_id = "database_name"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "database_mame" {
  secret = google_secret_manager_secret.database_name.name
}

resource "google_secret_manager_secret_iam_member" "database_name" {
  secret_id = google_secret_manager_secret.database_name.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "database_socket" {
  secret_id = "database_socket"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "database_socket" {
  secret = google_secret_manager_secret.database_socket.name
}

resource "google_secret_manager_secret_iam_member" "database_socket" {
  secret_id = google_secret_manager_secret.database_socket.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "slack_team_id" {
  secret_id = "slack_team_id"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "slack_team_id" {
  secret = google_secret_manager_secret.slack_team_id.name
}

resource "google_secret_manager_secret_iam_member" "slack_team_id" {
  secret_id = google_secret_manager_secret.slack_team_id.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "potato_channel" {
  secret_id = "potato_channel"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "potato_channel" {
  secret = google_secret_manager_secret.potato_channel.name
}

resource "google_secret_manager_secret_iam_member" "potato_channel" {
  secret_id = google_secret_manager_secret.potato_channel.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "potato_slack_user_id" {
  secret_id = "potato_slack_user_id"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "potato_slack_user_id" {
  secret = google_secret_manager_secret.potato_slack_user_id.name
}

resource "google_secret_manager_secret_iam_member" "potato_slack_user_id" {
  secret_id = google_secret_manager_secret.potato_slack_user_id.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "backend_dsn" {
  secret_id = "backend_dsn"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "backend_dsn" {
  secret = google_secret_manager_secret.backend_dsn.name
}

resource "google_secret_manager_secret_iam_member" "backend_dsn" {
  secret_id = google_secret_manager_secret.backend_dsn.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "frontend_dsn" {
  secret_id = "frontend_dsn"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "frontend_dsn" {
  secret = google_secret_manager_secret.frontend_dsn.name
}

resource "google_secret_manager_secret_iam_member" "frontend_dsn" {
  secret_id = google_secret_manager_secret.frontend_dsn.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}

resource "google_secret_manager_secret" "potal_dsn" {
  secret_id = "potal_dsn"
  replication {
    automatic = true
  }
}

data "google_secret_manager_secret_version" "potal_dsn" {
  secret = google_secret_manager_secret.potal_dsn.name
}

resource "google_secret_manager_secret_iam_member" "potal_dsn" {
  secret_id = google_secret_manager_secret.potal_dsn.id
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}
