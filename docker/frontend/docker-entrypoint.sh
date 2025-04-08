#!/bin/sh
set -e

# Apache gets grumpy about PID files pre-existing
rm -rf rm -rf /run/httpd/* /tmp/httpd*

exec httpd -DFOREGROUND "$@"
