# Community-Powered Protocol & Discussion Platform

## 🚀 2026 Production-Grade Full-Stack Baseline

| Layer                  | Version                | Purpose / Rationale                                   |
| ---------------------- | ---------------------- | ----------------------------------------------------- |
| **OS runtime**         | **Node.js 24 LTS**     | Stable LTS baseline (Current is Node 26)              |
| **Package manager**    | **npm 11.x**           | Bundled with Node 24                                  |
| **Frontend framework** | **Next.js 16.x**       | Active LTS line                                       |
| **UI library**         | **React 19.3.x**       | Stable View Transitions & Fragment Refs               |
| **Language**           | **TypeScript 5.x**     | Strict type safety                                    |
| **CSS framework**      | **Tailwind CSS 4.3.x** | Current v4 line (scrollbars, container-size, zoom-*)  |
| **Backend**            | **Laravel 13.x**       | Current Laravel major                                 |
| **PHP**                | **PHP 8.5.x**          | Current supported stable line for Laravel 13          |
| **Database**           | **PostgreSQL 18.x**    | Modern major version with high-throughput indexing    |
| **API**                | **REST / JSON API**    | Clean Next.js ↔ Laravel separation                    |
| **Authentication**     | **Laravel Sanctum**    | SPA/API token & session authentication                |
| **Caching/queues**     | **Redis**              | High-performance distributed caching & queues         |
| **Web server**         | **Nginx**              | Production reverse proxy                              |
| **Containers**         | **Docker + Compose**   | Reproducible development & deployment                 |
| **Testing (Backend)**  | **Pest 4 / PHPUnit 12**| Expressive backend testing                            |
| **Testing (Frontend)** | **Vitest + Playwright**| Fast component testing + browser E2E                  |
| **Code quality**       | **ESLint + Prettier**  | Frontend formatting & static analysis                 |
| **PHP quality**        | **Pint + PHPStan**     | Formatting & Level 8 static analysis                  |

---

## 🏛 Architecture

```text
                         INTERNET
                             │
                             ▼
                    ┌─────────────────┐
                    │      NGINX      │
                    │ Reverse Proxy   │
                    └────────┬────────┘
                             │
              ┌──────────────┴──────────────┐
              │                             │
              ▼                             ▼
     ┌─────────────────┐          ┌─────────────────┐
     │    Next.js 16   │          │   Laravel 13    │
     │                 │          │                 │
     │ React 19.3      │          │ PHP 8.5         │
     │ TypeScript 5    │◄────────►│ REST API        │
     │ Tailwind 4.3    │  JSON    │ Sanctum         │
     └─────────────────┘          └────────┬────────┘
                                           │
                           ┌───────────────┼───────────────┐
                           │               │               │
                           ▼               ▼               ▼
                    ┌────────────┐  ┌────────────┐  ┌────────────┐
                    │ PostgreSQL │  │   Redis    │  │   Queue    │
                    │    18      │  │            │  │  Workers   │
                    └────────────┘  └────────────┘  └────────────┘
```

---

## 🛠 Quick Start (Docker Compose — Recommended)

Start all services:
```bash
docker compose up -d
```

Run migrations & Scout index:
```bash
docker compose exec backend php artisan migrate --seed
docker compose exec backend php artisan scout:import "App\Models\Protocol"
docker compose exec backend php artisan scout:import "App\Models\Thread"
```

Services are exposed at:
- **Web App & API Gateway**: http://localhost
- **Next.js Direct**: http://localhost:3000
- **PostgreSQL 18**: `localhost:5432` (User: `postgres`, Pass: `secret`, DB: `protocol_platform`)
- **Redis**: `localhost:6379`
- **Typesense**: `localhost:8108`

---

## 🧪 Testing & Code Quality

### Backend
```bash
# If using Docker (Zero-PHP Host):
docker compose exec backend ./vendor/bin/pest
docker compose exec backend ./vendor/bin/pint
docker compose exec backend ./vendor/bin/phpstan

# If using Host PHP:
cd backend
./vendor/bin/pest          # Pest 4 test suite
./vendor/bin/pint          # Laravel Pint formatting
./vendor/bin/phpstan       # PHPStan / Larastan static analysis
```

### Frontend
```bash
cd frontend
npm run test               # Vitest component & unit tests
npm run test:e2e           # Playwright E2E browser tests
npm run lint               # ESLint
npm run format             # Prettier
```

---

## 📂 Next Steps
Inspect `app/Services/*Service.php` and `app/Repositories/*Repository.php` in backend,
and `features/*` in frontend. Every stub includes `// TODO` markers defining where
business logic and domain rules belong.
