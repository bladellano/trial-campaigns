.PHONY: help up down restart logs shell test clean

# Cores
GREEN  := \033[0;32m
YELLOW := \033[0;33m
NC     := \033[0m

help: ## Mostra comandos disponíveis
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2}'

up: ## Sobe containers
	docker-compose up -d

down: ## Para containers
	docker-compose down

restart: down up ## Reinicia containers

logs: ## Mostra logs
	docker-compose logs -f

shell: ## Acessa bash do container
	docker-compose exec app bash

test: ## Executa testes
	docker-compose exec app php artisan test

clean: ## Limpa cache do Docker
	@echo "$(YELLOW)Limpando cache...$(NC)"
	docker system prune -af
	@echo "$(GREEN)Limpeza concluída!$(NC)"

# Aliases úteis
build: ## Build dos containers
	docker-compose build

redis: ## Acessa Redis CLI
	docker-compose exec redis redis-cli

queue: ## Inicia queue worker
	docker-compose exec app php artisan queue:work --tries=3

migrate: ## Executa migrations
	docker-compose exec app php artisan migrate

fresh: ## Reset do banco
	docker-compose exec app php artisan migrate:fresh --seed

# Setup inicial completo
setup: up ## Setup completo (primeira vez)
	@echo "$(GREEN)Aguardando containers...$(NC)"
	@sleep 3
	docker-compose exec app composer install
	docker-compose exec app npm install
	docker-compose exec app cp -n .env.example .env || true
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate --seed
	@echo "$(GREEN)✓ Setup concluído!$(NC)"
	@echo "$(YELLOW)→ http://trial-campaigns.docker.local$(NC)"
