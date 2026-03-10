# Campaign Manager — Technical Trial

## Overview

Laravel application for managing email campaigns with queue-based processing.

**Task**: Review/fix existing code + Build REST API layer

## Quick Start (Docker)

```bash
# 1. Add domain to hosts
echo "127.0.0.1 trial-campaigns.docker.local" | sudo tee -a /etc/hosts

# 2. Setup environment
make setup

# 3. Access
# → http://trial-campaigns.docker.local
# → http://localhost:8080 (Traefik)
```

## Commands

```bash
make help     # List all commands
make logs     # View logs
make shell    # Access container bash
make test     # Run tests
make queue    # Start queue worker
make backup   # Backup (MySQL, Redis, volumes)
make health   # Health check all services
```

📖 **Documentation**: [DOCKER.md](DOCKER.md) | [PRODUCTION.md](PRODUCTION.md)

## Production Ready ✅

This project includes production-ready features (P0):

- 🔒 **SSL/HTTPS** - Let's Encrypt via Traefik (auto-renewal)
- 🔐 **Security** - Environment variables for passwords (no hardcoded)
- 💾 **Backups** - Automated backups (local + S3 support)
- 👷 **Queue Workers** - Supervisor managing 2 workers + scheduler
- 🏥 **Monitoring** - Health checks on all 5 services

**Deploy to production:**

```bash
# 1. Configure environment
cp .env.production.example .env.production
# Edit with your values (domain, passwords, etc)

# 2. Start production environment
make prod-up

# 3. Verify health
make health
```

See [PRODUCTION.md](PRODUCTION.md) for complete production guide.

## Local Setup (Without Docker)

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan queue:work
```

## What Exists

- Models: `Contact`, `ContactList`, `Campaign`, `CampaignSend`
- Service: `CampaignService`
- Job: `SendCampaignEmail`
- Middleware: `EnsureCampaignIsDraft`
- Scheduled command for campaigns

## Part 1 — Code Review

Review migrations, models, services, jobs, middleware, and scheduler.

Document in `CHANGES.md`:
- What the issue is
- Why it matters in production
- How you fixed it

## Part 2 — Build the API

### Contacts
- `GET /api/contacts` — paginated list
- `POST /api/contacts` — create
- `POST /api/contacts/{id}/unsubscribe`

### Contact Lists
- `GET /api/contact-lists`
- `POST /api/contact-lists`
- `POST /api/contact-lists/{id}/contacts`

### Campaigns
- `GET /api/campaigns` — list with stats
- `POST /api/campaigns` — create/schedule
- `GET /api/campaigns/{id}` — show with stats
- `POST /api/campaigns/{id}/dispatch`

### Requirements
- FormRequest validation
- Pagination on lists
- Stats via DB aggregation (not collection counting)
- At least one Feature test

## Deliverables

- Fixed codebase with `CHANGES.md`
- Working REST API
- Feature test(s)

Time estimate: 2–3 hours
