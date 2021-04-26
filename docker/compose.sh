#!/bin/bash

# The postgres container is built by us so that we can include
# the setup script in the image.
# The warehouse container is built with PHP extensions added
# and a user created with the same identity as the host user.
docker-compose build \
  --build-arg UID=$(id -u) \
  --build-arg GID=$(id -g) \
  --build-arg USER=$(id -un) \
  --build-arg GROUP=$(id -gn)
# When the containers are brought up the database will initialise
# on first run.
# The warehouse executes its setup script which copies in some 
# config files then waits for the database to be ready. Next it 
# starts Apache and will respond to http requests.
# This is performed in the background.
docker-compose up -d

# Wait for warehouse to be up
echo "Waiting for warehouse..."
until curl --silent --output outputfile http://localhost:8080; do
  sleep 1
done
echo "Warehouse is up."

# Find out where we get redirected to on requesting localhost:8080.
location=$(curl \
  --output outputfile \
  --location \
  --silent \
  --show-error \
  --write-out "%{url_effective}" \
  http://localhost:8080)

if [ $location = "http://localhost:8080/index.php/setup_check" ]; then
  # Database initialisation has not been performed yet.
  prompt="Do you want the indicia database schema initialised (Y/n)?"
  read -rs -n 1 -p "$prompt" 
  if [ "$REPLY" = "Y" ] || [ "$REPLY" = "y" ] || [ -z "$REPLY" ]; then
    echo
    echo "Initialising the indicia schema."
    curl --config warehouse/setup/01_database_init
    echo "Performing first log in."
    curl --config warehouse/setup/02_first_login
    echo "Setting the password for user 'admin' to 'password'."
    curl --config warehouse/setup/03_set_admin_password
    echo "Upgrading the indicia schema to the most recent version."
    curl --config warehouse/setup/04_database_upgrade   

    # Now the indicia schema exists we can set permissions for it.
    echo "Setting permissions on the indicia schema."
    export PGPASSWORD=password
    psql -q -o outputfile -h localhost -U postgres indicia <<____EOF
      GRANT USAGE ON SCHEMA indicia TO indicia_report_user;
      GRANT SELECT ON ALL TABLES IN SCHEMA indicia TO indicia_report_user;
      ALTER DEFAULT PRIVILEGES IN SCHEMA indicia 
        GRANT SELECT ON TABLES TO indicia_report_user;
      ALTER USER indicia_report_user SET search_path = indicia, public, pg_catalog;
      ALTER USER indicia_user SET search_path = indicia, public, pg_catalog;
____EOF

    # With the search_path set we can apply the optiimisation
    find="config\['apply_schema'\] = TRUE;"
    replace="config\['apply_schema'\] = false;"
    sed -i "s/$find/$replace/" ../application/config/indicia.php

    # With the database fully set up we can enable scheduled tasks.
    prompt="Do you want scheduled tasks to run (Y/n)?"
    read -rs -n 1 -p "$prompt"
    if [ "$REPLY" = "Y" ] || [ "$REPLY" = "y" ] || [ -z "$REPLY" ]; then
      echo
      echo "Adding scheduled tasks to crontab."
      cronspec="*/15 * * * * php /var/www/html/index.php scheduled_tasks"
      croncmd="echo $cronspec | crontab -u $(id -un) -"
      docker exec docker_warehouse_1 sh -c "set -f; $croncmd"

      # With scheduled_tasks enabled we can enable the data_cleaner.
      prompt="Do you want data_cleaner tasks to run (Y/n)?"
      read -rs -n 1 -p "$prompt"
      if [ "$REPLY" = "Y" ] || [ "$REPLY" = "y" ] || [ -z "$REPLY" ]; then
        echo
        echo "Adding data cleaner tasks to application/config/config.php."
        module_array=(
          'data_cleaner_ancillary_species'
          'data_cleaner_designated_taxa'
          'data_cleaner_identification_difficulty'
          'data_cleaner_location_lookup_attr_list'
          'data_cleaner_new_species_for_site'
          'data_cleaner_occurrence_lookup_attr_outside_range'
          'data_cleaner_period'
          'data_cleaner_period_within_year'
          'data_cleaner_sample_attribute_changes_for_site'
          'data_cleaner_sample_lookup_attr_outside_range'
          'data_cleaner_sample_number_attr_outside_range'
          'data_cleaner_sample_time_attr_outside_range'
          'data_cleaner_species_location'
          'data_cleaner_species_location_name'
          'data_cleaner_without_polygon'
        )
        # This just has the effect of uncommenting all the modules.
        for module in ${module_array[@]}; do
          replace="         MODPATH.'$module'"
          find="\/\/$replace"
          sed -i "s/$find/$replace/" ../application/config/config.php
          echo Added $module
        done
      fi # End of enable data_cleaner.
    fi # End of enable scheduled_tasks.
  fi # End of initialise indicia schema.
fi

# Clean up.
rm -f cookiefile
rm -f outputfile

echo
echo "You can visit the warehouse at http://localhost:8080"
echo "You can see email it sends at http://localhost:8025"
echo "You can examine the database at http://localhost:8070"
