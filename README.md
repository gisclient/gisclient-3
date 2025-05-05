# GisClient-3

GisClient3 is an open source software written in AJAX, Javascript, PHP/MapScript that offers an innovative way to manage complex GIS projects and a platform for publishing spatial data.
This version apply a MapServer patch to take advantage of the PostgreSQL Row Level Security

[![Build Status](https://travis-ci.org/gisclient/gisclient-3.svg?branch=master)](https://travis-ci.org/gisclient/gisclient-3)

## Clone the repo

**NOTE**: git lfs must bi installed. To check if it is installed run `git lfs install`. If you don't see a message indicating that git lfs install was successful, try to install it:

On MacOS using brew: `brew install git-lfs`

On Ubuntu: `sudo apt-get install git-lfs`

To download the lfs files run inside the repository `git lfs pull`

## Run with docker compose

Use docker compose to run the GisClient backend, frontend and a PostgreSQL database:

```
docked compose up
```

Open a browser and go to [http://127.0.0.1/author/](http://127.0.0.1/author/)

To generate a working map enter inside the UI on [http://127.0.0.1/author/](http://127.0.0.1/author/), press on the "Author" button, go to the default project, and regenerate the map.
You can open a gis client like qGIS, and add a WMS or WFS with this URL: [http://127.0.0.1/author/services/ows.php?project=default&map=default&](http://127.0.0.1/author/services/ows.php?project=default&map=default&request=getcapabilities&service=WMS&version=1.1.1)

Defaults:

- Login and password: `admin`. _Don't forget to change the password 😱_
- Web port: 80
- PostgreSQL port: 5432

You can create a .env file to customize parameters

## Local installation / Usage

You can install GisClient3 via [composer](https://getcomposer.org/).

```

composer create-project gisclient/author <directory> dev-<branch-name>

```

## Acknowledgments

This branch is actively maintained.
