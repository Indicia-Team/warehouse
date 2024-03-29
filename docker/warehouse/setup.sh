#!/bin/sh

# Find out where we get redirected to on requesting localhost:8080.
location=$(curl \
  --output outputfile \
  --location \
  --silent \
  --show-error \
  --write-out "%{url_effective}" \
  http://localhost:8080)

if [ $location = "http://localhost:8080/index.php/setup_check" ]; then
  echo
  echo "Starting Warehouse setup"
  echo
  # Database initialisation has not been performed yet.
  prompt="Do you want the indicia database schema initialised (Y/n)?"
  read -rs -n 1 -p "$prompt" 
  if [ "$REPLY" = "Y" ] || [ "$REPLY" = "y" ] || [ -z "$REPLY" ]; then
    echo
    echo "Initialising the indicia schema."
    curl --config warehouse/setup/01_database_init
    echo "...Performing first log in."
    curl --config warehouse/setup/02_first_login
    echo "...Setting the password for user 'admin' to 'password'."
    curl --config warehouse/setup/03_set_admin_password
    echo "...Upgrading the indicia schema to the most recent version."
    curl --config warehouse/setup/database_upgrade

    # Now the indicia schema exists we can set permissions for it.
    echo "...Setting permissions on the indicia schema."
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

    # We can import the UK Species Inventory to a species list
    echo
    prompt="Do you want to import the UK Species Inventory (Could take 15 mins) (Y/n)?"
    read -rs -n 1 -p "$prompt"
    if [ "$REPLY" = "Y" ] || [ "$REPLY" = "y" ] || [ -z "$REPLY" ]; then
      echo
      echo "Adding UK Species Inventory."

      echo "...Creating an empty list."
      curl --config warehouse/setup/04_new_uksi_species_list  

      # Enable required modules.
      module_array=(
        'taxon_designations'
        'taxon_associations'
        'species_alerts'
        'data_cleaner_period_within_year'
        'data_cleaner_without_polygon'
      )
      # This just has the effect of uncommenting all the modules.
      for module in ${module_array[@]}; do
        replace="         MODPATH.'$module'"
        find="\/\/$replace"
        sed -i "s/$find/$replace/" ../application/config/config.php
        echo "...Enabled $module"
      done

      echo "...Upgrading the database after adding modules."
      curl --config warehouse/setup/database_upgrade   

      echo "...Copy the UKSI files from Github."
      url=https://codeload.github.com/Indicia-Team/support_files/tar.gz/master
      curl $url | tar -xz --strip-components=1 support_files-master/UKSI

      echo "...Executing the import on the warehouse."
      docker exec indicia-warehouse-1 sh -c '
        cd docker/UKSI
        php import-uksi.php \
        --warehouse-path=/var/www/html \
        --data-path=/var/docker/UKSI \
        --su=postgres \
        --supass=password \
        --taxon_list_id=1 \
        --user_id=1
      '

      echo "...Setting the master list id in application/config/indicia.php"
      find="config\['master_list_id'\] = 0;"
      replace="config\['master_list_id'\] = 1;"
      sed -i "s/$find/$replace/" ../application/config/indicia.php

      # Clean up.
      rm -rf UKSI

    fi # End of add UKSI species list.
    
    # We can import the GBIF Backbone to a species list
    echo
    prompt="Do you want to import the GBIF Backbone Taxonomy (Could take 4 hours) (Y/n)?"
    read -rs -n 1 -p "$prompt"
    if [ "$REPLY" = "Y" ] || [ "$REPLY" = "y" ] || [ -z "$REPLY" ]; then
      echo
      echo "Adding GBIF Backbone Taxonomy."

      echo "...Creating an empty list."
      curl --config warehouse/setup/05_new_gbif_species_list  

      #### COPYING FROM UKSI - MAY NOT BE NEEDED.
      # Enable required modules.
      module_array=(
        'taxon_designations'
        'taxon_associations'
        'species_alerts'
        'data_cleaner_period_within_year'
        'data_cleaner_without_polygon'
      )
      # This just has the effect of uncommenting all the modules.
      for module in ${module_array[@]}; do
        replace="         MODPATH.'$module'"
        find="\/\/$replace"
        sed -i "s/$find/$replace/" ../application/config/config.php
        echo "...Enabled $module"
      done

      echo "...Upgrading the database after adding modules."
      curl --config warehouse/setup/database_upgrade   

      echo "...Copying the GBIF scripts from Github."
      url=https://codeload.github.com/Indicia-Team/support_files/tar.gz/master
      curl $url | tar -xz --strip-components=1 support_files-master/GBIF

      echo "...Downloading the GBIF Backbone Taxonomy."
      cd GBIF
      url=https://hosted-datasets.gbif.org/datasets/backbone/current/simple.txt.gz
      curl -O $url
      echo "...Decompressing the GBIF file."
      gunzip -k simple.txt.gz
      # Rename to match expectations of the import script.
      mv simple.txt backbone-current-simple.txt

      echo "...Executing the import on the warehouse."
      # Note, warehouse-path is in the warehouse container
      # but data-path is in the postgres container.
      # There are corresponding mounts in the compose file.
      docker exec indicia-warehouse-1 sh -c '
        cd docker/GBIF
        php import-gbif.php \
        --warehouse-path=/var/www/html \
        --data-path=/var/docker/GBIF \
        --su=postgres \
        --supass=password \
        --taxon_list_id=1 \
        --user_id=1'

      # # Clean up.
      cd -
      rm -rf GBIF

    fi # End of add GBIF Backbone species list.

    # With the database fully set up we can enable scheduled tasks.
    echo
    prompt="Do you want scheduled tasks to run (Y/n)?"
    read -rs -n 1 -p "$prompt"
    if [ "$REPLY" = "Y" ] || [ "$REPLY" = "y" ] || [ -z "$REPLY" ]; then
      echo
      echo "Adding scheduled tasks to crontab."
      croncmd="crontab -u $(id -un) docker/warehouse/config/crontab.txt"
      docker exec indicia-warehouse-1 $croncmd

      # With scheduled_tasks enabled we can enable the data_cleaner.
      echo
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
          'taxon_designations'
        )
        # This just has the effect of uncommenting all the modules.
        for module in ${module_array[@]}; do
          replace="         MODPATH.'$module'"
          find="\/\/$replace"
          sed -i "s/$find/$replace/" ../application/config/config.php
          echo "...Enabled $module"
        done

        echo "...Upgrading the database after adding modules."
        curl --config warehouse/setup/database_upgrade   

      fi # End of enable data_cleaner.
    fi # End of enable scheduled_tasks.
  fi # End of initialise indicia schema.

  # Increase file upload size.
  sed -i "s/\$config\['maxUploadSize'\] = '1M';/\$config['maxUploadSize'] = '32M';/" "../application/config/indicia.php"
  echo
  echo "Warehouse setup complete."
else
  echo "Warehouse setup already complete."
fi # End of setup.
echo
