#!/bin/bash

# Enable extended globbing for easy removal of cache and log files.
shopt -s extglob

# Set the project name which determines the network and container names.
export COMPOSE_PROJECT_NAME=indicia

prompt="This will destroy your warehouse and erase all data. Proceed (y/N)?"
read -rs -n 1 -p "$prompt" 
echo
if [ "$REPLY" = "Y" ] || [ "$REPLY" = "y" ]; then

    # Stop and remove containers and volumes.
    docker compose down -v

    # Remove config files
    rm -f ../application/config/config.php
    rm -f ../application/config/database.php
    rm -f ../application/config/email.php
    rm -f ../application/config/indicia.php
    rm -f ../client_helpers/helper_config.php

    # Remove cache files
    rm -f ../application/cache/!(.gitignore)

    # Remove log files
    rm -f ../application/logs/!(.gitignore)

else
    echo Reset abandonned.
fi