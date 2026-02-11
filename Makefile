SHELL := /bin/bash

.PHONY: up install cim cr test

up:
	ddev start

install:
	ddev drush si -y minimal --account-name=admin --account-pass=admin
	ddev drush en -y seahub_work_orders

cim:
	ddev drush cim -y

cr:
	ddev drush cr

test:
	@ddev exec bash -lc 'mkdir -p /tmp/browser_output; export SIMPLETEST_DB="mysql://db:db@db:3306/db"; export BROWSERTEST_OUTPUT_DIRECTORY="/tmp/browser_output"; ./vendor/bin/phpunit -c web/core web/modules/custom/seahub_work_orders/tests/src'
