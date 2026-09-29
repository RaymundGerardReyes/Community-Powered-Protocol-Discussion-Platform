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

---

## 14. Relational RDBMS & Storage Topology Standard: PostgreSQL TEXT/TOAST, JSONB & Search Sidecar
- **Database Model vs. Data Length Invariant:**
  - Data length (large strings, technical specifications, discussions, or code blocks) is an internal storage characteristic, NOT a criterion for choosing NoSQL or document databases over relational databases.
  - Never introduce MongoDB or document stores solely because fields like `threads.content` or `comments.content` can grow very long.
- **PostgreSQL TOAST Mechanism:**
  - PostgreSQL automatically manages large column values using TOAST (The Oversized-Attribute Storage Technique), compressing and storing oversized attributes out-of-line in separate physical storage chunks without impacting heap page scans.
- **Relational Integrity Primacy:**
  - The core domain model relies strictly on relational guarantees:
    - User -> Protocol -> Thread -> Comment -> Nested Replies (Adjacency List with `parent_id` FK).
    - Foreign key constraints with `cascadeOnDelete()` and `nullOnDelete()`.
    - Unique compound constraints (e.g. `[protocol_id, user_id]` on reviews, `[user_id, votable_type, votable_id]` on votes).
- **Document-like Data via JSONB:**
  - For genuinely variable, polymorphic, or semi-structured data (e.g., `protocols.metadata` containing dynamic audits, tags, and contract addresses), use PostgreSQL `JSONB` rather than introducing a separate document database.
- **Dedicated Search Offloading (Typesense Sidecar):**
  - Full-text fuzzy search, prefix matching, and relevance ranking belong in the Typesense search sidecar. The relational database remains the single source of truth for all writes, updates, and relational queries.
- **Extreme Payload Threshold:**
  - If single posts or artifacts exceed hundreds of kilobytes or megabytes, evaluate PostgreSQL `TEXT` vs. S3/object storage with relational URL pointers, rather than switching the transactional data layer to MongoDB.

---

## 15. Comment Hierarchy, Arbitrary Recursion & Timestamp Precision Standard
- **In-Memory O(N) Tree Assembly over Hardcoded Nesting:**
  - Never hardcode multi-level eager loading (e.g. `with(['replies.replies.replies.user'])`).
  - Retrieve thread comments with their authors in a single query (`$thread->comments()->with('user')->orderBy('created_at')->get()`), group by `parent_id`, assign the `replies` relation in memory in $O(N)$ time, and return the root comments (`whereNull('parent_id')`).
- **Parent Comment Thread Boundary Validation:**
  - All nested comment creations must validate that `parent_id` belongs to the exact target thread:
    ```php
    Rule::exists('comments', 'id')->where('thread_id', $threadId)
    ```
  - This prevents cross-thread parent referencing from corrupting discussion trees.
- **Timestamp Precision Invariant:**
  - Never truncate `created_at` timestamps at the API or resource layer; always emit full ISO-8601 UTC strings (`toISOString()`).
  - Frontend formatters must preserve hours, minutes, and seconds (e.g., `Sep 27, 2026 • 11:14:42 PM`).
- **Unbounded Recursive Tree Rendering:**
  - Frontend comment components must render recursively for all descendant replies, using CSS indentation (`.comment-indent`) for visual hierarchy without capping or hiding replies at deep levels.

---

## 16. Case-Insensitive Taxonomy Filtering & Search Input UI/UX Standard
- **Case-Insensitive Query Invariant:**
  - Database queries filtering by categories, tags, or status must never rely on case-sensitive equality (`where('category', $category)`).
  - Always use case-insensitive comparisons across PostgreSQL and SQLite:
    ```php
    $lower = strtolower($category);
    $compact = str_replace(['-', ' ', '_'], '', $lower);
    $query->where(function ($q) use ($lower, $compact) {
        $q->whereRaw('LOWER(category) = ?', [$lower])
          ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(category, '-', ''), ' ', ''), '_', '')) = ?", [$compact]);
    });
    ```
- **Case-Insensitive Free-Text Search:**
  - Fallback SQL text search must use `LOWER(column) LIKE ?` with lowercase search terms to guarantee case-insensitivity on PostgreSQL:
    ```php
    $term = '%'.mb_strtolower($search).'%';
    $query->where(function ($q) use ($term) {
        $q->whereRaw('LOWER(title) LIKE ?', [$term])
          ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
    });
    ```
- **Search Input Layout & Icon Clearance:**
  - Inputs with absolute left icons must guarantee explicit padding (`padding-left: 2.5rem`) to prevent CSS cascade resets from colliding text with the icon.
  - Native WebKit search decorations must be suppressed (`-webkit-appearance: none; display: none`) to eliminate user-agent icon collisions, and an explicit interactive clear button (`✕`) must be provided.

---

## 17. RSC / Client State Synchronization & Live Dynamic Tree Mutations
- **Client Subscription via Hybrid Initial Data Pattern:**
  - When an async Server Component (RSC) provides initial data, the receiving interactive section must bind to a TanStack React Query hook (e.g. `useComments(threadId, initialComments)`) utilizing `initialData: initialComments`.
  - This guarantees instant SSR/RSC rendering with zero loading flicker while enabling real-time client mutations to trigger immediate re-renders.
