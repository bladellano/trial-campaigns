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

---

## ✅ Implementation Status

### Part 1 — Code Review ✅ **COMPLETE**

**Fixed Issues:**
- Database schema (missing indexes, wrong data types)
- N+1 queries in models
- Queue architecture (chunking, retry logic, idempotency)
- Business logic bugs (middleware, scheduler)

📄 **Documentation:** [CHANGES.md](CHANGES.md) — Detailed documentation of all 8 issues found and fixed

### Part 2 — REST API ✅ **COMPLETE**

**What Was Built:**
- 3 API Controllers (Contacts, Contact Lists, Campaigns)
- 4 FormRequest classes with validation
- 3 API Resources (JSON transformation)
- 11 RESTful endpoints
- 26 Feature Tests (100% passing)

📄 **Documentation:** [API-IMPLEMENTATION.md](API-IMPLEMENTATION.md) — Complete API reference with examples

---

## 🚀 Using the API

### Quick Example

```bash
# Create a contact
curl -X POST http://trial-campaigns.docker.local/api/contacts \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com"}'

# List contacts (paginated)
curl http://trial-campaigns.docker.local/api/contacts

# Create a contact list
curl -X POST http://trial-campaigns.docker.local/api/contact-lists \
  -H "Content-Type: application/json" \
  -d '{"name":"Newsletter","description":"Monthly subscribers"}'

# Add contact to list
curl -X POST http://trial-campaigns.docker.local/api/contact-lists/1/contacts \
  -H "Content-Type: application/json" \
  -d '{"contact_id":1}'

# Create campaign
curl -X POST http://trial-campaigns.docker.local/api/campaigns \
  -H "Content-Type: application/json" \
  -d '{"subject":"Welcome!","body":"Thanks for joining","contact_list_id":1}'

# Dispatch campaign
curl -X POST http://trial-campaigns.docker.local/api/campaigns/1/dispatch

# Check campaign stats
curl http://trial-campaigns.docker.local/api/campaigns/1
```

### All Endpoints

**Contacts:**
- `GET /api/contacts` — List (paginated, 15/page)
- `POST /api/contacts` — Create (validates email uniqueness)
- `POST /api/contacts/{id}/unsubscribe` — Mark as unsubscribed

**Contact Lists:**
- `GET /api/contact-lists` — List with contacts count
- `POST /api/contact-lists` — Create
- `POST /api/contact-lists/{id}/contacts` — Add contact (idempotent)

**Campaigns:**
- `GET /api/campaigns` — List with stats (paginated, 15/page)
- `POST /api/campaigns` — Create (always starts as "draft")
- `GET /api/campaigns/{id}` — Show with stats
- `POST /api/campaigns/{id}/dispatch` — Send immediately (draft only)

**Features:**
- FormRequest validation on all POST endpoints
- API Resources for consistent JSON responses
- Database aggregation for campaign stats (no N+1)
- Pagination with metadata (current_page, total, links)
- Idempotent operations (add contact, dispatch)
- ISO 8601 timestamps

📖 **Full documentation:** [API-IMPLEMENTATION.md](API-IMPLEMENTATION.md)

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run only API tests
php artisan test --filter=Api

# Test coverage
php artisan test --coverage
```

**Test Results:**
- 26 API Feature Tests ✅ 100% passing
- Covers all endpoints, validation, and edge cases
- Tests idempotency, pagination, DB aggregation

---

## Deliverables

- Fixed codebase with `CHANGES.md`
- Working REST API
- Feature test(s)

Time estimate: 2–3 hours
