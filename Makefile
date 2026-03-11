.PHONY: init build up down restart shell artisan composer test coverage coverage-ci phpstan migrate fresh

COVERAGE_MIN ?= 85

# Initialize Laravel project (run once)
init:
	docker run --rm -v $(PWD):/app -w /app composer:latest composer create-project laravel/laravel temp --prefer-dist
	cp -r temp/. .
	rm -rf temp
	cp .env.example .env
	@echo "Laravel installed. Run 'make build && make up' to start."

# Build Docker images
build:
	docker compose build

# Start containers
up:
	docker compose up -d

# Stop containers
down:
	docker compose down

# Restart containers
restart:
	docker compose restart

# Access app shell
shell:
	docker compose exec app sh

# Run artisan command
artisan:
	docker compose exec app php artisan $(cmd)

# Run composer command
composer:
	docker compose exec app composer $(cmd)

# Run tests
test:
	docker compose exec app php artisan test

# Run tests with line coverage report
coverage:
	docker compose exec app php artisan test --coverage --min=$(COVERAGE_MIN)

# Run coverage gate in local/CI shell (non-docker wrapper)
coverage-ci:
	php artisan test --coverage --min=$(COVERAGE_MIN)

# Run static analysis in docker
phpstan:
	docker compose exec app ./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G

# Run migrations
migrate:
	docker compose exec app php artisan migrate

# Fresh migration with seed
fresh:
	docker compose exec app php artisan migrate:fresh --seed
