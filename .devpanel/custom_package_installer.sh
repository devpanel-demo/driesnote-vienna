#!/usr/bin/env bash
# ---------------------------------------------------------------------
# Copyright (C) 2024 DevPanel
# You can install any service here to support your project
# Please make sure you run apt update before install any packages
# Example:
# - sudo apt-get update
# - sudo apt-get install nano
#
# ----------------------------------------------------------------------

sudo apt-get update
sudo apt-get install -y jq nano npm

# Install VSCode Extensions
if [[ -n "$DP_VSCODE_EXTENSIONS" ]]; then
  sudo chown -R www:www $APP_ROOT/.vscode/extensions/
  IFS=','
  for value in $DP_VSCODE_EXTENSIONS; do
    code-server --install-extension $value --user-data-dir=$APP_ROOT/.vscode
  done
fi
