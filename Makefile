up:
	docker compose up -d --build
	docker compose exec php composer install
	docker compose exec php php artisan migrate
	docker compose restart nginx
	$(MAKE) mcp-build

down:
	docker compose down

shell:
	docker compose exec php bash

migrate:
	docker compose exec php php artisan migrate

mcp-build:
	cd mcp-server && npm install && npm run build

mcp-dev:
	cd mcp-server && npm run dev
