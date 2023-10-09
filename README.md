# Indicia Warehouse [![Build Status](https://travis-ci.com/Indicia-Team/warehouse.svg?branch=master)](https://travis-ci.com/Indicia-Team/warehouse)

This is the repository for the Indicia Warehouse, the server-side component of Indicia, the online wildlife recording
toolkit. Indicia accelerates development of wildlife recording websites and mobile applications. Documentation is
available at https://indicia-docs.readthedocs.io/en/latest/index.html.

Details of the installation procedure are at
http://indicia-docs.readthedocs.org/en/latest/administrating/warehouse/warehouse-installation.html.

The latest stable download of the warehouse code is available at https://github.com/Indicia-Team/warehouse/releases/latest

Details of the upgrade procedure are at
http://indicia-docs.readthedocs.org/en/latest/administrating/warehouse/warehouse-upgrading.html.

## Docker development
For those working in Linux, there is a set up script which will install
and configure a 'complete' (work in progress) Indicia infrastructure using
Docker. The code is in a shared volume so that you can see the effect of
changes immediately and xdebug is set up so you can set breakpoints and
single step through code.

### Prerequisites
Naturally you'll need [Docker](https://docs.docker.com/engine/install/) installed.

Currently, a postgres client is needed on the host for the script to complete the set
up of the indicia schema. E.g. on Ubuntu you can
`sudo apt install postgresql-client`.

### Starting
If you clone this repo, `cd docker` and execute `./compose.sh` it will start
5 docker containers offering these services.
1. A postgres database with postgis installed.
1. pgAdmin for administering the database.
1. A mock mail server.
1. A webserver running the warehouse code.
1. GeoServer for sharing spatial data in OGC standard format.
On first run, it offers to initialise the indicia database schema.
If you choose this option you will later login in as user `admin` having
password `password`.

To reset your docker system to an entirely clean state, execute `./reset.sh`
from the `docker` folder.
If you are going to immediately restart docker then delete cookies from
your browser too.

### Using
Once running you can browse the warehouse at http://localhost:8080.
You can examine the database with pgAdmin at http://localhost:8070.
Any mail sent by the warehouse can be viewed at http://localhost:8025.
GeoServer can be configured at http://localhost:8090/geoserver.

#### PgAdmin
To connect pgAdmin to the database, configure the connection with
 - Host name: The docker container name e.g. indicia_postgres_1
 - Port: 5432
 - Username: postgres
 - Password: password
To list the container names and ports you can execute the command
`docker container ls --format "table {{.Names}}\t{{.Ports}}"`

#### GeoServer
To log in, the default credentials are:
 - Username: admin
 - Password: geoserver
 
### Unit testing
There is a separate Docker configuration for unit testing which can be
run up by `cd docker` then `./phpunit.sh`. This replicates the unit
testing performed when you push commits to the repository, enabling you
to create and debug tests locally. It uses its own volume for the database
so won't overwrite any setup you have in your development system. You do have to
stop your development containers before running unit testing though, as it uses
the same ports and modifies configuration. Config files that are modified by 
testing are backed up and restored, provided the script runs to completion.
