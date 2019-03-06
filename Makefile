.PHONY: release

analyze:
	vendor/bin/psalm

test:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-clover=build/logs/clover.xml

release:
	/usr/bin/env php release