#!/bin/sh
set -eu

# Vercel supplies PORT at runtime. Apache defaults to port 80, so update both
# its listener and virtual-host declaration before starting the web server.
port="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

# The container image itself is immutable on Vercel. CodeIgniter's cache,
# session/log paths and uploaded demo files therefore live under /tmp.
mkdir -p /tmp/catcare-writable/cache /tmp/catcare-writable/logs /tmp/catcare-writable/session /tmp/catcare-uploads
chmod -R a+rwx /tmp/catcare-writable /tmp/catcare-uploads

# Do not run database migrations here. Vercel may create a fresh container for
# each cold start, and a network migration can exceed its startup window before
# Apache begins listening. Apply migrations as a one-time release operation
# after the production database credentials are configured.

exec apache2-foreground
