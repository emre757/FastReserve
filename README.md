# FastReserve

![Tests](https://github.com/emre757/FastReserve/actions/workflows/tests.yml/badge.svg)

FastReserve is a reservation platform where companies ("teams") publish offerings such as
events, appointments, and time slots, and customers book them. Its main problem is limited capacity,
temporary reservation holds, and asynchronous payment results without overbooking.

## Status: work in progress

This is a portfolio project under active development. Implemented flows include team/company management,
offering creation, authentication with 2FA, reservation holds and cancellation, Stripe Checkout, and confirmation
through signed payment webhooks. Delayed jobs expire reservation holds and complete offerings; availability changes
are broadcast to the frontend.

Free offering confirmation, refunds, and more reservation/concurrency features remain incomplete.
Stripe Connect onboarding is implemented but still has unfinished work documented in [Technical notes](TECHNICAL.md).
See the [TODO list](TODO.md) for remaining work.

## Usage of AI

AI helped with some frontend pages, the PHPStan rule for required model events, the lock-ordering helper,
and parts of the payment tests, such as stripe SDK tests.

## Highlights

- **Custom PHPStan rules:** enforce project conventions and catch unsafe patterns, such as bulk updates or deletes that
  bypass required model events.
- **Multi-tenancy:** enforced through Laravel Policies and team-scoped queries, a simpler approach than giving each
  company its own database, and enough for this project's size.
- **Transaction writes:** operations with more than one write (for example, creating an offering also creates
  an audit log) run inside a DB transaction.
- **Reservation capacity:** row locks protect capacity checks and writes; pending holds have an expiration time.
- **Payments:** integer minor units, checkout idempotency per payment attempt, signed webhooks, and guarded payment
  status
  separate checkout creation from reservation confirmation.
- **Domain events:** separate side effects (audit logging, live availability updates) from controllers.
- **Real-time updates:** private Laravel Reverb channels publish availability with a version number; the React hook
  ignores older updates.
- **Form Requests + Policies** keep validation and authorization out of controllers.
- **Typed frontend/backend:** Wayfinder generates typed route/controller functions for Inertia navigation and forms.
- **CI on every PR** — Pint, PHPStan/Larastan, ESLint, Prettier, and Pest all run in GitHub Actions against a real
  PostgreSQL service. While some configuration comes from the starter kit, I customized the starter-kit workflow for
  this project; for example, caching Composer dependencies to speed up runs.

## Tech stack

**Backend:** Laravel 13 (PHP 8.5), PostgreSQL, Redis, Laravel Horizon (queues), Laravel Reverb (WebSockets),
Laravel Fortify (auth + 2FA)

**Frontend:** Inertia.js v3, React 19, TypeScript, Tailwind CSS v4, and component libraries such as Radix UI

**Tooling:** Laravel Sail (Docker), Wayfinder, Pest, PHPStan, Pint, ESLint/Prettier, GitHub Actions, and more

## Technical notes

See [Technical notes](TECHNICAL.md) for lock ordering, reservation capacity, payment state,
webhook handling, broadcast versions, background jobs, and the `RequiresModelEvents` PHPStan rule.

## Getting started

Requires Docker with Compose, PHP 8.5 with the project's required extensions, and Composer 2 for the initial
dependency installation. Other commands run inside Laravel Sail, which gives PHP, Node, PostgreSQL,
Redis, and Mailpit.

```bash
git clone https://github.com/emre757/FastReserve.git
cd FastReserve
composer install --no-scripts
./vendor/bin/sail up -d
./vendor/bin/sail composer setup
./vendor/bin/sail artisan db:seed
```

Use `--no-scripts` during the first composer installation so that Laravel's Composer hooks can be run when sail is
available.
`composer setup` installs dependencies, generates the application key, runs migrations, and builds frontend.
Seeding is separate.

Before starting development, follow [stripe setup](#stripe-setup).

### Run the development processes

```bash
./vendor/bin/sail composer run dev
```

This starts the following processes:

| Process    | Command                    | Purpose                                                            |
|------------|----------------------------|--------------------------------------------------------------------|
| Horizon    | `php artisan horizon`      | Manages Redis jobs, including delayed expiration and notifications |
| Reverb     | `php artisan reverb:start` | Starts websockets                                                  |
| Vite       | `npm run dev`              | Starts frontend with hot reload                                    |
| PHP server | `php artisan serve`        | Starts Laravel's development server                                |
| Logs       | `php artisan pail`         | Application logs                                                   |

The scheduler is separate. To run the daily cleanup tasks in `routes/console.php`, use another terminal:

```bash
./vendor/bin/sail artisan schedule:work
```

Reservation hold expiration uses delayed queue jobs, so it requires Horizon and not scheduler.
Stripe webhook also runs separate:

## Stripe setup

Use a stripe sandbox account. Set `STRIPE_SECRET` to your secret API key and `STRIPE_KEY` to your
publishable key in `.env`. For paid checkout, the offering's company needs a connected payment account marked
active with transfers enabled; the database seeder does not provision a Stripe account.

**Make sure you've installed [Stripe CLI](https://docs.stripe.com/cli).**

Run one Stripe CLI listener during local development:

```bash
stripe listen \
  --events 'checkout.session.completed,checkout.session.async_payment_succeeded,checkout.session.async_payment_failed,checkout.session.expired' \
  --thin-events 'v2.core.account[configuration.recipient].capability_status_updated,v2.core.account.closed' \
  --forward-thin-to http://localhost:80/webhooks/stripe/accounts \
  --forward-to http://localhost:80/webhooks/stripe/payments
```

Set each endpoint's signing secret from the listener output in `.env`. Usually it uses one shared signing
secret, then both values are the same:

```dotenv
STRIPE_ACCOUNT_WEBHOOK_SECRET=whsec_...
STRIPE_PAYMENT_WEBHOOK_SECRET=whsec_...
```

See [Stripe's webhook documentation](https://docs.stripe.com/webhooks) for CLI forwarding and signing secrets.
The account-webhook configuration mismatch is listed under current limitations in [Technical notes](TECHNICAL.md).

## Tests and checks

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail composer run ci:check
```

This runs all tests including frontend/backend linting/quality checks and the test suite.
It is also run during CI so make sure this passes before pushing to the main branch.

## Terminology

The `Team` model represents a company in the backend; "company" is just the front-end name for a team and its
offerings, not a separate entity.

Reservations are never deleted; a canceled or expired reservation just changes status.

## What's left

[TODO List](TODO.md)
