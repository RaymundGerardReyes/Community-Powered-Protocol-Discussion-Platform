# Workspace Development Rules & Architectural Standards

## 1. W3C CORS & Credentials Invariant
- When frontend API clients (e.g., Axios in Next.js) configure `withCredentials: true`, browsers strictly reject wildcard origins (`*`) under the W3C CORS specification.
- Laravel's `config/cors.php` must explicitly define:
  ```php
  'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000', env('FRONTEND_URL')],
  'supports_credentials' => true,
  ```
- Never leave `allowed_origins: ['*']` or `supports_credentials: false` when credentials are active.

---

## 2. Git Bash (MINGW64) Toolchain on Windows
- Newly registered Windows User Environment Variables (`User PATH`) are not automatically loaded into existing Git Bash sessions.
- Always configure `/c/tools/php` in `~/.bashrc`:
  ```bash
  export PATH="/c/tools/php:$PATH"
  ```
- Unix emulation shells do not execute Windows `.bat` files when invoked directly. Provide a POSIX wrapper script (`/bin/sh`) for tools like `composer` alongside batch files.
- Command-line flags must not have spaces after `--` (e.g., use `--host=127.0.0.1 --port=8000`, not `-- host=...`).

---

## 3. Local Environment Simplicity & Service Dependencies
- **No Redis Requirement for Local Development:**
  - Redis is only meant for high-scale production clustering. In local development environments, Redis is unnecessary and adds fragile container/TCP timeout overhead (`Predis\TimeoutException`).
  - Configure `backend/.env` with Laravel's built-in local drivers:
    ```env
    CACHE_STORE=file
    QUEUE_CONNECTION=sync
    SESSION_DRIVER=file
    ```
  - This eliminates background Redis connection timeouts, unburdens developer machines, and allows all tests, caching, and sessions to run instantly with zero dependencies.
- **Database Dependency:** Ensure the PostgreSQL 17 container (`protocol_pg17` on port 5433) is active for database operations.

---

## 4. Frontend Client/Server Boundary Discipline
- Keep page components as React Server Components (RSC) to maximize SEO and initial server rendering speed.
- In Next.js 16 with React 19, `params` and `searchParams` in server page components are asynchronous promises (`await searchParams`, `await params`).
- For voting engines or reaction counts, implement optimistic updates in `onMutate` with rollback snapshots in `onError`.
- **Never attach event handlers (`onMouseOver`, `onMouseOut`, `onClick`, etc.) to JSX elements rendered in RSC files.** Next.js will throw a prerender error at build time. Use Tailwind hover utilities (`hover:bg-indigo-50`) or move the element into a `'use client'` component.

---

## 5. Tailwind CSS v4 — Zero-Config Pattern
- This project uses **Tailwind CSS v4** (`tailwindcss ^4.x`, `@tailwindcss/postcss ^4`).
- **Do NOT create `tailwind.config.ts` or `tailwind.config.js`** — those are v3 artifacts and are ignored in v4.
- Configuration is done entirely via `app/globals.css` using `@import "tailwindcss"` and `@theme inline { ... }`.
- PostCSS plugin is `"@tailwindcss/postcss": {}` in `postcss.config.mjs`.
- Custom design tokens go in `:root { }` as CSS custom properties (e.g., `--brand`, `--surface-card`).

---

## 6. CSS Design Token System & Dark Color Prevention
- **`app/globals.css` is the single source of truth** for all design tokens and shared utility classes in this project.
- Never remove `@media (prefers-color-scheme: dark)` — keep it absent from `globals.css` to enforce the light-only theme. Adding it back causes OS-level dark mode to override light component styles, producing mixed dark/light UI sections.
- All component files must reference colors via CSS custom properties (e.g., `var(--text-primary)`, `var(--surface-card)`, `var(--brand)`) rather than hardcoded RGBA values or Tailwind's `bg-slate-*` / `bg-gray-*` dark shades.
- **Hardcoded dark values that caused the bug:**
  - `rgba(30, 41, 59, 0.4)` — was the comment card background (dark slate)
  - `rgba(15, 23, 42, 0.7)` — was the textarea background (near-black)
  - These must never be re-introduced; use `var(--surface-card)` instead.
