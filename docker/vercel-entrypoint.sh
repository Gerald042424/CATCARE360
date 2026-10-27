#!/bin/sh
set -eu

# Vercel supplies PORT at runtime. Apache defaults to port 80, so update both
# its listener and virtual-host declaration before starting the web server.
port="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

# Creates only migrations that have not yet run. On TiDB this initializes the
# empty demo database once; later launches simply continue to Apache.
if ! CI_ENVIRONMENT=development php -d display_errors=stderr -d log_errors=1 -d error_log=/dev/stderr spark migrate --all --no-interaction >&2; then
    echo "CatCare 360 startup failed while initializing the database schema."
    # Send only framework startup diagnostics to Vercel. Redact connection
    # credentials before emitting the log stream.
    tail -n 120 writable/logs/log-*.log 2>/dev/null \
        | sed -E 's/(password|pwd|token|secret)[^[:space:]]*/\1=[REDACTED]/Ig' \
        >&2 || true
    exit 1
fi

exec apache2-foreground
