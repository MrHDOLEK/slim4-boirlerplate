#!/usr/bin/env sh

set -e
supervisord --nodaemon --configuration /etc/supervisor/conf.d/supervisord.conf
