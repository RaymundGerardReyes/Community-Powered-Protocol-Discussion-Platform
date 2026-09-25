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

## 3. Service Dependency Liveness (Redis & Databases)
- When Laravel's `.env` specifies `SESSION_DRIVER=redis` or `CACHE_STORE=redis`, an offline Redis port causes synchronous TCP connection timeouts (5–10s latency) on every incoming HTTP request.
- Ensure the Redis container (`protocol_redis` on port 6379) is active before serving API requests.

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

## 7. Dual-Mode Environment Portability (Zero-Docker Fallback)
- The project supports two execution modes:
  1. **Containerized Mode** (Docker Desktop running): Uses `protocol_pg17` (port 5433), `protocol_redis` (port 6379), and `protocol_typesense` (port 8108).
  2. **Standalone Host Mode** (No Docker required): Uses native SQLite (`database/database.sqlite`), `CACHE_STORE=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, and `SCOUT_DRIVER=null`.
- The repository's `database/database.sqlite` is already seeded with the required full dataset (12 protocols, 12 threads, 24 comments, 36 reviews, 48 votes).
- Never overwrite a functioning standalone `.env` with container port configurations (`5433`, `6379`) unless verifying that the Docker daemon and containers are actually active.
- When Docker is stopped, automatically fall back to or recommend the zero-dependency SQLite configuration to ensure immediate, uninterrupted local execution.
