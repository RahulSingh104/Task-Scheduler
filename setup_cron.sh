#!/bin/bash

# Get the absolute path to the cron.php file
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CRON_PHP="$DIR/src/cron.php"

# Path to the PHP binary (adjust if necessary)
PHP_BINARY="/usr/bin/php"

# Define the CRON job schedule (every hour)
CRON_SCHEDULE="0 * * * * $PHP_BINARY $CRON_PHP >> /dev/null 2>&1"

# Add the CRON job to the user's crontab
(crontab -l 2>/dev/null | grep -v "$CRON_PHP"; echo "$CRON_SCHEDULE") | crontab -

echo "CRON job set up successfully to run every hour."