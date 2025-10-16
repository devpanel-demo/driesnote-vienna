#!/usr/bin/env bash

cd $APP_ROOT

echo Remove root-owned files.
sudo rm -rf lost+found

STATIC_FILES_PATH="$WEB_ROOT/sites/default/files"
SETTINGS_FILES_PATH="$WEB_ROOT/sites/default/settings.php"

if [[ ! -n "$APACHE_RUN_USER" ]]; then
  export APACHE_RUN_USER=www-data
fi
if [[ ! -n "$APACHE_RUN_GROUP" ]]; then
  export APACHE_RUN_GROUP=www-data
fi

#== Composer install.
if [[ -f "$APP_ROOT/composer.json" ]]; then
  # cd $APP_ROOT && composer install;
  cd $APP_ROOT
  if [[ ! -f composer.lock ]]; then
    composer update --no-interaction --no-progress
  else
    composer install --no-interaction --no-progress
  fi
fi
if [[ -f "$WEB_ROOT/composer.json" ]]; then
  cd $WEB_ROOT && composer install;
fi
#== Install drush locally
# echo "Install drush locally ..."
# composer require --dev drush/drush

#== Generate hash salt
echo 'Generate hash salt ...'
DRUPAL_HASH_SALT=$(openssl rand -hex 32);
echo $DRUPAL_HASH_SALT > $APP_ROOT/.devpanel/salt.txt

cd $WEB_ROOT && git submodule update --init --recursive

#== Install Drupal.
# #Securing file permissions and ownership
# #https://www.drupal.org/docs/security-in-drupal/securing-file-permissions-and-ownership
[[ ! -d $STATIC_FILES_PATH ]] && sudo mkdir --mode 775 $STATIC_FILES_PATH || sudo chmod 775 -R $STATIC_FILES_PATH

#== Drush Site Install
if [[ $(mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASSWORD $DB_NAME --skip-ssl -e "show tables;") == '' ]]; then
  echo "> Drush status"
  drush status
  
  echo "Site installing ..."
  cd $APP_ROOT
  # sudo chown -R $APACHE_RUN_USER:$APACHE_RUN_GROUP $STATIC_FILES_PATH
  # Install
  # drush si drupal_cms_installer installer_site_template_form.add_ons=byte --account-name=devpanel --account-pass=devpanel --site-name="Driesnote Vienna 2025 Demo" --db-url=mysql://$DB_USER:$DB_PASSWORD@$DB_HOST:$DB_PORT/$DB_NAME -y
  drush si standard --account-name=devpanel --account-pass=devpanel --site-name="Driesnote Vienna 2025 Demo" --db-url=mysql://$DB_USER:$DB_PASSWORD@$DB_HOST:$DB_PORT/$DB_NAME -y
  # Install byte
  #ddev install-byte

  # Composer stuff
  # composer require 'drupal/ai:1.2.x-dev@dev' \
  # 'drupal/ai_agents:1.2.x-dev@dev' \
  # 'drupal/ai_provider_openai:1.2.x-dev@dev' \
  # 'drupal/ai_simple_pdf_to_text:^1.0@alpha' \
  # 'drupal/ai_vdb_provider_milvus:1.1.x-dev@dev' \
  # 'drupal/page_cache_exclusion:^1.0' \
  # 'drupal/pexels_ai:^1.0@alpha' \
  # 'jfcherng/php-diff:^6.0'

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
fi
