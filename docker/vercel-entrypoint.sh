#!/bin/sh
set -eu

# Vercel supplies PORT at runtime. Apache defaults to port 80, so update both
# its listener and virtual-host declaration before starting the web server.
port="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

# Creates only migrations that have not yet run. On TiDB this initializes the
# empty demo database once; later launches simply continue to Apache.
if ! php -d display_errors=1 -d log_errors=0 spark migrate --all --no-interaction 2>&1; then
    echo "CatCare 360 startup failed while initializing the database schema."
    exit 1
fi

exec apache2-foreground
