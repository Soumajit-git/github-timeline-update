#!/bin/bash
# This script should set up a CRON job to run cron.php every 5 minutes.
# You need to implement the CRON setup logic here.

# Make script executable
chmod +x setup_cron.sh

# Get absolute path to PHP and this cron file
PHP_PATH=$(which php)
CRON_FILE_PATH="$(cd "$(dirname "$0")"; pwd)/cron.php"

# CRON expression: every 5 minutes
CRON_JOB="*/5 * * * * $PHP_PATH $CRON_FILE_PATH > /dev/null 2>&1"

# Check if cron exists
(crontab -l 2>/dev/null | grep -Fv "$CRON_FILE_PATH"; echo "$CRON_JOB") | crontab -
echo "CRON job set to run every 5 minutes: $CRON_JOB"
