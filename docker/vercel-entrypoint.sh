#!/bin/sh
set -eu

# Vercel supplies PORT at runtime. Apache defaults to port 80, so update both
# its listener and virtual-host declaration before starting the web server.
port="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

# Creates only migrations that have not yet run. On TiDB this initializes the
# empty demo database once; later launches simply continue to Apache.
# Do not override CI_ENVIRONMENT here. Forcing "development" makes CI validate
# a CLI base URL before it can connect to TiDB, even when production is set in
# Vercel's environment variables.
if ! migration_output=$(php -d display_errors=stderr -d log_errors=1 -d error_log=/dev/stderr spark migrate --all --no-interaction 2>&1); then
    echo "CatCare 360 startup failed while initializing the database schema."
    # Vercel retains only a limited number of output lines per invocation.
    # Print the beginning, where Spark places the actual exception message,
    # instead of a long trailing stack trace.
    printf '%s\n' "$migration_output" \
        | sed -E 's/(password|pwd|token|secret)[^[:space:]]*/\1=[REDACTED]/Ig' \
        | head -n 24 \
        >&2
    # Send only framework startup diagnostics to Vercel. Redact connection
    # credentials before emitting the log stream.
    tail -n 5 writable/logs/log-*.log 2>/dev/null \
        | sed -E 's/(password|pwd|token|secret)[^[:space:]]*/\1=[REDACTED]/Ig' \
        >&2 || true
    exit 1
fi

exec apache2-foreground
