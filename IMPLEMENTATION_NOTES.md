# Community-Powered Protocol Platform: Implementation Notes & Technical Architecture

## 1. Executive Summary & Objective

This platform organizes, filters, and surfaces structured knowledge (**Protocols**) and community discussions (**Threads**) focused on evidence-based **healing, wellness, and instructional health practices** (e.g., Circadian Optimization, Cold-Water Immersion, Gut Restoration, Zone 2 Cardio, and Ergonomics). It simulates Reddit/forum-style engagement through nested comment trees, polymorphic reputation voting (+1 / -1), and expert peer reviews with 1–5 star ratings.

The architecture marries a high-performance **Laravel 11 REST API** transactional backend with a **Typesense 27** search sidecar and a modern **Next.js 16 (React 19)** frontend with Tailwind CSS v4.

---

## 2. System Architecture & Topology

```text
                     NEXT.JS 16 FRONTEND (Port 3000)
               React Server Components (RSC) & TanStack Query
                                    │
               ┌────────────────────┴────────────────────┐
               ▼                                         ▼
   TYPESENSE SEARCH SIDECAR (Port 8108/443)      LARAVEL 11 REST API (Port 8000)
    Direct Read Discovery & Typo Search            Transactional Mutations & Auth
               │                                         │
               │                                         ▼
               │                              RELATIONAL STORAGE (Port 5433)
               └────── Sync on CRUD ◄──────── PostgreSQL 17 / SQLite Host Mode
```

### Relational Database vs. Search Sidecar Boundary
1. **Transactional Integrity (RDBMS Single Source of Truth):**
   - User authentication (hashed bcrypt credentials, Sanctum tokens).
   - Foreign key integrity (`onDelete('cascade')`, `parent_id` comment trees).
   - Compound unique constraints preventing duplicate votes (`[user_id, votable_type, votable_id]`) and duplicate reviews (`[protocol_id, user_id]`).
2. **Search Engine Read Sidecar (Typesense):**
   - Read catalog queries (`/api/v1/protocols`) and protocol discussions (`/api/v1/protocols/{id}/threads`) hydrate directly from Typesense collections with **zero database SQL queries**.
   - Sub-15ms prefix search, fuzzy matching, and multi-facet filtering (by category, tags, status, votes, reviews count, and ratings).
   - On creation, update, or deletion, Eloquent models automatically synchronize their indexable state to Typesense via Laravel Scout.

---

## 3. Core Domain Models & Invariants

| Entity | Attributes & Canonical Fields | Relationships & Rules |
| :--- | :--- | :--- |
| **Protocol** | `title`, `description` / `content`, `category`, `tags`, `version`, `status`, `votes_count`, `average_rating` / `rating` | Has many Threads, Reviews; Morph-many Votes. Authored by User. |
| **Thread** | `title`, `content` / `body`, `slug`, `views_count`, `replies_count`, `votes_count` | Belongs to Protocol & User; Has many Comments; Morph-many Votes. |
| **Comment** | `content` / `body`, `parent_id` (nested replies), `votes_count` | Belongs to Thread, User, and optional parent Comment. Unbounded recursion assembled in $O(N)$ time in memory. |
| **Review** | `rating` (1–5), `summary`, `feedback`, `verdict` (`approved`, `changes_requested`, `rejected`), `findings` | Belongs to Protocol & User. Author cannot review their own protocol. Unique per user-protocol pair. |
| **Vote** | `value` (+1 / -1), `votable_type`, `votable_id` | Polymorphic; toggleable (clicking same vote removes it; opposite updates it). One vote per user per entity. |

---

## 4. RESTful API Contract & Field Parity

All endpoints reside under `/api/v1`. To guarantee 100% contract parity across different consumers, API Resources return both canonical specification fields and backwards-compatible aliases:

* **Protocols (`/api/v1/protocols`)**:
  - `GET /api/v1/protocols` — Filterable by `search` (title query), `category`, `status`, and sorted by `created_at` (Most Recent), `reviews_count` (Most Reviewed), or `votes_count` (Most Upvoted / Highest Rated).
  - `GET /api/v1/protocols/{slug}` — Resolves protocol by slug with embedded threads and peer reviews.
  - `POST /api/v1/protocols` *(Auth)* — Accepts `title`, `content` or `description`, `category`, and `tags`.
* **Threads (`/api/v1/protocols/{id}/threads`)**:
  - `GET /api/v1/protocols/{protocol}/threads` — Lists pinned and recent threads for a protocol.
  - `POST /api/v1/protocols/{protocol}/threads` *(Auth)* — Accepts `title`, `body` or `content`.
  - `GET /api/v1/threads/{id}` — Thread detail view with nested comment tree.
* **Comments (`/api/v1/threads/{id}/comments`)**:
  - `GET /api/v1/threads/{thread}/comments` — Returns hierarchical comment tree.
  - `POST /api/v1/threads/{thread}/comments` *(Auth)* — Creates root comment or nested reply via `parent_id`.
* **Reviews (`/api/v1/protocols/{id}/reviews`)**:
  - `POST /api/v1/protocols/{protocol}/reviews` *(Auth)* — Submits rating (1–5) and optional `feedback`.
* **Polymorphic Votes (`/api/v1/votes`)**:
  - `POST /api/v1/votes` *(Auth)* — Casts or toggles votes for `protocol`, `thread`, or `comment`.

---

## 5. Key Engineering & Design Decisions

### A. Pure Typesense Document Hydration (Zero-SQL Read Path)
Read catalog requests hydrate Eloquent models directly from Typesense document hits without performing downstream SQL queries (`whereIn('id', $ids)`). Response headers confirm:
```http
X-Search-Driver: typesense
X-Data-Source: typesense
X-Database-Connection: none (typesense-decoupled)
X-Database-Target: typesense-cloud
```

### B. Fail-Loud Search Reliability
If Typesense credentials are intentionally omitted or unreachable in strict search mode, the application halts immediately with **HTTP 503 Service Unavailable** (`< 30ms` latency), eliminating silent fallback loops that previously returned outdated mock rows from local SQLite storage.

### C. Client-Side Interactive Ergonomics
- **Expandable Submission Cards:** Thread creation and Review submission are rendered as expandable, accessible cards above their respective sections on the protocol detail page, reducing modal friction.
- **Optimistic TanStack Query Mutations:** Comment creation and vote changes update the UI at $0\text{ ms}$ before network acknowledgment, rolling back automatically on error.
- **Client Auth Deduplication:** Consecutive in-flight calls to `/api/v1/auth/me` are memoized, avoiding duplicate network waterfalls during React 19 component mounting.

---

## 6. Verification & Automated Test Coverage

The platform is fortified with end-to-end automated testing spanning unit, integration, and API feature suites:

* **Backend Test Suite (`php artisan test`):**
  - **107 Tests / 598 Assertions (100% Pass Rate)** across 11 test suites.
  - Suites include `ProtocolTest`, `ThreadCommentApiTest`, `ReviewApiTest`, `VoteApiTest`, `AuthApiTest`, `RoutingPathTest`, and `TypesenseCollectionSchemaTest`.
* **Frontend Test Suite (`npx vitest run`):**
  - **27 Test Files / 93 Tests (100% Pass Rate)** spanning UI components, query cache hooks, API services, and navigation.

---

## 7. Setup & Quickstart

```bash
# 1. Backend Setup
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan search:reindex
php artisan serve --host=127.0.0.1 --port=8000

# 2. Frontend Setup
cd ../frontend
npm install
cp .env.example .env.local
npm run dev
```
Accessible at `http://localhost:3000` (Next.js) and `http://127.0.0.1:8000` (Laravel API).
