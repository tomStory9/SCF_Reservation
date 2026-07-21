#!/bin/sh
set -e

# Check if the first argument starts with a dash (likely an option)
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

# Check if the command is php-fpm, php, or bin/console
if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	# Install dependencies and set up the database if not in production environment
	if [ "$APP_ENV" != 'prod' ]; then
		composer db
	fi

	chmod +x bin/console
	# Wait for the database to be ready before executing further commands
	echo "Waiting for the database to be ready..."
	ATTEMPTS_LEFT=60
	until bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1 || [ $ATTEMPTS_LEFT -eq 0 ]; do
		sleep 1
		ATTEMPTS_LEFT=$((ATTEMPTS_LEFT - 1))
		echo "Retrying... $ATTEMPTS_LEFT attempts left."
	done

	if [ $ATTEMPTS_LEFT -eq 0 ]; then
		echo "Unable to reach the database."
		exit 1
	fi
	echo "database checking"
	# Check if the database is empty or not
	TABLE_COUNT=$(bin/console doctrine:query:sql "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES" | grep -o '[0-9]*')

	if [ "$TABLE_COUNT" -le 208 ]; then
		echo "Database is empty. Running Doctrine commands..."
		# Run necessary Doctrine commands
		bin/console doctrine:database:drop --force
		bin/console doctrine:database:create
		bin/console doctrine:schema:create
	else
		bin/console doctrine:migrations:migrate latest
	fi

	# Set proper permissions for Symfony application
	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var

fi

# Execute the Docker PHP entrypoint with the provided arguments
exec docker-php-entrypoint "$@"