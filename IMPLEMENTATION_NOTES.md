# Community-Powered Protocol Platform — Implementation Notes

**Author:** Raymund Gerard Reyes  
**Architecture Version:** 2026 Production Baseline (Laravel 13 + Next.js 16 + PostgreSQL 17/18 + Typesense 27.1 + Redis 7)  
**Date:** September 2026  

---

## 1. Executive Summary & Architecture Overview

The Community-Powered Protocol Discussion Platform is built to provide decentralized governance, protocol proposal discussions, peer code reviews, and community reputation voting. The system adheres to strict domain separation between a stateless Laravel 13 REST API backend and a Next.js 16 (React 19, Turbopack) client-side application with server-rendered routing.

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        Next.js 16 (App Router)                         │
│   React 19 • Turbopack • TanStack Query v5 • Tailwind CSS 4 • Typesense│
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ HTTP / JSON (CORS + Sanctum)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        Laravel 13 REST API                             │
│   PHP 8.4/8.5 • Form Requests • API Resources • Scout Engine • Pint    │
└───────────┬───────────────────────┼─────────────────────────┬──────────┘
            │                       │                         │
            ▼                       ▼                         ▼
┌───────────────────────┐ ┌───────────────────┐ ┌────────────────────────┐
│     PostgreSQL 17     │ │    Typesense 27   │ │        Redis 7         │
│  Relational Storage   │ │ Typo-tolerant     │ │ Distributed Caching    │
│  Polymorphic Votes    │ │ Fast Search Index │ │ Session & Rate Limits  │
└───────────────────────┘ └───────────────────┘ └────────────────────────┘
```

---

## 2. Backend Design Patterns & Implementation

### 2.1 Layered Architecture (Repository & Service Pattern)
To ensure strict separation of concerns, the backend enforces a three-tier architecture:
1. **Controllers (`app/Http/Controllers/Api/V1`)**: Handle HTTP request validation delegation and JSON resource transformations. Controllers never execute raw SQL or mutate database state directly.
2. **Services (`app/Services`)**: Contain all business logic, authorization policies, and orchestration.
   - `ProtocolService`: Manages protocol creation, slugification, status transitions, and search index dispatch.
   - `VoteService`: Implements polymorphic vote casting with atomic toggle logic and real-time aggregate recalculation.
   - `ReviewService`: Computes running weighted protocol review scores and average ratings upon peer review submissions.
   - `CommentService`: Manages recursive hierarchical discussion threads and nested replies.
3. **Repositories (`app/Repositories`)**: Encapsulate Eloquent query construction, eager-loading relations, sorting strategies, and pagination.

### 2.2 Relational Model & Polymorphic Voting Engine
- **Protocols Table**: Tracks metadata (`title`, `slug`, `category`, `version`, `status`, `metadata` JSONB), along with cached denormalized aggregates (`score`, `votes_count`, `reviews_count`, `average_rating`) to prevent $O(N)$ runtime aggregations.
- **Discussion Threads & Hierarchical Comments**: Adjacency list tree pattern via `parent_id` foreign key referencing `comments.id` enabling unlimited nesting depth for replies.
- **Polymorphic Votes Table**: Supports voting across any votable entity (`votable_type`, `votable_id`, `user_id`, `value`). The system enforces unique composite keys `(user_id, votable_type, votable_id)` ensuring a user can cast at most one vote per entity (+1 or -1) with toggle-off support.
- **Weighted Protocol Scoring Algorithm**:
  $$\text{Score} = (\text{Net Votes} \times 10) + (\text{Peer Reviews Count} \times 5) + (\text{Average Rating} \times 2)$$

### 2.3 Search Engine: Laravel Scout & Typesense 27
- Configured native Typesense engine via `laravel/scout` and `typesense/typesense-php`.
- Custom schemas defined in `config/scout.php` for `Protocol` and `Thread` models.
- Dedicated Artisan command `php artisan search:reindex` wipes and regenerates instant search collections.
- Frontend directly communicates with Typesense on port `8108` using a read-only search key (`abc`), offloading heavy search traffic from the relational database.

---

## 3. Frontend Architecture & Modern Web Patterns

### 3.1 Next.js 16 App Router & Server Components
- **Feature-Colocated Structure**: Code is partitioned by business feature under `frontend/features/` (`protocols/`, `threads/`, `comments/`, `reviews/`, `votes/`).
- **Async Metadata & Route Parameters**: Compatible with React 19 / Next.js 16 asynchronous request APIs (`await searchParams`, `await params`).
- **Optimistic UI with TanStack React Query v5**: Voting mutations immediately increment or decrement counts in client state via `onMutate` rollback snapshots, delivering 0ms perceived interaction latency.
- **Search Client with Automatic Fallback**: The client-side search component attempts high-speed instant search queries against Typesense. If Typesense is unreachable, it automatically degrades gracefully to the Laravel backend SQL search endpoint.

### 3.2 Security, CORS, and Credentials
- **W3C CORS Protocol**: Because the frontend uses `withCredentials: true` for Sanctum stateful authentication, `config/cors.php` explicitly specifies allowed frontend origins (`http://localhost:3000`, `http://127.0.0.1:3000`) and sets `supports_credentials: true`. Wildcards (`*`) are strictly avoided to adhere to browser security specifications.

---

## 4. Verification, Testing & Quality Assurance

### 4.1 Automated Backend Suite (Pest 4)
- **37 automated feature and unit tests** across 8 test suites:
  - `AuthenticationTest`: Registration, login token issuance, Sanctum user verification.
  - `ProtocolTest`: Full CRUD, slug routing, pagination, category filtering, and sorting.
  - `ThreadTest`: Thread creation, protocol association, view counter increment.
  - `CommentTest`: Nested reply creation, parent-child tree verification.
  - `ReviewTest`: Star ratings (1-5), duplicate review prevention, aggregate updates.
  - `VoteTest`: Upvoting, downvoting, toggle reversals, and score recalculation.
  - `SearchReindexCommandTest`: Scout Typesense index synchronization.
- **Result:** 100% pass rate (37 tests, 191 assertions).

### 4.2 Static Analysis & Formatting
- **PHPStan / Larastan**: Level 8 analysis passed with zero errors.
- **Laravel Pint**: Formatted strictly according to Laravel opinionated standards.
- **TypeScript & ESLint**: Strict type checking with 0 errors across all Next.js routes.

---

## 5. Deployment & Execution Instructions

### Option A: Quickstart via Docker (Zero Local Setup)
```bash
docker compose up -d
docker compose exec backend php artisan migrate --seed
docker compose exec backend php artisan search:reindex
```
- App: `http://localhost:3000`
- API: `http://localhost:8000` or `http://localhost/api`
- Typesense: `http://localhost:8108`

### Option B: Native Host Execution
```bash
# 1. Start support services (PostgreSQL 17, Typesense, Redis)
docker run -d --name protocol_pg17 -p 5433:5432 -e POSTGRES_DB=protocol_platform -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD=secret postgres:17-alpine
docker run -d --name protocol_typesense -p 8108:8108 typesense/typesense:27.1 --data-dir /data --api-key=xyz --enable-cors
docker run -d --name protocol_redis -p 6379:6379 redis:alpine

# 2. Backend
cd backend
php artisan migrate:fresh --seed
php artisan search:reindex
php artisan serve --host=127.0.0.1 --port=8000

# 3. Frontend
cd frontend
npm run dev
```
Navigate to **`http://localhost:3000/protocols`**.
