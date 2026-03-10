#!/bin/bash
# ===========================================
# DOCKER ENTRYPOINT PARA PRODUÇÃO
# ===========================================
# Inicia PHP-FPM com Supervisor (queue workers + scheduler)
# ===========================================

set -e

echo "[ENTRYPOINT] Iniciando aplicação em modo produção..."

# Aguardar banco de dados estar pronto
echo "[ENTRYPOINT] Aguardando MySQL..."
while ! php artisan db:show > /dev/null 2>&1; do
    echo "[ENTRYPOINT] MySQL não está pronto - aguardando..."
    sleep 2
done

echo "[ENTRYPOINT] MySQL pronto!"

# Criar diretórios necessários
mkdir -p /var/www/storage/logs

# Garantir permissões corretas
chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Iniciar Supervisor como root (ele gerencia processos do usuário laravel)
echo "[ENTRYPOINT] Iniciando Supervisor..."
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf &

# Aguardar um pouco para o Supervisor iniciar
sleep 2

# Exibir status do Supervisor
echo "[ENTRYPOINT] Status do Supervisor:"
supervisorctl status || true

# Iniciar PHP-FPM em foreground
echo "[ENTRYPOINT] Iniciando PHP-FPM..."
exec php-fpm
