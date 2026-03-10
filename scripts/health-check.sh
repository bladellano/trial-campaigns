#!/bin/bash
# ===========================================
# SCRIPT DE MONITORAMENTO / HEALTH CHECK
# ===========================================
# Verifica o status de todos os serviços
# Uso: ./scripts/health-check.sh
# ===========================================

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

FAILURES=0

check_service() {
    local service_name=$1
    local check_command=$2
    local description=$3

    echo -n "Verificando ${description}... "

    if eval "$check_command" &>/dev/null; then
        echo -e "${GREEN}✓ OK${NC}"
        return 0
    else
        echo -e "${RED}✗ FALHOU${NC}"
        FAILURES=$((FAILURES + 1))
        return 1
    fi
}

echo -e "${BLUE}═══════════════════════════════════════${NC}"
echo -e "${BLUE}  HEALTH CHECK - TRIAL CAMPAIGNS${NC}"
echo -e "${BLUE}═══════════════════════════════════════${NC}"
echo ""

# Verificar containers Docker
echo -e "${YELLOW}[1] Containers Docker${NC}"
check_service "traefik" "docker ps | grep -q trial-campaigns-traefik" "Traefik (Reverse Proxy)"
check_service "app" "docker ps | grep -q trial-campaigns-app" "App (PHP-FPM)"
check_service "nginx" "docker ps | grep -q trial-campaigns-nginx" "Nginx (Web Server)"
check_service "db" "docker ps | grep -q trial-campaigns-db" "MySQL (Database)"
check_service "redis" "docker ps | grep -q trial-campaigns-redis" "Redis (Cache/Queue)"
echo ""

# Verificar serviços
echo -e "${YELLOW}[2] Serviços${NC}"
check_service "mysql-ping" "docker-compose exec -T db mysqladmin ping -h localhost" "MySQL Connection"
check_service "redis-ping" "docker-compose exec -T redis redis-cli ping | grep -q PONG" "Redis Connection"
echo ""

# Verificar aplicação web
echo -e "${YELLOW}[3] Aplicação Web${NC}"
check_service "http" "curl -f -s http://trial-campaigns.docker.local > /dev/null" "HTTP Response"

# Verificar se está em produção com HTTPS
if [ -f .env.production ]; then
    source .env.production
    if [ -n "$DOMAIN" ]; then
        check_service "https" "curl -f -s -k https://${DOMAIN} > /dev/null" "HTTPS Response"
    fi
fi
echo ""

# Verificar Traefik dashboard
echo -e "${YELLOW}[4] Traefik Dashboard${NC}"
check_service "dashboard" "curl -f -s http://localhost:8080/api/http/routers > /dev/null" "Traefik API"
echo ""

# Verificar uso de recursos
echo -e "${YELLOW}[5] Uso de Recursos${NC}"
echo -e "Containers rodando:"
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}" | grep trial-campaigns || true
echo ""

# Verificar espaço em disco
echo -e "${YELLOW}[6] Espaço em Disco${NC}"
df -h | grep -E "Filesystem|/docker|/$" || df -h | head -n 2
echo ""

# Verificar volumes
echo -e "${YELLOW}[7] Volumes Docker${NC}"
docker volume ls | grep trial-campaigns
echo ""

# Verificar logs recentes de erro
echo -e "${YELLOW}[8] Logs Recentes (últimas 5 linhas)${NC}"
echo -e "${BLUE}App:${NC}"
docker-compose logs --tail=5 app 2>/dev/null | tail -n 5 || echo "Sem logs"
echo -e "${BLUE}Nginx:${NC}"
docker-compose logs --tail=5 nginx 2>/dev/null | tail -n 5 || echo "Sem logs"
echo ""

# Verificar queue workers (se Supervisor estiver rodando)
echo -e "${YELLOW}[9] Queue Workers${NC}"
docker-compose exec -T app supervisorctl status 2>/dev/null || echo "Supervisor não está rodando (apenas em produção)"
echo ""

# Resumo final
echo -e "${BLUE}═══════════════════════════════════════${NC}"
if [ $FAILURES -eq 0 ]; then
    echo -e "${GREEN}✓ TODOS OS CHECKS PASSARAM!${NC}"
    exit 0
else
    echo -e "${RED}✗ $FAILURES CHECK(S) FALHARAM!${NC}"
    exit 1
fi
