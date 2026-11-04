#!/bin/bash
# run.sh - Starts the local PHP development server
cd "$(dirname "$0")"

# Ensure PostgreSQL is running
if ! (echo > /dev/tcp/localhost/5432) 2>/dev/null; then
    if [ -x "$HOME/pgsql/bin/pg_ctl" ] && [ -d "$HOME/pgdata" ]; then
        echo "Starting PostgreSQL server..."
        "$HOME/pgsql/bin/pg_ctl" -D "$HOME/pgdata" -l "$HOME/pgdata/logfile" start
    fi
fi

PORT=8000
echo "=========================================================="
echo " 🌾 Agriculture Information System (AIS)"
echo " Server starting at: http://localhost:$PORT"
echo " Press Ctrl + C to stop the server."
echo "=========================================================="
php -S localhost:$PORT

