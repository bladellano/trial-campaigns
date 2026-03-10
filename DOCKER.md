# 🐳 Docker - Guia Completo

## 🚀 Quick Start

```bash
# 1. Adicionar domínio ao hosts
echo "127.0.0.1 trial-campaigns.docker.local" | sudo tee -a /etc/hosts

# 2. Subir ambiente
make setup

# 3. Acessar
# → http://trial-campaigns.docker.local
# → http://localhost:8080 (Traefik Dashboard)
```

## 📦 Stack

| Serviço | Imagem | Porta | Descrição |
|---------|--------|-------|-----------|
| **traefik** | traefik:v3.0 | 80, 8080 | Reverse Proxy |
| **app** | php:8.4-fpm | 5173 | Laravel + Node.js |
| **nginx** | nginx:alpine | - | Web Server |
| **db** | mysql:8.0 | 3306 | MySQL |
| **redis** | redis:alpine | 6379 | Cache/Queue |

## 🛠️ Comandos

```bash
make help       # Ver todos os comandos
make up         # Subir containers
make down       # Parar containers
make logs       # Ver logs
make shell      # Bash no container
make redis      # Redis CLI
make test       # Executar testes
make queue      # Queue worker
make migrate    # Migrations
make fresh      # Reset banco
make clean      # Limpar cache Docker
```

## 🔧 Desenvolvimento

### Rodar Vite (Hot Reload)
```bash
docker-compose exec app npm run dev
```

### Executar comandos Artisan
```bash
docker-compose exec app php artisan <comando>
# Exemplo:
docker-compose exec app php artisan tinker
```

### Acessar banco de dados
```bash
# Via host local
mysql -h 127.0.0.1 -P 3306 -u root -proot laravel_db

# Ou usar uma GUI: TablePlus, MySQL Workbench, etc.
```

## 🩺 Troubleshooting

### Falta de espaço no Docker
```bash
make clean
```

### MySQL não está pronto
```bash
# Aguarde o healthcheck (~20s)
docker-compose ps  # Verifique status "healthy"
```

### Porta em uso
```bash
# Verifique o que está usando a porta
lsof -i :80
lsof -i :3306

# Pare o serviço conflitante ou mude a porta no docker-compose.yml
```

### Permissões incorretas
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Rebuild completo
```bash
make down
make clean
make setup
```

## 🔐 Variáveis de Ambiente

### Desenvolvimento (Docker)
```env
DB_HOST=db
REDIS_HOST=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

### Produção
- Use senhas fortes para DB e Redis
- Configure `APP_DEBUG=false` e `APP_ENV=production`
- Use HTTPS/SSL via Traefik
- Não exponha portas desnecessárias

## 📚 Referências

- [Traefik v3](https://doc.traefik.io/traefik/)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [PHP Extensions Installer](https://github.com/mlocati/docker-php-extension-installer)
