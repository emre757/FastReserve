# FastReserve

![Tests](https://github.com/emre757/FastReserve/actions/workflows/tests.yml/badge.svg)

FastReserve is a reservation platform where companies ("teams") publish offerings such as
events, appointments, and time slots, and customers book them. The core problem it's built to solve is guaranteeing that
bookings never sell more spots than an offering's capacity.

## Status: work in progress

This is a portfolio project, not a finished product. **Team/company management, offerings, and authentication (including
2FA) are functional.** The reservation booking flow (the main feature) is still being built:
the data model, migration, and live-capacity broadcasting exist, but creating, confirming, expiring, and cancelling a
reservation isn't wired up yet. See [What's left](#whats-left).

## Highlights

- **Multi-tenancy:** enforced through Laravel Policies and team-scoped queries, a simpler approach than giving each
  company its own database, and enough for this project's size.
- **Transaction writes:** operations with more than one write (for example, creating an offering also creates
  an audit log) run inside a DB transaction.
- **Domain events:** separate side effects (audit logging, live availability updates) from controllers.
- **Real-time updates (IN PROGRESS):** offering capacity changes broadcast over WebSockets (Laravel Reverb) and are live
  on the frontend via Laravel Echo. (this is still WIP)
- **Form Requests + Policies** keep validation and authorization out of controllers.
- **Typed frontend/backend:** Wayfinder generates typed route/controller calls for Inertia, so the
  frontend never manually writes a URL.
- **CI on every PR** — Pint, PHPStan/Larastan, ESLint, Prettier, and Pest all run in GitHub Actions against a real
  PostgreSQL service. While some configuration comes from the starter kit, I customized the starter-kit workflow for
  this project; for example, caching Composer dependencies to speed up runs.

## Tech stack

**Backend:** Laravel 13 (PHP 8.5), PostgreSQL, Redis, Laravel Horizon (queues), Laravel Reverb (WebSockets),
Laravel Fortify (auth + 2FA)

**Frontend:** Inertia.js v3, React 19, TypeScript, Tailwind CSS v4, and component libraries such as Radix UI

**Tooling:** Laravel Sail (Docker), Wayfinder, Pest, PHPStan, Pint, ESLint/Prettier, GitHub Actions, and more

## Getting started

Requires [Docker](https://www.docker.com/).

```bash
git clone https://github.com/emre757/FastReserve.git
cd FastReserve
composer install
./vendor/bin/sail up -d
sail composer setup
```

The app runs at http://localhost. Log in with the seeded account: `test@example.com` / `password`.

Horizon and Reverb aren't started by the commands above, instead run `sail artisan horizon` and
`sail artisan reverb:start` in a terminal.

## Terminology

The `Team` model represents a company in the backend; "company" is just the front-end name for a team and its
offerings, not a separate entity.

Reservations are never deleted; a canceled or expired reservation just changes status.

## What's left

- Reservation flow: create, confirm, cancel, and auto-expire holds (currently boilerplate, not implemented)
- Company statistics dashboard
- Platform administration (inviting users to create a company and then evaluating their applications)
- Test coverage for the reservation flow and other features that are currently not tested