- **Optimistic Recursive Cache Insertion:**
  - Upon successful creation of a comment or reply, `CommentForm` must immediately update the active query cache synchronously using an immutable tree helper (`insertCommentIntoTree`):
    - Top-level comments append to the root array.
    - Nested replies find the matching `parent_id` at arbitrary recursion depths and append to `parent.replies`.
    - Duplicate IDs are deduplicated.
  - The newly submitted comment/reply appears in the DOM at $0\text{ ms}$ latency before any background network refetch completes.
- **Dual Revalidation (Query Cache + RSC Router Refresh):**
  - Following the synchronous cache insertion, the client must trigger both:
    1. `queryClient.invalidateQueries({ queryKey: ['comments', threadId] })` for backend truth synchronization.
    2. `router.refresh()` to update server-rendered counters (`thread.comments_count`) and Server Component caches.
- **Auto-Expansion on Reply Invariant:**
  - When submitting a reply to a collapsed parent comment, the parent comment must automatically expand (`setCollapsed(false)`), guaranteeing immediate visibility of the newly posted reply.

---

## 18. Accessible Full-Card Clickable Surface Standard (Stretched-Link Pattern)
- **Visual Affordance vs. Hit Target Parity (Fitts's Law):**
  - If a card element presents hover animations, elevation shifts (`hover:-translate-y-0.5`), or `cursor: pointer`, the user perceives the entire card container as the hit target.
  - Making only the `<h3>` title text interactive violates user expectations and creates dead click zones across card padding, metadata, and descriptions.
- **Stretched-Link Architecture over Whole-Card Wrapping:**
  - **Never** wrap the entire semantic `<article>` in an `<a>` or `<Link>` tag. Wrapping large composite cards in links pollutes the accessibility tree by causing screen readers to announce paragraphs, badges, and timestamps as a single run-on link name. Furthermore, nesting interactive elements (like vote buttons) inside an anchor tag is invalid HTML5.
  - **The Standard Pattern:** Keep `<article>` as the outer semantic container with `relative`. Anchor the primary navigation to the heading's `<Link>` and expand its click area to the entire container using a pseudo-element:
    ```tsx
    <Link href={href} className="focus:outline-none after:absolute after:inset-0 after:z-0">
      <h3 className="group-hover:underline">{title}</h3>
    </Link>
    ```
- **Interactive Sub-Control Isolation (Z-Index Stacking):**
  - Secondary interactive elements inside the card (such as `VoteButton`, category tag links, or author profiles) must be placed on an elevated stacking context using `relative z-10`.
  - Non-interactive metadata, descriptions, and badges should be marked `pointer-events-none` so mouse events pass transparently through to the underlying stretched link.
- **Accessible Name Integrity:**
  - Do not use redundant `aria-label` overrides on the stretched link that mask the natural heading text, ensuring full compatibility with automated tests and screen reader navigation.

---

## 19. Typesense Schema Alignment & Dual-Path Scout/SQL Fallback Routing Standard
- **Schema Parity & Nested Fields Invariant:**
  - Typesense collection schemas must explicitly declare sorting and faceting fields required by application filters (`votes`, `reviews_count`, `average_rating`, `status`, `category`, `tags`).
  - Collections must set `"enable_nested_fields": true` to support polymorphic and semi-structured metadata attributes (e.g. `metadata.tags`, audit trails).
  - Include the wildcard `['name' => '.*', 'type' => 'auto']` to enable schema auto-detection for emergent fields.
- **Repository Dual-Path Search Routing:**
  - When `config('scout.driver') === 'typesense'` and a search term is provided, the repository must query Typesense via `Model::search($term)->keys()` and filter Eloquent queries by matching IDs (`whereIn('id', $ids)`).
  - The repository search execution MUST be wrapped in a resilient exception guard (`try ... catch (\Throwable)`). If the Typesense daemon or cloud cluster experiences a timeout or network interruption, the query must immediately fall back to SQL case-insensitive search (`LOWER(...) LIKE ?`) without bubbling a 500 error to the client.
- **Client Direct Query vs. API Fallback:**
  - High-frequency search-as-you-type in the frontend must query Typesense Cloud directly using the Search-Only API key for sub-15ms response times.
  - If client-side search encounters network failures, it must seamlessly degrade to the backend endpoint (`/api/v1/protocols?search=`).
- **Default Sorting Field & Canonical Field Alias Parity:**
  - Whenever a collection schema declares a `default_sorting_field` (such as `vote_score`, `votes_count`, or `votes`) or mandatory schema fields created externally, the model's `toSearchableArray()` MUST provide all canonical field aliases:
    - Vote and ranking metrics: `vote_score`, `votes_count`, `votes`, `score`.
    - Discussion activity metrics: `comment_count`, `comments_count`, `replies_count`, `views_count`.
    - Author identity: `author`, `author_name`, `author_id`, `user_id`.
  - In `typesenseCollectionSchema()`, all secondary or emergent metric fields must declare `'optional' => true` to guarantee schema compatibility across different cluster creation methods.
- **Cloud Credential ASCII Sanitization Invariant:**
  - Hostnames, node URLs, and API keys retrieved from `.env` must be sanitized against non-printable Unicode characters, non-breaking spaces (`\xC2\xA0`), zero-width spaces, and trailing quotes/slashes before being passed to HTTP clients or DNS resolvers (`preg_replace('/[^\x21-\x7E]/', '', ...)`).


