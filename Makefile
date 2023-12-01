SHELL := /bin/bash
.ONESHELL:
.SHELLFLAGS = -c -e

.PHONY=build
build: _requirements.check-all
	docker build . -t welfaire-recruitment-test-backend \
		--build-arg WF_UID=$$(id -u) \
		--build-arg WF_GID=$$(id -g)

.PHONY=clean
clean:
	@if [[ "$$(docker images -q welfaire-recruitment-test-backend)" != "" ]]
	then
		docker rmi welfaire-recruitment-test-backend
	fi
	rm -rf .tmp/*.txt vendor

.PHONY=run.shell
run.shell:
	$(MAKE) _run _CMD='/bin/bash -l'

.PHONY=run.test
run.test:
	$(MAKE) run._test _TARGET=.

.PHONY=run.test.sales-data-analyzer
run.test.sales-data-analyzer:
	$(MAKE) run._test _TARGET=tests/SalesDataAnalyzerTest.php

.PHONY=run.test.php-code-processor
run.test.php-code-processor:
	$(MAKE) run._test _TARGET=tests/PhpCodeProcessorTest.php

.PHONY=run._test
run._test:
	$(MAKE) _run _CMD='/bin/bash -l -c "composer install"'
	$(MAKE) _run _CMD='/bin/bash -l -c "./vendor/bin/phpunit --testdox $(_TARGET)"'

.PHONY=_run
_run:
	@if [[ "$$(docker images -q welfaire-recruitment-test-backend)" == "" ]]
	then
		$(MAKE) build
	fi
	docker run \
		--mount type=bind,source=$$PWD,target=/home/welfaire/project \
		-it --rm \
		welfaire-recruitment-test-backend $(_CMD)

.PHONY: _requirements.check-all
_requirements.check-all: _requirements.check-linux _requirements.check-docker _requirements.check-user

.PHONY: _requirements.check-linux
_requirements.check-linux:
	@if [[ $$(uname) != "Linux" ]]
	then
		echo -ne "\033[0;31m"
		echo "Your OS is not based on GNU/Linux" >&2
		echo -ne "\033[0m"
		exit 1
	fi

.PHONY: _requirements.check-docker
_requirements.check-docker:
	@docker -v > /dev/null 2>&1
	if [[ $$? != 0 ]]
	then
		echo -ne "\033[0;31m"
		echo "You must install 'docker'" >&2
		echo -ne "\033[0m"
		exit 1
	fi

.PHONY: _requirements.check-user
_requirements.check-user:
	groups $$USER | grep -E '\bdocker\b' >/dev/null
	if [[ $$? != 0 ]]
	then
		echo -ne "\033[0;31m"
		echo "The current user must be a member of 'docker' group" >&2
		echo -ne "\033[0m"
		exit 1
	fi
