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

# Creates only migrations that have not yet run. On TiDB this initializes the
# empty demo database once; later launches simply continue to Apache.
# Run only the command-line migration in development mode so Spark emits the
# actual exception. APP_BASE_URL prevents CI's CLI URL validation from masking
# the database failure when the Vercel variable has not been configured yet.
if ! migration_output=$(CI_ENVIRONMENT=development APP_BASE_URL="${APP_BASE_URL:-https://catcare-360.vercel.app}" php -d display_errors=stderr -d log_errors=1 -d error_log=/dev/stderr spark migrate --all --no-interaction 2>&1); then
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
