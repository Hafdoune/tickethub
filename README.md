# TicketHub

A customer support (helpdesk) application built with **Symfony 7.4**, **Doctrine**, **MySQL** and **Vue.js**.

Customers open tickets and talk with the support team. Agents work through the queue, assign tickets to themselves, update their status and follow activity from a Vue.js dashboard. A REST API lets other tools integrate with TicketHub.

![CI](https://github.com/Hafdoune/tickethub/actions/workflows/ci.yml/badge.svg)

## Features

- Form login, registration and hierarchical roles (`ROLE_USER` < `ROLE_AGENT` < `ROLE_ADMIN`)
- Tickets with a category, a priority and a lifecycle (`open → in progress → resolved → closed`)
- Customer / support conversation thread; a customer reply reopens a resolved ticket
- Access rules centralised in a **Voter** (`TicketVoter`): customers only see their own tickets and can only edit a ticket before an agent picks it up
- Filterable, paginated ticket list (status, priority, full-text search)
- Agent dashboard in **Vue.js 3 + TypeScript** (`.vue` components with `<script setup>`): counters, sortable queue, "assign to me", auto-refresh
- **JSON REST API** authenticated with Bearer tokens
- Unit and functional tests, continuous integration with GitHub Actions
- Docker image based on FrankenPHP

The user interface is in French, as the app targets French-speaking customers.

## Stack

| Layer | Choice |
|---|---|
| Back end | PHP 8.3, Symfony 7.4 (LTS) |
| Data | Doctrine ORM 3, Doctrine Migrations, MySQL 8.4 |
| Front end | Twig + Twig Components, Vue.js 3 (Composition API, `<script setup lang="ts">`), TypeScript, Vite |
| UI | Tailwind CSS v4, shadcn/ui: [Symfony UX Toolkit](https://ux.symfony.com/toolkit) for Twig, [shadcn-vue](https://www.shadcn-vue.com/) for Vue |
| Security | Security bundle, form login, access token authenticator, voters, CSRF |
| Tests | PHPUnit 12, WebTestCase, DAMA Doctrine Test Bundle |
| Quality | PHPStan (level 6, Symfony and Doctrine extensions), vue-tsc |
| Infrastructure | Docker, FrankenPHP, GitHub Actions |

## Run with Docker

```bash
git clone https://github.com/Hafdoune/tickethub.git
cd tickethub
docker compose up -d --build
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

The app is available at http://localhost:8080. Migrations run when the container starts.

## Run locally

Requirements: PHP 8.2+, Composer, Node.js 20+, and Docker (for MySQL) or a local MySQL server.

```bash
composer install
npm install
npm run build
docker compose up -d database
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
symfony serve
```

While working on the front end, `npm run dev` starts the Vite dev server with hot reload; the Symfony bundle switches to it automatically.

The Docker database listens on port `3307` so it doesn't clash with a local MySQL. To use another database, override `DATABASE_URL` in `.env.local`.

## Demo accounts

Password for every account: `password`

| Role | Email |
|---|---|
| Admin | `admin@tickethub.test` |
| Agent | `agent@tickethub.test` |
| Agent | `agent2@tickethub.test` |
| Customer | `client@tickethub.test` |

## API

Generate a token (it is shown only once; only its SHA-256 hash is stored):

```bash
php bin/console app:api-token client@tickethub.test
```

| Method | Route | Description |
|---|---|---|
| `GET` | `/api/me` | Current user |
| `GET` | `/api/tickets` | Paginated list (`page`, `limit`, `status`, `priority`, `category`, `q`) |
| `GET` | `/api/tickets/{id}` | Ticket details with the conversation |
| `POST` | `/api/tickets` | Create a ticket |
| `PATCH` | `/api/tickets/{id}` | Edit the content (author) or the status / assignee (agents) |
| `POST` | `/api/tickets/{id}/close` | Close a ticket |
| `POST` | `/api/tickets/{id}/comments` | Reply |

```bash
curl -X POST http://localhost:8080/api/tickets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Box sans connexion","description":"Plus d internet depuis ce matin.","categoryId":1,"priority":"high"}'
```

Errors use the `application/problem+json` format. An invalid request returns `422` with the list of violations per field.

## Design decisions

- **A Voter instead of `if`s in controllers**: every access rule for a ticket lives in one place and is unit-tested. Controllers only declare `#[IsGranted(TicketVoter::EDIT, 'ticket')]`.
- **DTOs + `#[MapRequestPayload]`** for the API: JSON is deserialised and validated before it reaches the controller; entities are never filled directly from user input.
- **Serialization groups** (`ticket:list`, `ticket:item`) control which fields are exposed (no emails or roles in public responses).
- **Business logic in the entities**: `Ticket::assignTo()` moves an open ticket to "in progress", `Ticket::close()` records the closing date.
- **Explicit joins** (`addSelect`) and Doctrine's `Paginator` avoid the N+1 problem on lists.
- **Hashed API tokens**: a database leak does not give access to the API.
- **One design system**: Twig pages use the shadcn components from Symfony UX Toolkit (`<twig:Button>`, `<twig:Card>`…) and a custom form theme, the dashboard uses shadcn-vue; both share the same Tailwind CSS variables.
- **`useDashboard` composable**: the dashboard's state, loading and assignment logic is separated from rendering; components only receive typed props.
- **Tests isolated in transactions** (DAMA): each test creates its own data and the database is rolled back automatically.

## Tests

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
npm run build
php bin/phpunit
vendor/bin/phpstan analyse
npm run typecheck
```

## Structure

```
src/
├── Command/            Console command (API token generation)
├── Controller/         Web, dashboard and API controllers (Api/)
├── DataFixtures/       Demo data
├── Dto/                Validated API payloads
├── Entity/             User, Ticket, Category, Comment
├── Enum/               TicketStatus, TicketPriority
├── Form/               Symfony forms
├── Repository/         Doctrine queries and filters
└── Security/           Voter and token authentication
assets/
├── components/ui/      shadcn-vue components
├── dashboard/          Vue components (.vue), composable, API client and TypeScript types
└── styles/             Tailwind CSS and shadcn theme
templates/
├── components/         shadcn Twig components (UX Toolkit) and domain badges
└── form/               shadcn form theme
tests/                  Unit and functional tests
```
