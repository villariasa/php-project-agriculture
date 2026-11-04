#!/bin/bash
set -e

# Default to port 80 if PORT is not set by the hosting environment
export PORT="${PORT:-80}"

# Ensure config/Database.php exists for runtime (copied from example if gitignored)
if [ ! -f /var/www/html/config/Database.php ]; then
    cp /var/www/html/config/Database.example.php /var/www/html/config/Database.php
fi

# Dynamically update Apache configuration to listen on the assigned PORT
sed -i "s/Listen [0-9]*/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf

# Start Apache in the foreground
exec apache2-foreground
