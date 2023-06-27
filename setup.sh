#!/bin/bash

if test -f /bitnami/moodle/setup.php; then
        echo "adler setup.php exists -> this is not the first run"
        first_run=false
else
        echo "adler setup.php does not exists -> this is first run"
        cp /opt/adler/setup.php /bitnami/moodle/setup.php
        chown daemon /bitnami/moodle/setup.php
        first_run=true
fi

su daemon -s /bin/sh -c "php /bitnami/moodle/setup.php --first_run=$first_run ${DEFAULT_USER_NAME:+--default_user_name=$DEFAULT_USER_NAME} ${DEFAULT_USER_PASSWORD:+--default_user_password=$DEFAULT_USER_PASSWORD} ${DEFAULT_USER_FIRST_NAME:+--default_user_first_name=$DEFAULT_USER_FIRST_NAME} ${DEFAULT_USER_LAST_NAME:+--default_user_last_name=$DEFAULT_USER_LAST_NAME} ${DEFAULT_USER_EMAIL:+--default_user_email=$DEFAULT_USER_EMAIL}"

echo "finished adler setup/update script"
