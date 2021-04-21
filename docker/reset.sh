#!/bin/bash

# Enable extended globbing.
shopt -s extglob

# Stop and remove containers and volumes.
docker-compose down -v

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