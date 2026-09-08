.PHONY: deps fmt lint build test
deps:
	composer install
fmt:
	composer run fmt
lint:
	composer run lint && composer run fmt:check
build:
	find src -name '*.php' -print0 | xargs -0 -n1 php -l
test:
	composer run test