- Utility classes that **must** be defined in `globals.css` (not assumed from Tailwind):
  `card-flat`, `btn`, `btn-primary`, `btn-secondary`, `btn-ghost`, `btn-sm/md/lg`,
  `badge`, `badge-published`, `badge-draft`, `badge-deprecated`, `badge-info`, `badge-neutral`,
  `input`, `section-title`, `star-filled`, `star-empty`, `comment-indent`, `divider`.

---

## 7. Dual-Mode Environment Architecture & Zero-Docker Fallback
- The platform supports two interchangeable operational modes:
  1. **Containerized Mode (Docker active):**
     Uses PostgreSQL 17 (`protocol_pg17` on port 5433) and Typesense (`protocol_typesense` on port 8108).
     ```env
     DB_CONNECTION=pgsql
     DB_HOST=127.0.0.1
     DB_PORT=5433
     DB_DATABASE=protocol_platform
     DB_USERNAME=postgres
     DB_PASSWORD=secret
     SCOUT_DRIVER=typesense
     ```
  2. **Standalone Host Mode (Docker offline or WSL unresponsive):**
     Uses native pre-seeded SQLite (`database/database.sqlite`), eliminating container dependencies and TCP timeouts.
     ```env
     DB_CONNECTION=sqlite
     DB_DATABASE=database/database.sqlite
     CACHE_STORE=file
     QUEUE_CONNECTION=sync
     SESSION_DRIVER=file
     SCOUT_DRIVER=null
     ```
- **Automated Provisioning (`scripts/provision-db.ps1`):**
  Execute `.\scripts\provision-db.ps1 -Mode Standalone` to instantly run without Docker, or `.\scripts\provision-db.ps1 -Mode Docker` when Docker Desktop is active.
- **Graceful Frontend Search Degradation:**
  Frontend clients must silently degrade to Laravel `/api/v1/protocols?search=` whenever the Typesense daemon is unreachable on port 8108.


---

## 8. RESTful Resource Invariants & Schema Parity
- **Nested Resource Route Structure:**
  - Comments must always be posted through their parent resource: `POST /api/v1/threads/{thread}/comments`.
  - Never call root-level `POST /api/v1/comments`.
- **Field Name Parity (`content` vs `body`):**
  - Database schema and Eloquent resources use `content` for discussions and comments.
  - Frontend interfaces and API payloads must send `content: string` (optionally retaining `body` as an alias for backwards compatibility).
- **Polymorphic Votable Type Shorthands:**
  - When dispatching votes via `POST /api/v1/votes`, always send canonical shorthand identifiers: `'protocol'`, `'thread'`, or `'comment'`.
  - Avoid raw PHP FQCN string literals (e.g. `'App\Models\Thread'`) in client code, which suffer from backslash escape stripping in JavaScript.

---

## 9. Semantic Versioning & Release Pipeline Orchestration
- **Pipeline Invariant:**
  - Every batch of bug updates, stability fixes, or new features MUST culminate in an explicit Semantic Version bump (`MAJOR.MINOR.PATCH`) and annotated git tag.
  - Never leave significant bug fixes or features untagged in `HEAD`.
- **SemVer Increment Guidelines:**
  - **PATCH (`vX.Y.Z+1`):** Backward-compatible bug fixes, UI regressions, typo/validation corrections, and environment tuning (e.g., Redis local dev decoupling).
  - **MINOR (`vX.Y+1.0`):** Backward-compatible feature additions, new test suites, or major non-breaking refactors.
  - **MAJOR (`vX+1.0.0`):** Incompatible API changes, breaking database migrations, or breaking frontend contract rewrites.
- **Release Orchestration Step Sequence:**
  1. **Run & Verify All Tests:** Confirm 100% pass across backend (`php artisan test`) and frontend (`npx vitest run`).
  2. **Synchronize Package Manifests:** Update `"version"` in `frontend/package.json` to match the target semver.
  3. **Conventional Commit:** Stage and commit changes with descriptive type prefixes (`fix:`, `feat:`, `test:`, `chore:`).
  4. **Mint Annotated Git Tag:** Execute `git tag -a vX.Y.Z -m "Release vX.Y.Z: <Summary of changes>"`.
  5. **Verify Tag:** Confirm creation via `git tag -l -n3 "vX.Y.Z"`.

---

