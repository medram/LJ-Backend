.PHONY: check format help

all:
	@echo "Nothing yet!"

check:
	./vendor/bin/php-cs-fixer check .

format:
	./vendor/bin/php-cs-fixer fix .

lint: # find . -name '*.php' ! -path './vendor/*' | while read file; do php -l "$file"; done
	find . -name '*.php' ! -path './vendor/*' | xargs -I % php -l %

help:
	@echo "Nothing yet!"
