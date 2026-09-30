# The Beginner's Engineering Guide to the Backend Architecture
## A 1st-Year Student's Guide to Enterprise Laravel, Clean Architecture & Object-Oriented Programming (OOP)

Welcome to the backend of the **Community-Powered Protocol & Discussion Platform**!

If you are a first-year computer science, IT, or software engineering student, enterprise web repositories can feel intimidating at first glance. You see folders like `Controllers`, `Services`, `Repositories`, `Requests`, `Resources`, `Models`, and `Interfaces`, and you might wonder:

> *"In my programming class, I can just write an SQL query in a single file or function. Why are there so many files, classes, and layers here?"*

The short answer is **Clean Architecture** and **Separation of Concerns**. When systems grow to handle hundreds of thousands of users, mixing database queries, validation rules, HTTP headers, and business logic into one place turns code into unmaintainable "spaghetti."

This guide breaks down every single component, workflow, and Object-Oriented design pattern used in this project using clear analogies, visual diagrams, and real file references.

---

## 1. The Big Picture: The Restaurant Analogy

Imagine our backend application is a **high-end restaurant**:

```text
 ┌─────────────────┐
 │   CLIENT / UI   │  (Customer)
 └────────┬────────┘
          │ 1. Orders food (HTTP Request: POST /api/v1/votes)
          ▼
 ┌─────────────────┐
 │   MIDDLEWARE    │  (Door Bouncer / Security)
 └────────┬────────┘
          │ 2. Checks dress code, sanitizes, measures service time
          ▼
 ┌─────────────────┐
 │  FORM REQUEST   │  (Order Form Inspector)
 └────────┬────────┘
          │ 3. Validates order fields (e.g., must be a valid dish ID)
          ▼
 ┌─────────────────┐
 │   CONTROLLER    │  (The Waiter / Cashier)
 └────────┬────────┘
          │ 4. Receives the validated request, hands task to Kitchen
          ▼
 ┌─────────────────┐
 │ DOMAIN SERVICE  │  (The Head Chef - Business Rules)
 └────────┬────────┘
          │ 5. Executes logic: "Can this person vote? Did they vote already?"
          ▼
 ┌─────────────────┐
 │   REPOSITORY    │  (Pantry Manager / Storage Specialist)
 └────────┬────────┘
          │ 6. Knows WHERE ingredients live (SQL Database vs Typesense Search)
          ▼
 ┌─────────────────┐
 │ ELOQUENT MODEL  │  (The Pantry Box & Ingredients)
 └────────┬────────┘
          │ 7. Relational tables & records
          ▼
 ┌─────────────────┐
 │  API RESOURCE   │  (Plating & Presentation Chef)
 └────────┬────────┘
          │ 8. Formats clean JSON output back to the Customer
          ▼
 ┌─────────────────┐
 │   HTTP 200 OK   │  (Delicious Dish Served!)
 └─────────────────┘
```

