.PHONY: install
install:
	bin/console --env=prod cache:clear
	bin/console --env=prod slub:install

.PHONY: migrate
migrate:
	bin/console --env=prod cache:clear
	bin/console --env=prod doctrine:migrations:migrate --no-interaction

.PHONY: install-test
install-test:
	bin/console --env=test cache:clear
	bin/console --env=test slub:install -vvv

.PHONY: check
check:
	bin/console cache:clear --env=test
	vendor/bin/behat -f progress
	vendor/bin/phpunit --coverage-text --coverage-clover build/logs/clover.xml
	vendor/bin/phpstan analyse --level max src tests
	vendor/bin/php-cs-fixer fix --diff --dry-run --config=.php_cs.php --using-cache=no

.PHONY: tunnel
tunnel:
	ngrok http slub.test:80
	open https://api.slack.com/apps/AGAJXNKPG/event-subscriptions?

.PHONY: log-prod
log-prod:
	heroku logs --tail -a slub-akeneo

.PHONY: log-staging
log-staging:
	heroku logs --tail -a slub-test

.PHONY: log-staging
od-prod: # Open dashboard production
	open https://dashboard.heroku.com/apps/slub-akeneo

.PHONY: log-staging
od-staging: # Open dashboard staging
	open https://dashboard.heroku.com/apps/slub-test
