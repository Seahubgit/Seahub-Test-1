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
	ddev phpunit -c web/core --testsuite seahub_work_orders
