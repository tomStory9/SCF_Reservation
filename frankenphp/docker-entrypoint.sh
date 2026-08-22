#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then

    if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
        composer install --prefer-dist --no-progress --no-interaction
    fi

    php bin/console -V

    if grep -q ^DATABASE_URL= .env; then
        echo 'Waiting for database to be ready...'
        ATTEMPTS_LEFT_TO_REACH_DATABASE=60
        until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
            if [ $? -eq 255 ]; then
                ATTEMPTS_LEFT_TO_REACH_DATABASE=0
                break
            fi
            sleep 1
            ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
            echo "Still waiting for database to be ready... $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
        done

        if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
            echo 'The database is not up or not reachable:'
            echo "$DATABASE_ERROR"
            exit 1
        else
            echo 'The database is now ready and reachable'
        fi

        if find ./migrations -iname '*.php' -print -quit | grep --quiet .; then
            if php bin/console doctrine:migrations:status --no-interaction | grep -q "New Migrations:.*[1-9]"; then
                echo 'Pending migrations detected, running migrations...'
                php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
                php bin/console doctrine:fixtures:load --no-interaction
            else
                echo 'Database already up to date, no migration to run.'
            fi
        fi
    fi

    echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"