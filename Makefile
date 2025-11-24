# -------------------------------------------------------
#  lambda-symfony-template — Makefile
#  ------------------------------------------------------
#  This Makefile provides a clean developer experience for:
#   - local development
#   - running tests
#   - deploying to AWS Lambda
#   - tailing CloudWatch logs
#   - clearing cache / installing dependencies
# -------------------------------------------------------

# Colors (for nicer terminal output)
GREEN=\033[0;32m
NC=\033[0m

# Default host/port for local development using PHP built-in server
HOST=localhost
PORT=8000

# Serverless function name (from serverless.yml)
FUNCTION=web


# -------------------------------------------------------
#  Local Development
# -------------------------------------------------------

# Start Symfony in dev mode using the Symfony CLI (recommended)
start:
	@echo "$(GREEN)> Starting Symfony server in dev mode...$(NC)"
	APP_ENV=dev symfony server:start

# Alternative: start the PHP built-in server (if not using Symfony CLI)
start-php:
	@echo "$(GREEN)> Starting PHP built-in server on $(HOST):$(PORT)...$(NC)"
	APP_ENV=dev php -S $(HOST):$(PORT) -t public


# -------------------------------------------------------
#  Testing
# -------------------------------------------------------

test:
	@echo "$(GREEN)> Running test suite...$(NC)"
	vendor/bin/phpunit


# -------------------------------------------------------
#  Cache / Cleanup
# -------------------------------------------------------

# Clear all Symfony caches + PHPUnit cache
clean:
	@echo "$(GREEN)> Clearing Symfony and PHPUnit caches...$(NC)"
	rm -rf var/cache/*
	rm -rf .phpunit.cache

# Clear + rebuild prod cache locally (useful before deploy)
cache:
	@echo "$(GREEN)> Rebuilding prod cache...$(NC)"
	APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear


# -------------------------------------------------------
#  Composer
# -------------------------------------------------------

install:
	@echo "$(GREEN)> Installing Composer dependencies...$(NC)"
	composer install --no-interaction

update:
	@echo "$(GREEN)> Updating Composer dependencies...$(NC)"
	composer update --no-interaction


# -------------------------------------------------------
#  Serverless / AWS Lambda
# -------------------------------------------------------

# Deploy to AWS Lambda using Serverless Framework
deploy:
	@echo "$(GREEN)> Deploying to AWS Lambda...$(NC)"
	serverless deploy

# Remove the deployment from AWS
remove:
	@echo "$(GREEN)> Removing deployment from AWS Lambda...$(NC)"
	serverless remove

# Tail CloudWatch logs for the main function
logs:
	@echo "$(GREEN)> Tailing CloudWatch logs for '$(FUNCTION)'...$(NC)"
	serverless logs -f $(FUNCTION) --tail

# Dry-run print of the final serverless configuration
sls-print:
	@echo "$(GREEN)> Validating Serverless config...$(NC)"
	serverless print

# Build local AWS-style environment
lambda-local-build:
	docker compose pull app

# Run local AWS-style environment
lambda-local-up:
	docker compose up

# Run in detached mode
lambda-local-up-d:
	docker compose up -d

# Stop local environment
lambda-local-down:
	docker compose down
