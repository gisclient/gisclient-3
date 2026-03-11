# GisClient-3

GisClient3 is an open source software written in AJAX, Javascript, PHP/MapScript that offers an innovative way to manage complex GIS projects and a platform for publishing spatial data.
This version apply a MapServer patch to take advantage of the PostgreSQL Row Level Security

[![CI](https://github.com/gisclient/gisclient-3/actions/workflows/ci.yml/badge.svg)](https://github.com/gisclient/gisclient-3/actions/workflows/ci.yml)

## Clone the repo

**NOTE**: git lfs must be installed. To check if it is installed run `git lfs install`. If you don't see a message indicating that git lfs install was successful, try to install it: `sudo apt install git-lfs`

## Run with docker compose

Use docker compose to run the GisClient backend, frontend and a PostgreSQL database:

```
# if you have permission problems, you need the following environment variables (add them in ~/.bashrc for example):
# export USER_ID=$(id -u)
# export GROUP_ID=$(id -g)
# these variables change the id of the php-fpm user
docker compose up
```

## Run with Makefile

Use the provided `Makefile` shortcuts for common development tasks:

```bash
make start       # start all services in background and build images
make up          # start all services in foreground and build images
make down        # stop all services
make clean       # stop services and remove volumes (full reset)
make deps        # install backend composer dependencies in container
make db-upgrade  # run database upgrade script (doc/update_db_from_3.4.0.sql)
make test        # run backend test suite
make test-ci     # run backend test suite in CI-friendly mode
make phpstan     # run static analysis
make phpstan-ci  # run static analysis in CI-friendly mode
make ecs         # run coding standard checks
make ecs-ci      # run coding standard checks in CI-friendly mode
make rector      # run rector in dry-run mode
make rector-ci   # run rector in CI-friendly mode
make quality     # run all checks (rector, ecs, phpstan)
make quality-ci  # run all CI checks (rector-ci, ecs-ci, phpstan-ci)
make ecs-fix     # apply coding standard fixes
make rector-fix  # apply rector refactors
make quality-fix # run all auto-fixes (rector-fix, ecs-fix)
make cache-clear # clear container cache and restart backend service
```

On first setup (or after resetting volumes), run `make deps` before `make test`.

Open a browser and go to [http://127.0.0.1:8080/author/](http://127.0.0.1:8080/author/)

To generate a working map enter inside the UI on [http://127.0.0.1:8080/author/](http://127.0.0.1:8080/author/), press on the "Author" button, go to the default project, and regenerate the map.
You can open a gis client like qGIS, and add a WMS or WFS with this URL: [http://127.0.0.1:8080/author/services/ows.php?project=default&map=default&](http://127.0.0.1:8080/author/services/ows.php?project=default&map=default&request=getcapabilities&service=WMS&version=1.1.1)

Defaults:

- Login and password: `admin`. _Don't forget to change the password 😱_
- Web port: 8080
- PostgreSQL port: 5432

You can create a .env file to customize parameters

GitHub Actions uses the same Make-based workflow in CI by copying `.env.dist` to `.env`, starting the Docker stack, and running `make test-ci` / `make quality-ci`.

## Local installation / Usage

You can install GisClient3 via [composer](https://getcomposer.org/).

```

composer create-project gisclient/author <directory> dev-<branch-name>

```

## Acknowledgments

This branch is actively maintained.