| Layer in Code | Restaurant Role | Responsibility | Real File Example |
| :--- | :--- | :--- | :--- |
| **Route & Middleware** | Hostess & Security | Routes request to right place; checks auth and latency | [`routes/api.php`](file:///d:/PHP/protocol-platform/backend/routes/api.php), [`TrackResponseTime.php`](file:///d:/PHP/protocol-platform/backend/app/Http/Middleware/TrackResponseTime.php) |
| **Form Request** | Order Inspector | Validates input format before code runs | [`StoreReviewRequest.php`](file:///d:/PHP/protocol-platform/backend/app/Http/Requests/StoreReviewRequest.php) |
| **Controller** | Waiter / Cashier | Accepts HTTP input, calls Service, returns Response | [`ProtocolController.php`](file:///d:/PHP/protocol-platform/backend/app/Http/Controllers/Api/v1/ProtocolController.php) |
| **Domain Service** | Executive Chef | Enforces business rules & transactional steps | [`ProtocolService.php`](file:///d:/PHP/protocol-platform/backend/app/Services/ProtocolService.php) |
| **Repository** | Pantry Manager | Retrieves/persists data; hides storage details | [`ProtocolRepository.php`](file:///d:/PHP/protocol-platform/backend/app/Repositories/Eloquent/ProtocolRepository.php) |
| **Eloquent Model** | Ingredient Specs | Defines database schema, relationships, and data | [`Protocol.php`](file:///d:/PHP/protocol-platform/backend/app/Models/Protocol.php) |
| **API Resource** | Presentation Chef | Shapes JSON output with exact field aliases | [`ProtocolResource.php`](file:///d:/PHP/protocol-platform/backend/app/Http/Resources/ProtocolResource.php) |

---

## 2. The 4 Pillars of OOP Used in This Project

In school, you learn OOP principles in theory. Here is how they are literally implemented in our code:

### A. Encapsulation (Hiding Internal State)
**Encapsulation** means bundling data and methods that operate on that data inside a class, while restricting direct access from the outside.

* **Where it is in our code:** Look at [`VoteService.php`](file:///d:/PHP/protocol-platform/backend/app/Services/VoteService.php):
  ```php
  class VoteService
  {
      // Encapsulated whitelist: Outside code cannot tamper with allowed types
      protected array $allowedTypes = [
          'protocol' => Protocol::class,
          'thread' => Thread::class,
          'comment' => Comment::class,
      ];
      ...
  }
  ```
  Outside controllers cannot modify `$allowedTypes`. They can only call `cast($user, $type, $id, $value)`, and the class protects its own internal rules.

* **Another Example:** In [`ProtocolRepository.php`](file:///d:/PHP/protocol-platform/backend/app/Repositories/Eloquent/ProtocolRepository.php), the complex logic of searching Typesense Cloud vs querying fallback SQL tables is kept `protected`. The outside world simply calls `$repo->findBySlugOrFail($slug)`.

---

### B. Abstraction (Hiding Complexity Behind Simple Interfaces)
**Abstraction** means giving the programmer a simple button to press without forcing them to understand the complicated mechanics behind it.

* **Where it is in our code:** The **Interface Contract** pattern!
  Look at [`ProtocolRepositoryInterface.php`](file:///d:/PHP/protocol-platform/backend/app/Repositories/Contracts/ProtocolRepositoryInterface.php):
  ```php
  interface ProtocolRepositoryInterface
  {
      public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;
      public function findBySlugOrFail(string $slug): Protocol;
      public function getTopVoted(int $limit = 10): Collection;
  }
  ```
  The Controller doesn't know if protocols are stored in PostgreSQL, SQLite, Typesense, MongoDB, or an AWS S3 bucket. It only knows that `findBySlugOrFail()` returns a `Protocol`.

---

### C. Inheritance (Code Reuse Without Duplication)
**Inheritance** allows a child class to inherit all the properties and methods of a parent class.

* **Where it is in our code:**
  1. **Eloquent Models:** [`Protocol.php`](file:///d:/PHP/protocol-platform/backend/app/Models/Protocol.php) extends `Illuminate\Database\Eloquent\Model`.
     By inheriting from `Model`, `Protocol` automatically gets database powers like `::find()`, `::create()`, `$protocol->save()`, and dirty-tracking without writing 500 lines of SQL boilerplate.
  2. **Form Requests:** [`StoreReviewRequest.php`](file:///d:/PHP/protocol-platform/backend/app/Http/Requests/StoreReviewRequest.php) extends `FormRequest`.
     It inherits automatic validation redirects, JSON error formatting (HTTP 422), and user authentication checks.

---

### D. Polymorphism (One Interface, Many Forms)
**Polymorphism** means that different entities can be treated identically through a shared mechanism. In this project, we use **Polymorphic Database Relationships**:

* **The Problem:** In our app, users can vote on **Protocols**, **Threads**, and **Comments**.
  - Traditional beginner way: Create 3 separate tables (`protocol_votes`, `thread_votes`, `comment_votes`). That causes duplicated code!
  - The Polymorphic OOP way: Create ONE `votes` table and ONE `Vote` model!

Look at [`Vote.php`](file:///d:/PHP/protocol-platform/backend/app/Models/Vote.php):
```php
class Vote extends Model
{
    // A vote morphs to whatever entity is being voted on!
    public function votable(): MorphTo
    {
        return $this->morphTo();
    }
}
```
And in [`Protocol.php`](file:///d:/PHP/protocol-platform/backend/app/Models/Protocol.php):
```php
public function votes(): MorphMany
{
    return $this->morphMany(Vote::class, 'votable');
}
```
The `votes` table has two special columns:
- `votable_type`: Stores the class name (e.g., `App\Models\Protocol` or `App\Models\Thread`).
- `votable_id`: Stores the specific ID (e.g., `11`).

One single `Vote` class works dynamically across three completely different features!

---

## 3. Dependency Injection (DI) & Inversion of Control (IoC)

In first-year classes, students often create objects with the `new` keyword:
```php
// The beginner way (Tight Coupling):
public function index() {
    $repo = new ProtocolRepository(); // Hardcoded dependency!
    return $repo->all();
}
```
Why is this bad? Because if `ProtocolRepository` needs a database connection or API client, you have to manually configure it every time. And in automated tests, you cannot easily replace it with a mock!

### The Modern Way: Dependency Injection
Look at [`ProtocolController.php`](file:///d:/PHP/protocol-platform/backend/app/Http/Controllers/Api/v1/ProtocolController.php):
```php
class ProtocolController extends Controller
{
    public function __construct(
        protected ProtocolRepositoryInterface $repository, // Injected!
        protected ProtocolService $service                 // Injected!
    ) {}
}
```
Notice there is **no `new` keyword**!
When a user visits `/api/v1/protocols`, Laravel's **Service Container** inspects the constructor, sees that `ProtocolController` needs `ProtocolRepositoryInterface`, automatically constructs the implementation ([`ProtocolRepository`](file:///d:/PHP/protocol-platform/backend/app/Repositories/Eloquent/ProtocolRepository.php)), passes the Typesense client, and injects it into the controller.

This is called **Inversion of Control (IoC)**: the framework manages the lifecycle of your objects for you!

---

## 4. End-to-End Walkthrough: What Happens During a Request?

Let's trace a real user action: **A clinician submits a peer review on a protocol.**

```text
Browser/Client
   │ POST /api/v1/protocols/11/reviews
   │ Body: { "rating": 5, "summary": "Great evidence base", "verdict": "approved" }
   ▼
[1] routes/api.php
   │ Route::post('protocols/{protocol}/reviews', [ReviewController::class, 'store'])
   ▼
[2] TrackResponseTime Middleware
   │ Starts timer: $start = hrtime(true)
   ▼
[3] StoreReviewRequest (Validation Guard)
   │ Validates that:
   │  - rating is an integer between 1 and 5
   │  - verdict is one of 'approved', 'changes_requested', 'rejected'
   │ If invalid -> immediately returns 422 JSON with errors (no database touched!)
   ▼
[4] ReviewController@store
   │ Extracts validated data: $request->validated()
   │ Delegates to domain service:
   │ $review = $this->service->create($protocol, $request->user(), $data);
   ▼
[5] ReviewService@create (The Brain)
   │ Business Rule Check 1: Is user reviewing their own protocol?
   │   if ($user->id === $protocol->user_id) -> throw 422 "Authors cannot review own protocol"
   │ Business Rule Check 2: Did user already review this protocol?
   │   if (Review::where('protocol_id', $protocol->id)->where('user_id', $user->id)->exists())
   │     -> throw 422 "Duplicate review"
   │ Database Transaction:
   │   1. Insert row into `reviews` table.
   │   2. Recalculate protocol's `average_rating` and `reviews_count`.
   │   3. Save updated metrics to `protocols` table.
   ▼
[6] Scout / Typesense Event Listener
   │ Protocol model was updated -> Scout triggers `searchable()`
   │ Automatically pushes updated rating and review count to Typesense Cloud!
   ▼
[7] ReviewResource
   │ Converts Eloquent model to clean, predictable JSON response
   ▼
[8] TrackResponseTime Middleware (Response Exit)
   │ Attaches X-Response-Time and Server-Timing headers
   │ If local dev server -> sends Connection: close
   ▼
Browser receives: HTTP 201 Created with JSON payload!
```

---

## 5. The Storage Topology: Why Two Databases?

You might wonder: *"Why do we have PostgreSQL AND Typesense running at the same time?"*

| Characteristic | PostgreSQL 17 (Relational Database) | Typesense 27 (Search Engine Sidecar) |
| :--- | :--- | :--- |
| **Primary Purpose** | **Transactional Single Source of Truth** | **Instant Read Catalog & Typo Search** |
| **Strengths** | Strict ACID transactions, foreign keys (`cascadeOnDelete`), unique compound constraints (no duplicate votes) | Sub-15ms prefix search, typo tolerance (finding "circadian" even if you type "circadin"), faceted sorting |
| **Weaknesses** | Text searches with `LIKE '%term%'` can be slow on huge datasets | Not designed for complex transactional rollbacks or user password hashes |
| **Analogy** | The bank's main vault and ledger | The fast electronic library search kiosk in the lobby |

### How They Work Together (Dual-Engine Harmony)
1. **Writes (Create, Update, Delete, Vote, Review):** Always go directly to **PostgreSQL**. This guarantees zero lost data and ensures foreign key safety.
2. **Syncing:** Whenever a protocol or thread changes in PostgreSQL, Laravel Scout automatically serializes it (`toSearchableArray()`) and sends a copy to **Typesense**.
3. **Reads (Search, Catalog, Detail Pages):** Queries hit **Typesense** directly for `< 1ms` lightning-fast response times.

---

## 6. Directory Map & Where Code Lives

Here is your quick-reference map to explore the backend folder structure:

```text
backend/
├── app/
│   ├── Console/Commands/       <- CLI commands you run in terminal (benchmark, reindex)
│   ├── Events/                 <- Events that announce something happened (e.g. VoteCast)
│   ├── Http/
│   │   ├── Controllers/Api/v1/ <- API endpoints (Receives HTTP requests, calls Services)
│   │   ├── Middleware/         <- Filters running before/after requests (CORS, Latency)
│   │   ├── Requests/           <- Form validation rules (Guards before controller)
│   │   └── Resources/          <- Data transformers (Formats output JSON)
│   ├── Models/                 <- Eloquent database entities (Protocol, Thread, Comment, Vote)
│   ├── Repositories/           <- Data query abstraction layer (Interfaces + Typesense logic)
│   └── Services/               <- Business logic engines (Voting rules, Review eligibility)
├── config/                     <- Configuration settings (cors.php, scout.php, database.php)
├── database/
│   ├── migrations/             <- Blueprint instructions creating database tables
│   └── seeders/                <- Creates realistic demo/mock data (Protocols, Threads, Users)
├── routes/
│   └── api.php                 <- All URL route definitions (/api/v1/protocols, etc.)
└── tests/
    ├── Feature/                <- Automated API endpoint tests
    ├── Unit/                   <- Isolated tests for Services and Repositories
    └── E2E/                    <- Complete full-lifecycle simulation tests
```

---

## 7. How to Experiment and Learn as a 1st-Year Student

Here are 3 fun ways to play with this code and see the patterns in action:

1. **Run the Latency Benchmark:**
   ```bash
   php artisan benchmark:latency --count=20
   ```
   *Watch how the Middleware, Repository, and Typesense collaborate to respond in 0.5 milliseconds!*

2. **Run the Test Suite:**
   ```bash
   php artisan test
   ```
   *Notice how 107 tests execute in under 4 seconds. Automated tests are living documentation of how every method is expected to work.*

3. **Inspect the Seed Data:**
   Open [`database/seeders/DatabaseSeeder.php`](file:///d:/PHP/protocol-platform/backend/database/seeders/DatabaseSeeder.php). See how it creates realistic protocols (like "Circadian Optimization" and "Norwegian 4x4"), seeds expert clinicians, and links discussion threads.

---

### Summary Checklist for Mastering this Architecture

- [x] **Controllers** should be thin: they only handle input and output.
- [x] **Services** hold the rules: if it's a decision ("can this user do this?"), put it in a Service.
- [x] **Repositories** hold the data queries: never write raw database lookups in a Controller.
- [x] **Form Requests** protect the door: validation stops bad data before your code even runs.
- [x] **Resources** format the output: they protect internal database column names from leaking to the outside world.
