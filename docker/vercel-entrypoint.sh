#!/bin/sh
set -eu

# Vercel supplies PORT at runtime. Apache defaults to port 80, so update both
# its listener and virtual-host declaration before starting the web server.
port="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

# Creates only migrations that have not yet run. On TiDB this initializes the
# empty demo database once; later launches simply continue to Apache.
php spark migrate --all --no-interaction

exec apache2-foreground
