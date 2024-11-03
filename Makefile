.PHONY: check format help

all:
	@echo "Nothing yet!"

check:
	./vendor/bin/php-cs-fixer check .

format:
	./vendor/bin/php-cs-fixer fix .

help:
	@echo "Nothing yet!"
