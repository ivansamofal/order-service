.PHONY: up down build bash migrate cc

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

bash:
	docker compose exec php sh

migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

cc:
	docker compose exec php php bin/console cache:clear

# Start full stack: product-service first (creates shared network + rabbitmq),
# then order-service joins the shared network.
stack-up:
	docker compose up -d
	cd ../order-service && docker compose up -d

stack-down:
	cd ../order-service && docker compose down
	docker compose down
