resource "google_service_account" "backend" {
  account_id = "service-backend"
}

resource "google_service_account" "potal" {
  account_id = "service-potal"
}

resource "google_cloud_run_service_iam_binding" "potal" {
  location = google_cloud_run_v2_service.potal.location
  service  = google_cloud_run_v2_service.potal.name
  role     = "roles/run.invoker"
  members = [
    "allUsers"
  ]
}

resource "google_cloud_run_service_iam_binding" "backend" {
  location = google_cloud_run_v2_service.backend.location
  service  = google_cloud_run_v2_service.backend.name
  role     = "roles/run.invoker"
  members = [
    "allUsers"
  ]
}

resource "google_project_iam_binding" "backend_sql" {
  project = data.google_project.project.name
  role    = "roles/cloudsql.client"
  members = [
    "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com",
  ]
  depends_on = [google_service_account.backend]
}

resource "google_project_iam_member" "cloud_build_cloud_run_admin" {
  project = data.google_project.project.name
  role    = "roles/run.admin"
  member  = "serviceAccount:${data.google_project.project.number}@cloudbuild.gserviceaccount.com"
}
