check:
	vendor/bin/behat -v
	vendor/bin/phpunit
	vendor/bin/phpstan analyse --level max src tests
	vendor/bin/php-cs-fixer fix --diff --dry-run --config=.php_cs.php --using-cache=no