## 10. Frontend 3-Tier Testing Directory Architecture Standard
- **Directory Hierarchy Parity:**
  - All frontend tests MUST reside under the dedicated `frontend/tests/` directory structured into 3 distinct tiers, mirroring the backend testing architecture:
    1. `frontend/tests/unit/`: UI design system components (`components/`), utility functions (`lib/`), and raw API services (`api/`).
    2. `frontend/tests/integration/`: State providers (`auth/`), custom hooks with React Query cache (`votes/`), composite filter bars & paginated lists (`protocols/`), and recursive discussion threads (`comments/`).
    3. `frontend/tests/e2e/`: Full browser user journeys using Playwright (`specs`, `mock-data`, and mock servers).
- **Prohibition of Source Directory Co-location:**
  - Never place `.test.ts`, `.test.tsx`, or `.spec.ts` files inside production source directories (`features/`, `components/`, `lib/`, `app/`). Source directories must remain pure production code.
- **Test Runner Scopes:**
  - **Vitest:** Scans `tests/unit` and `tests/integration`, strictly excluding `tests/e2e`.
  - **Playwright:** Scans `tests/e2e`.

---

## 11. Repository Hygiene & External Agent Artifact Exclusion
- **Prohibition of Third-Party Agent Artifacts:**
  - External agent instruction files (e.g., `AGENTS.md`, `CLAUDE.md`) must never be placed or committed in repository subdirectories (`scripts/`, `frontend/`, `docker/`, `backend/`).
  - The single source of truth for repository behavioral guidelines and architectural standards is `GEMINI.md`.
- **Ignore List Invariant:**
  - `.gitignore` must explicitly ignore `AGENTS.md` and `CLAUDE.md` to prevent accidental re-introduction by external tooling.
- **Remote Hygiene & Git Tracking:**
  - When cleaning legacy agent files, always delete them via `git rm`, synchronize package manifests, commit with `chore:`, tag with semantic versioning, and push all commits and tags to `origin/main` to guarantee remote eradication.

---

## 12. Loopback Latency SLO & Performance Benchmarking Standard
- **Loopback Context & Diagnostic Principle:**
  - Because local loopback network overhead is negligible (< 1 ms), end-to-end API latency directly reflects application stack efficiency (bootstrapping, query execution, business logic, serialization, and connection lifecycle).
  - Any regular local API endpoint taking > 500 ms must be treated as a performance defect and investigated across the stack rather than dismissed as "local dev overhead".
- **Local Service Level Objectives (SLO) for Synchronous CRUD Endpoints:**
  - **P50 (Median):** `< 100 ms`
  - **P95:** `< 250 ms`
  - **P99:** `< 500 ms`
- **6-Tier Development Performance Rubric:**
  | Latency | Classification | Interpretation |
  | :--- | :--- | :--- |
  | `< 50 ms` | **Excellent** | Highly responsive, optimal query and caching |
  | `50–100 ms` | **Very Good** | Strong loopback execution |
  | `100–250 ms` | **Good** | Acceptable / healthy local operation |
  | `250–500 ms` | **Moderate** | Investigate if consistently occurring |
  | `> 500 ms` | **Slow** | Defect; active investigation required |
  | `> 1 s` | **Very Slow** | Critical bottleneck; blocking investigation |
- **Real Execution Profiling Invariant:**
  - Every API response must attach `X-Response-Time` and W3C standard `Server-Timing: app;dur=...` headers via middleware to isolate actual server execution time from TCP socket keep-alive timeouts.
  - Performance compliance must be verifiable via `php artisan benchmark:latency --count=50`.

---

## 13. PHP CLI Server Keep-Alive Artifacts & Client-Side Auth Deduplication
- **Keep-Alive Socket Duration vs Execution Time:**
  - `php artisan serve` measures elapsed wall-clock time between TCP socket `Accepted` and socket `Closing` (`ServeCommand.php`).
  - In HTTP/1.1 with persistent connections (`Connection: keep-alive`), PHP's built-in web server holds the socket open for its **500 ms idle timeout** waiting for subsequent pipelined requests.
  - When a standalone request arrives without an immediate follow-up on the exact same socket, `artisan serve` logs `~ 505ms–515ms` even though actual application execution was `< 10ms`.
  - Always verify actual application latency via `X-Response-Time`, `Server-Timing: app;dur=...`, or `php artisan benchmark:latency`.
- **Frontend Auth Verification Deduplication:**
  - Client-side auth verification (`/api/v1/auth/me`) must be deduplicated across component renders and React 18/19 StrictMode double-invocations using in-flight promise caching or cleanup abort guards.
  - Never allow unthrottled duplicate calls to `/api/v1/auth/me` within the same navigation or render cycle.


