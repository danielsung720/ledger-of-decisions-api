# Ledger of Decisions API

[English](README.md) | [繁體中文](README.zh-TW.md)

Backend API service for **Ledger of Decisions** - a personal finance application that tracks not just *what* you spend, but *why* you spend it.

## Tech Stack

- **Framework**: Laravel 12 (PHP 8.4)
- **Database**: SQLite (dev) / PostgreSQL (prod)
- **Cache**: Redis 7
- **Auth**: Laravel Sanctum (stateful session + CSRF)
- **Docs**: L5 Swagger (OpenAPI)
- **Email**: Resend
- **Container**: Docker (PHP-FPM + Nginx)

## Quick Start

### Prerequisites

- Docker & Docker Compose

### Setup

```bash
# Build and start containers
make build
make up

# Run migrations
make migrate

# (Optional) Fresh migration with seed data
make fresh
```

The API will be available at `http://localhost:8080/api/`.

### Environment

Copy `.env.example` to `.env` and adjust as needed. Key variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_CONNECTION` | `sqlite` | Database driver (`sqlite`, `pgsql`) |
| `CACHE_STORE` | `redis` | Cache backend |
| `MAIL_MAILER` | `log` | Email driver (`log`, `resend`) |
| `RESEND_API_KEY` | - | Resend API key (production) |
| `SESSION_DOMAIN` | `null` | Session cookie domain |
| `SESSION_SECURE_COOKIE` | `false` | Require HTTPS for session cookie |
| `SESSION_SAME_SITE` | `lax` | Session cookie same-site policy |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost,...` | Frontend domains using cookie auth |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:3000,...` | Allowed credentialed frontend origins |

## Architecture

```
app/
├── Console/Commands/     # Artisan commands (recurring expenses cron)
├── DTO/                  # Data Transfer Objects (by domain)
├── Enums/                # PHP 8.1+ enums (Category, Intent, etc.)
├── Events/               # Domain events
├── Http/
│   ├── Controllers/      # 11 API controllers
│   ├── Requests/         # Form request validation (20+)
│   └── Resources/        # JSON resource transformers
├── Models/               # Eloquent models
├── Repositories/         # Data access layer
├── Rules/                # Custom validation rules
└── Services/             # Business logic layer (12 services)
```

**Patterns**: Service Layer, Repository Pattern, DTO, Form Requests, Enum-based categorization.

## API Endpoints

### Stateful Auth Flow

1. Call `GET /sanctum/csrf-cookie` from frontend origin to receive `XSRF-TOKEN`.
2. Call `POST /api/login` with credentials (session cookie is established).
3. For write requests, send `X-XSRF-TOKEN` header.
4. Call `POST /api/logout` to invalidate session.
5. `Authorization: Bearer <token>` is disabled for protected API routes.

### Public

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/health` | Health check |
| `POST` | `/api/register` | User registration |
| `POST` | `/api/login` | Authentication |
| `POST` | `/api/verify-email` | Email verification |
| `POST` | `/api/resend-verification` | Resend verification code |
| `POST` | `/api/forgot-password` | Request password reset |
| `POST` | `/api/reset-password` | Complete password reset |
| `GET` | `/sanctum/csrf-cookie` | Issue XSRF-TOKEN cookie |

### Protected (Session Cookie)

**User**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/logout` | Logout |
| `GET` | `/api/user` | Current user |
| `PUT` | `/api/user/password` | Update password |
| `GET/PUT` | `/api/user/preferences` | User preferences |

**Expenses**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET/POST` | `/api/expenses` | List / Create |
| `GET/PUT/DELETE` | `/api/expenses/{id}` | Read / Update / Delete |
| `DELETE` | `/api/expenses/batch` | Batch delete |

**Decisions** (nested under expense)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/expenses/{id}/decision` | Tag decision |
| `GET/PUT/DELETE` | `/api/expenses/{id}/decision` | Read / Update / Remove |

**Combined Entry**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/entries` | Create expense + decision |

**Statistics**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/statistics/intents` | Intent breakdown |
| `GET` | `/api/statistics/summary` | Overall summary |
| `GET` | `/api/statistics/trends` | Trend analysis |

**Recurring Expenses**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET/POST` | `/api/recurring-expenses` | List / Create |
| `GET/PUT/DELETE` | `/api/recurring-expenses/{id}` | Read / Update / Delete |
| `GET` | `/api/recurring-expenses/upcoming` | Next 7 days |
| `POST` | `/api/recurring-expenses/{id}/generate` | Generate expenses |
| `GET` | `/api/recurring-expenses/{id}/history` | View history |

**Cash Flow**

| Method | Endpoint | Description |
|--------|----------|-------------|
| CRUD | `/api/incomes` | Income management |
| CRUD | `/api/cash-flow-items` | Cash flow items |
| `GET` | `/api/cash-flow/summary` | Cash flow summary |
| `GET` | `/api/cash-flow/projection` | Future projection |

### API Response Format

```json
{
  "success": true,
  "data": { ... },
  "error": null
}
```

Paginated responses include `meta` and `links` objects.

## Database Schema

| Table | Description |
|-------|-------------|
| `users` | User accounts with email verification |
| `expenses` | Spending records (amount, category, date, note) |
| `decisions` | Intent tagging (1:1 with expense) |
| `recurring_expenses` | Templates for recurring spending |
| `incomes` | Revenue records |
| `cash_flow_items` | Cash flow projections |

### Enums

- **Category**: `food`, `transport`, `training`, `living`, `other`
- **Intent**: `necessity`, `efficiency`, `enjoyment`, `recovery`, `impulse`
- **ConfidenceLevel**: `high`, `medium`, `low`
- **FrequencyType**: `daily`, `weekly`, `monthly`, `yearly`

## Makefile Commands

```bash
make build       # Build Docker images
make up          # Start containers
make down        # Stop containers
make restart     # Restart containers
make shell       # Access app container shell

make artisan cmd="..."   # Run artisan command
make composer cmd="..."  # Run composer command

make migrate     # Run migrations
make fresh       # Fresh migration + seed

make test        # Run tests
make coverage    # Run tests with coverage (min 85%)
make phpstan     # Static analysis
```

## Testing

```bash
# Run all tests
make test

# Run with coverage report (minimum 85%)
make coverage

# Run specific test
make artisan cmd="test --filter=ExpenseTest"
```

Tests use in-memory SQLite for speed. Test suites:
- `tests/Feature/` - API integration tests
- `tests/Unit/` - Unit tests for repositories, resources, enums

## API Documentation

Swagger/OpenAPI documentation is available at:

```
GET /api/documentation
```

## License

MIT
