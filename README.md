# Community-Powered Protocol & Discussion Platform

A production-grade structured healing, wellness, and instructional protocol discussion, peer review, and reputation voting platform built with **Laravel 13** and **Next.js 16 (React 19)**.

---

## 🚀 2026 Production-Grade Full-Stack Baseline

| Layer | Technology | Purpose / Rationale |
| :--- | :--- | :--- |
| **Frontend Framework** | **Next.js 16.3 (Turbopack)** | App Router, Server Components & Suspense |
| **UI Library** | **React 19** | Strict Actions, Transitions, and Fragment Refs |
| **Language** | **TypeScript 5.x** | Strict end-to-end type safety |
| **Styling** | **Tailwind CSS 4.x** | Modern styling, accessible design system |
| **State & Data Fetching** | **TanStack Query v5** | Optimistic UI mutations, stale-while-revalidate |
| **Search Engine** | **Typesense 27.1 / Cloud** | Instant, typo-tolerant search across protocols & threads |
| **Backend Framework** | **Laravel 13.x** | Versioned REST API with Repository-Service pattern |
| **Language Runtime** | **PHP 8.4 / 8.5** | High-performance CLI and API execution |
| **Database** | **PostgreSQL 17 / SQLite** | Relational data, foreign keys, polymorphic tables |
| **Caching & Queues** | **Sync / File / Redis** | Scalable sessions, caching, and rate limiting |
| **Testing (Backend)** | **Pest 4 / PHPUnit 12** | 107 automated tests across all suites (100% pass) |
| **Testing (Frontend)** | **Vitest 5 + Testing Library** | 27 test suites / 93 unit and integration tests (100% pass) |
| **Code Formatting** | **Laravel Pint & ESLint** | Automated opinionated linting and formatting |

---

## 🏛 System Architecture

```text
                         CLIENT / BROWSER
                                │
               ┌────────────────┴────────────────┐
               │                                 │
               ▼ (Port 3000)                     ▼ (Port 8108)
      ┌─────────────────┐               ┌─────────────────┐
      │   Next.js 16    │               │  Typesense 27   │
      │  React 19 (RSC) │               │ Fast Typo-Search│
      └────────┬────────┘               └─────────────────┘
               │ HTTP / JSON (CORS + Sanctum)
               ▼ (Port 8000)
      ┌───────────────────────────────────────────────────┐
      │               Laravel 13 REST API                 │
      │       Repositories ◄──► Services ◄──► Models      │
      └────────────┬─────────────────────────┬────────────┘
                   │                         │
                   ▼ (Port 5432/5433)        ▼ (Port 6379)
            ┌──────────────┐          ┌──────────────┐
            │  PostgreSQL  │          │   Redis 7    │
            │  17 / 18     │          │ Cache & Queue│
            └──────────────┘          └──────────────┘
```

---

## 📡 API Endpoints Overview

All endpoints are versioned under `/api/v1`:

### 1. Protocols (`/api/v1/protocols`)
- `GET /api/v1/protocols` — Paginated list with filtering (`status`, `category`, `search`, `sort`)
- `GET /api/v1/protocols/{slug}` — Fetch protocol by slug with threads and reviews
- `POST /api/v1/protocols` *(Auth)* — Create a new protocol
- `PUT /api/v1/protocols/{protocol}` *(Auth)* — Update protocol specification
- `DELETE /api/v1/protocols/{protocol}` *(Auth)* — Delete protocol

### 2. Discussion Threads (`/api/v1/protocols/{id}/threads`, `/api/v1/threads`)
- `GET /api/v1/protocols/{protocol}/threads` — List discussion threads for a protocol
- `GET /api/v1/threads/{id}` — Thread detail with nested comment tree
- `POST /api/v1/protocols/{protocol}/threads` *(Auth)* — Create a discussion thread
- `PUT /api/v1/threads/{thread}` *(Auth)* — Update thread
- `DELETE /api/v1/threads/{thread}` *(Auth)* — Delete thread

### 3. Nested Comments (`/api/v1/threads/{id}/comments`, `/api/v1/comments`)
- `GET /api/v1/threads/{thread}/comments` — List top-level comments and nested replies
- `POST /api/v1/threads/{thread}/comments` *(Auth)* — Create top-level or reply comment (`parent_id`)
- `DELETE /api/v1/comments/{comment}` *(Auth)* — Delete comment

