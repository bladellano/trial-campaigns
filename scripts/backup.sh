#!/bin/bash
set -e

# ===========================================
# SCRIPT DE BACKUP AUTOMATIZADO
# ===========================================
# Este script faz backup de MySQL, Redis e volumes Docker
# Uso: ./scripts/backup.sh [local|s3]
# ===========================================

BACKUP_DIR="${BACKUP_DIR:-./backups}"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS="${RETENTION_DAYS:-7}"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Criar diretório de backup
mkdir -p "$BACKUP_DIR"

# ===========================================
# 1. BACKUP DO MYSQL
# ===========================================
log_info "Iniciando backup do MySQL..."

DB_BACKUP_FILE="$BACKUP_DIR/mysql_backup_${DATE}.sql.gz"

if docker-compose exec -T db mysqladmin ping -h localhost 2>/dev/null; then
    # Verificar se temos variáveis de ambiente de produção
    if [ -f .env.production ]; then
        source .env.production
        DB_PASSWORD="${DB_PASSWORD:-root}"
    else
        DB_PASSWORD="root"
    fi

    docker-compose exec -T db mysqldump \
        --all-databases \
        --single-transaction \
        --quick \
        --lock-tables=false \
        -u root \
        -p"${DB_PASSWORD}" \
        | gzip > "$DB_BACKUP_FILE"

    log_info "Backup MySQL concluído: $DB_BACKUP_FILE"
else
    log_error "MySQL não está rodando!"
fi

# ===========================================
# 2. BACKUP DO REDIS
# ===========================================
log_info "Iniciando backup do Redis..."

REDIS_BACKUP_FILE="$BACKUP_DIR/redis_backup_${DATE}.rdb"

if docker-compose exec -T redis redis-cli ping 2>/dev/null; then
    # Forçar save do Redis
    docker-compose exec -T redis redis-cli BGSAVE
    sleep 2

    # Copiar arquivo RDB
    docker cp trial-campaigns-redis:/data/appendonly.aof "$BACKUP_DIR/redis_appendonly_${DATE}.aof" 2>/dev/null || true
    docker cp trial-campaigns-redis:/data/dump.rdb "$REDIS_BACKUP_FILE" 2>/dev/null || true

    log_info "Backup Redis concluído: $REDIS_BACKUP_FILE"
else
    log_warn "Redis não está rodando!"
fi

# ===========================================
# 3. BACKUP DOS VOLUMES DOCKER
# ===========================================
log_info "Iniciando backup dos volumes..."

VOLUMES_BACKUP_FILE="$BACKUP_DIR/volumes_backup_${DATE}.tar.gz"

docker run --rm \
    -v trial-campaigns_mysql-data:/mysql-data:ro \
    -v trial-campaigns_redis-data:/redis-data:ro \
    -v "$BACKUP_DIR":/backup \
    alpine \
    tar czf "/backup/volumes_backup_${DATE}.tar.gz" /mysql-data /redis-data

log_info "Backup volumes concluído: $VOLUMES_BACKUP_FILE"

# ===========================================
# 4. BACKUP DE ARQUIVOS DA APLICAÇÃO
# ===========================================
log_info "Iniciando backup dos arquivos da aplicação..."

APP_BACKUP_FILE="$BACKUP_DIR/app_storage_${DATE}.tar.gz"

tar czf "$APP_BACKUP_FILE" \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    storage/ 2>/dev/null || true

log_info "Backup storage concluído: $APP_BACKUP_FILE"

# ===========================================
# 5. UPLOAD PARA S3 (OPCIONAL)
# ===========================================
if [ "$1" == "s3" ]; then
    log_info "Enviando backups para S3..."

    if command -v aws &> /dev/null; then
        if [ -f .env.production ]; then
            source .env.production
        fi

        if [ -n "$AWS_BUCKET" ]; then
            aws s3 cp "$DB_BACKUP_FILE" "s3://${AWS_BUCKET}/mysql/" || log_error "Erro ao enviar MySQL para S3"
            aws s3 cp "$REDIS_BACKUP_FILE" "s3://${AWS_BUCKET}/redis/" || log_error "Erro ao enviar Redis para S3"
            aws s3 cp "$VOLUMES_BACKUP_FILE" "s3://${AWS_BUCKET}/volumes/" || log_error "Erro ao enviar volumes para S3"
            aws s3 cp "$APP_BACKUP_FILE" "s3://${AWS_BUCKET}/app/" || log_error "Erro ao enviar app para S3"

            log_info "Backups enviados para S3 com sucesso!"
        else
            log_warn "AWS_BUCKET não configurado no .env.production"
        fi
    else
        log_warn "AWS CLI não instalado. Pule o upload para S3 ou instale: brew install awscli"
    fi
fi

# ===========================================
# 6. LIMPEZA DE BACKUPS ANTIGOS
# ===========================================
log_info "Limpando backups antigos (mais de ${RETENTION_DAYS} dias)..."

find "$BACKUP_DIR" -name "*.sql.gz" -type f -mtime +${RETENTION_DAYS} -delete
find "$BACKUP_DIR" -name "*.rdb" -type f -mtime +${RETENTION_DAYS} -delete
find "$BACKUP_DIR" -name "*.aof" -type f -mtime +${RETENTION_DAYS} -delete
find "$BACKUP_DIR" -name "*.tar.gz" -type f -mtime +${RETENTION_DAYS} -delete

log_info "Limpeza concluída!"

# ===========================================
# RESUMO
# ===========================================
echo ""
log_info "═══════════════════════════════════════"
log_info "BACKUP CONCLUÍDO COM SUCESSO!"
log_info "═══════════════════════════════════════"
log_info "Data: $(date)"
log_info "Localização: $BACKUP_DIR"
echo ""
ls -lh "$BACKUP_DIR"/*_${DATE}.* 2>/dev/null || true
echo ""
log_info "Para restaurar, use: ./scripts/restore.sh"
log_info "═══════════════════════════════════════"
