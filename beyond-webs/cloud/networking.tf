terraform {
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = ">= 6.0, < 8.0"
    }
  }
}

variable "network_self_link" {
  description = "VPC network used by the Beyond Webs seat VMs and the Cloud Run gateway."
  type        = string
}

variable "gateway_egress_subnet_cidr" {
  description = "Dedicated Direct VPC egress subnet CIDR attached to the gateway Cloud Run service."
  type        = string

  validation {
    condition     = can(cidrnetmask(var.gateway_egress_subnet_cidr))
    error_message = "Set a valid IPv4 CIDR for the gateway Direct VPC egress subnet."
  }
}

resource "google_compute_firewall" "beyond_webs_gateway_allow" {
  name          = "beyond-webs-gateway-allow"
  network       = var.network_self_link
  direction     = "INGRESS"
  priority      = 900
  source_ranges = [var.gateway_egress_subnet_cidr]
  target_tags   = ["beyond-webs-session"]

  allow {
    protocol = "tcp"
    ports    = ["6080"]
  }
}

resource "google_compute_firewall" "beyond_webs_gateway_deny" {
  name          = "beyond-webs-gateway-deny"
  network       = var.network_self_link
  direction     = "INGRESS"
  priority      = 1000
  source_ranges = ["0.0.0.0/0"]
  target_tags   = ["beyond-webs-session"]

  deny {
    protocol = "tcp"
    ports    = ["6080"]
  }
}
