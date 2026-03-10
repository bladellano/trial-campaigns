#!/bin/bash
# ===========================================
# SCRIPT DE RESTORE DE BACKUP
# ===========================================
# Restaura backups do MySQL, Redis e volumes
# Uso: ./scripts/restore.sh BACKUP_DATE
# Exemplo: ./scripts/restore.sh 20260310_143000
# ===========================================

set -e

if [ -z "$1" ]; then
    echo "Uso: ./scripts/restore.sh BACKUP_DATE"
    echo "Exemplo: ./scripts/restore.sh 20260310_143000"
    echo ""
    echo "Backups disponíveis:"
    ls -lh backups/*.sql.gz 2>/dev/null | awk '{print $NF}' | sed 's/backups\/mysql_backup_/  /' | sed 's/\.sql\.gz//'
    exit 1
fi

BACKUP_DIR="${BACKUP_DIR:-./backups}"
DATE=$1

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se backup existe
DB_BACKUP_FILE="$BACKUP_DIR/mysql_backup_${DATE}.sql.gz"
REDIS_BACKUP_FILE="$BACKUP_DIR/redis_backup_${DATE}.rdb"
VOLUMES_BACKUP_FILE="$BACKUP_DIR/volumes_backup_${DATE}.tar.gz"

if [ ! -f "$DB_BACKUP_FILE" ]; then
    log_error "Backup não encontrado: $DB_BACKUP_FILE"
    exit 1
fi

# AVISO
echo -e "${RED}═══════════════════════════════════════${NC}"
echo -e "${RED}  ATENÇÃO: OPERAÇÃO DESTRUTIVA!${NC}"
echo -e "${RED}═══════════════════════════════════════${NC}"
echo -e "${YELLOW}Esta operação irá SOBRESCREVER os dados atuais!${NC}"
echo -e "${YELLOW}Certifique-se de ter um backup recente antes de continuar.${NC}"
echo ""
read -p "Deseja continuar? (digite 'sim' para confirmar): " confirm

if [ "$confirm" != "sim" ]; then
    log_warn "Operação cancelada pelo usuário."
    exit 0
fi

# ===========================================
# 1. RESTORE DO MYSQL
# ===========================================
log_info "Iniciando restore do MySQL..."

if [ -f .env.production ]; then
    source .env.production
    DB_PASSWORD="${DB_PASSWORD:-root}"
else
    DB_PASSWORD="root"
fi

gunzip < "$DB_BACKUP_FILE" | docker-compose exec -T db mysql -u root -p"${DB_PASSWORD}"

log_info "Restore MySQL concluído!"

# ===========================================
# 2. RESTORE DO REDIS (OPCIONAL)
# ===========================================
if [ -f "$REDIS_BACKUP_FILE" ]; then
    log_info "Iniciando restore do Redis..."

    docker-compose stop redis
    docker cp "$REDIS_BACKUP_FILE" trial-campaigns-redis:/data/dump.rdb
    docker-compose start redis

    log_info "Restore Redis concluído!"
else
    log_warn "Backup Redis não encontrado, pulando..."
fi

# ===========================================
# RESUMO
# ===========================================
echo ""
log_info "═══════════════════════════════════════"
log_info "RESTORE CONCLUÍDO!"
log_info "═══════════════════════════════════════"
log_info "Data do backup: $DATE"
echo ""
