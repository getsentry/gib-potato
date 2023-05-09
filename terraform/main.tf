terraform {
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "4.51.0"
    }
  }
  backend "gcs" {
    bucket = "gib-potato-terraform-state"
  }
}

provider "google" {
  project = "hackweek-gib-potato"
  region  = "us-central1"
  zone    = "us-central1-c"
}

resource "google_storage_bucket" "terraform-state" {
  name          = "gib-potato-terraform-state"
  force_destroy = false
  location      = "US"
  storage_class = "STANDARD"
  versioning {
    enabled = true
  }
}

data "google_project" "project" {
}
