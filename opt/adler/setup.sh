#!/bin/bash

if test -f /bitnami/moodle/adler_setup/setup.php; then
        echo "adler setup.php exists -> this is not the first run"
        first_run=false
else
        echo "adler setup.php does not exists -> this is first run"
        first_run=true
fi

rm -r /bitnami/moodle/adler_setup  # cleanup first
cp -r /opt/adler/moodle/adler_setup /bitnami/moodle/
chown -R daemon /bitnami/moodle/adler_setup
su daemon -s /bin/sh -c "php /bitnami/moodle/adler_setup/setup.php --first_run=$first_run ${USER_NAME:+--user_name=$USER_NAME} ${USER_PASSWORD:+--user_password=$USER_PASSWORD} ${USER_FIRST_NAME:+--user_first_name=$USER_FIRST_NAME} ${USER_LAST_NAME:+--user_last_name=$USER_LAST_NAME} ${USER_EMAIL:+--user_email=$USER_EMAIL} ${USER_ROLE:+--user_role=$USER_ROLE} ${PLUGIN_VERSION:+--plugin_version=$PLUGIN_VERSION}"

echo "finished adler setup/update script"
