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

# Produção
prod-up: ## Sobe ambiente de produção
	docker-compose -f docker-compose.prod.yml up -d
	@echo "$(GREEN)Ambiente de produção iniciado!$(NC)"

prod-down: ## Para ambiente de produção
	docker-compose -f docker-compose.prod.yml down

prod-logs: ## Logs de produção
	docker-compose -f docker-compose.prod.yml logs -f

# Backup e Restore
backup: ## Faz backup completo (MySQL, Redis, volumes)
	@echo "$(YELLOW)Iniciando backup...$(NC)"
	./scripts/backup.sh
	@echo "$(GREEN)Backup concluído!$(NC)"

backup-s3: ## Faz backup e envia para S3
	@echo "$(YELLOW)Iniciando backup com upload S3...$(NC)"
	./scripts/backup.sh s3
	@echo "$(GREEN)Backup e upload concluídos!$(NC)"

restore: ## Restaura backup (uso: make restore DATE=20260310_143000)
ifndef DATE
	@echo "$(YELLOW)Uso: make restore DATE=20260310_143000$(NC)"
	@echo "Backups disponíveis:"
	@ls -1 backups/*.sql.gz 2>/dev/null | sed 's/backups\/mysql_backup_/  /' | sed 's/\.sql\.gz//' || echo "Nenhum backup encontrado"
else
	@echo "$(YELLOW)Restaurando backup $(DATE)...$(NC)"
	./scripts/restore.sh $(DATE)
	@echo "$(GREEN)Restore concluído!$(NC)"
endif

# Monitoramento
health: ## Verifica status de todos os serviços
	@./scripts/health-check.sh

status: ## Mostra status dos containers
	@docker-compose ps

stats: ## Mostra uso de recursos
	@docker stats --no-stream trial-campaigns-app trial-campaigns-nginx trial-campaigns-db trial-campaigns-redis trial-campaigns-traefik
