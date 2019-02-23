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
