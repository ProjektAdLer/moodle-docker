#!/bin/sh

if test -f /bitnami/moodle/setup.php; then
        echo "adler setup.php exists -> this is not the first run"
        first_run=false
else
        echo "adler setup.php does not exists -> this is first run"
        cp /opt/adler/setup.php /bitnami/moodle/setup.php
        chown daemon /bitnami/moodle/setup.php
        first_run=true
fi

su daemon -s /bin/sh -c "php /bitnami/moodle/setup.php --first_run=$first_run"
