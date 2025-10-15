#!/bin/bash
# ---------------------------------------------------------------------
# Copyright (C) 2025 DevPanel
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation version 3 of the
# License.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# For GNU Affero General Public License see <https://www.gnu.org/licenses/>.
# ----------------------------------------------------------------------

## Description: Wrapper around the normal rebuild function with added stuff.
## Usage: demo-setup
## Example: "ddev demo-setup"

# Install
drush si drupal_cms_installer installer_site_template_form.add_ons=byte --account-name=devpanel --account-pass=devpanel --site-name="Driesnote Vienna 2025 Demo" -y
# Install byte
#ddev install-byte

# Composer stuff
composer require 'drupal/ai:1.2.x-dev@dev' \
'drupal/ai_agents:1.2.x-dev@dev' \
'drupal/ai_provider_openai:1.2.x-dev@dev' \
'drupal/ai_simple_pdf_to_text:^1.0@alpha' \
'drupal/ai_vdb_provider_milvus:1.1.x-dev@dev' \
'drupal/page_cache_exclusion:^1.0' \
'drupal/pexels_ai:^1.0@alpha' \
'jfcherng/php-diff:^6.0'

drush cr
# Install the AI stuff.
drush recipe ../custom_recipes/canvas_ai_setup
drush cr
# Add all images after to make sure they are indexed correctly.
drush recipe ../custom_recipes/media_images
# Add Aidan's manual pages
drush recipe ../custom_recipes/new_canvas_page
# Always flush the cache
drush cr

# Index the AI stuff
drush sapi-i

echo "Visit https://v2025demo.ddev.site/canvas and login with user 'devpanel' and password 'devpanel' to see the demo page."
echo "After login, you click New and New Page to create a new page from AI."
