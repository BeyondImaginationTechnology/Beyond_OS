#!/bin/sh
set -eu
if ! command -v sshd >/dev/null 2>&1; then apt-get update; DEBIAN_FRONTEND=noninteractive apt-get install -y openssh-server; fi
systemctl enable --now ssh