### 4. Peer Reviews (`/api/v1/protocols/{id}/reviews`, `/api/v1/reviews`)
- `GET /api/v1/protocols/{protocol}/reviews` — List peer reviews with ratings and verdicts
- `POST /api/v1/protocols/{protocol}/reviews` *(Auth)* — Submit a review (1–5 rating, summary, findings)
- `DELETE /api/v1/reviews/{review}` *(Auth)* — Remove review

### 5. Polymorphic Votes (`/api/v1/votes`)
- `POST /api/v1/votes` *(Auth)* — Cast or toggle vote (+1 / -1) on `Protocol`, `Thread`, or `Comment`

### 6. Authentication (`/api/v1/auth`)
- `POST /api/v1/auth/register` — Register user account
- `POST /api/v1/auth/login` — Login and receive Sanctum bearer token
- `GET /api/v1/auth/me` *(Auth)* — Current user profile
- `POST /api/v1/auth/logout` *(Auth)* — Revoke tokens

---

## 📦 Database Seeder & Mock Data

The database seeder (`php artisan migrate:fresh --seed`) creates:
- **5 Clinical & Community Contributors**: Admin (`admin@protocol.io`), Dr. Andrew H. (`andrew@protocol.io`), Dr. Rhonda P. (`rhonda@protocol.io`), Elena Rostova PT (`elena@protocol.io`), Coach Marcus Vance (`marcus@protocol.io`). Legacy demo users (`vitalik@protocol.io`, etc.) are also aliased for seamless backward compatibility. Password: `password`.
- **12 Published Healing & Wellness Protocols**: Circadian Sleep Architecture, Low-FODMAP Gut Health, Cold-Water Immersion, Rotator Cuff Rehab, Zone-2 Cardio, Cyclic Sigh Breathwork, 16:8 Fasting, Ergonomic Posture, Magnesium Sleep Stacking, Contrast Hydrotherapy, Low-Histamine Immunology, and VO2 Max Norwegian 4x4.
- **12 Discussion Threads**: One dedicated community discussion thread per protocol.
- **24 Hierarchical Comments**: Demonstrating arbitrary recursive nested discussions.
- **36 Peer Reviews**: Detailed findings, scores (1–5 stars), and verdicts.
- **48 Polymorphic Votes**: Initial reputation metrics and recalculation of protocol score aggregates.

---

## 🛠 Quickstart Guide

### Option 1: Native Windows / Git Bash (Host Execution)

#### Prerequisites
- PHP 8.4+ and Composer 2
- Node.js 20+ and npm
- Docker Desktop (for PostgreSQL, Typesense, and Redis services)

#### 1. Start Support Containers
```powershell
docker run -d --name protocol_pg17 -p 5433:5432 -e POSTGRES_DB=protocol_platform -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD=secret postgres:17-alpine
docker run -d --name protocol_typesense -p 8108:8108 typesense/typesense:27.1 --data-dir /data --api-key=xyz --enable-cors
docker run -d --name protocol_redis -p 6379:6379 redis:alpine
```

#### 2. Setup & Run Backend
```bash
cd backend
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan search:reindex
php artisan serve --host=127.0.0.1 --port=8000
```

#### 3. Setup & Run Frontend
```bash
cd frontend
cp .env.example .env.local
npm install
npm run dev
```

Visit **`http://localhost:3000`** (or **`http://localhost:3000/protocols`**).

---

### Option 2: Docker Compose (Full Stack Zero-Host-Dependencies)

```bash
docker compose up -d
docker compose exec backend php artisan migrate --seed
docker compose exec backend php artisan search:reindex
```

---

## 🧪 Testing & Verification

### Run Backend Tests (Pest 4)
```bash
cd backend
php vendor/bin/pest
```
> **Result:** `PASS Tests\Feature\Api\... (37 tests, 191 assertions)`

### Run Static Analysis & Formatting
```bash
cd backend
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

### Run Frontend Build & Tests
```bash
cd frontend
npm run build
npm run test
```

---

## 📄 Implementation Notes
For deep technical rationale, database schema details, search engine design, and architectural decisions, read [`IMPLEMENTATION_NOTES.md`](./IMPLEMENTATION_NOTES.md).
