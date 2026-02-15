# AGENTS.md (Repository Operating Guide)

This repo is a Symfony 8 (PHP >= 8.4) application with Doctrine ORM and PHPUnit.
Frontend assets use Symfony AssetMapper + Importmap (no Node build pipeline).

## Ground Rules (Agents)
- Follow existing project conventions; keep changes minimal and consistent.
- Do not commit secrets. Do not add real credentials to `.env` or any committed file.
- Prefer Symfony/Doctrine best practices over “frameworkless” patterns.
- Keep generated/compiled artifacts out of commits unless the repo already tracks them.

## Quick Start
- Install PHP deps:
  - `composer install`
- Run the app locally (one of):
  - Symfony CLI (if installed): `symfony serve`
  - Built-in server: `php -S 127.0.0.1:8000 -t public`
- Run via containers:
  - `docker compose up --build -d`
  - App: `http://localhost:8080`

## Build / Dev Commands
There is no custom build script in `composer.json` beyond Symfony Flex auto-scripts.

- Clear cache:
  - `php bin/console cache:clear`
  - Prod-mode cache clear: `APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear`

### Assets (AssetMapper + Importmap)
- Inspect mapped assets:
  - `php bin/console debug:asset-map`
- Add JS deps to importmap:
  - `php bin/console importmap:require <package>`
- Install/importmap assets (Flex auto-script also runs on install/update):
  - `php bin/console importmap:install`
- Compile asset map (recommended for prod):
  - `php bin/console asset-map:compile`

Notes:
- `config/packages/asset_mapper.yaml` uses `missing_import_mode: strict` in non-prod.
  Missing imports should fail fast during development.

## Lint / Static Checks
No PHP-CS-Fixer/PHPStan/Psalm config is present in this repo. Use Symfony lints + PHP linting.

- PHP syntax lint (single file):
  - `php -l src/Foo.php`
- Symfony container sanity check:
  - `php bin/console lint:container`
- YAML config lint:
  - `php bin/console lint:yaml config --parse-tags`
- Twig template lint:
  - `php bin/console lint:twig templates`

If you add a new linting tool (php-cs-fixer/phpstan/psalm), also add:
- a config file in-repo
- a documented command here
- (ideally) a CI workflow

## Tests (PHPUnit 13)
Important: PHPUnit config file is `phpunit.dist.xml` (non-default name).
Always pass `-c phpunit.dist.xml` unless a `phpunit.xml(.dist)` is added later.

- Run all tests:
  - `./bin/phpunit -c phpunit.dist.xml`
- Run a single test file:
  - `./bin/phpunit -c phpunit.dist.xml tests/SomeTest.php`
- Run a single test method (filter by method name):
  - `./bin/phpunit -c phpunit.dist.xml --filter '/::testItDoesThing$/'`
- Run tests matching class/method substring:
  - `./bin/phpunit -c phpunit.dist.xml --filter SomeFeature`
- Debugging tips:
  - Add `--testdox` for readable output
  - Add `--debug` for verbose execution tracing

Test environment:
- `phpunit.dist.xml` forces `APP_ENV=test`
- `tests/bootstrap.php` boots `.env` when `Dotenv::bootEnv()` exists

## Code Style (PHP)
Baseline: PSR-12 + Symfony conventions, aligned with `.editorconfig`:
- UTF-8, LF endings, 4-space indents, final newline, trim trailing whitespace.

### File Structure
- No closing `?>` tag in PHP files.
- One class per file (except tiny value objects/enums if clearly justified).
- Namespace matches directory (`App\\...` => `src/...`), PSR-4 already configured.

### Imports (`use ...`)
- Prefer explicit imports (no fully-qualified names in-line unless clarifying).
- Keep imports sorted and grouped:
  1) `Symfony\\...`, `Doctrine\\...`, other vendor
  2) `App\\...`
- Remove unused imports.

### Types
- Prefer strict, explicit types:
  - Typed properties
  - Return types on all methods (including `void`)
  - Narrow parameter types
- Use PHPDoc only when PHP types cannot express intent:
  - generic arrays (`array<string, Foo>`)
  - complex shapes
  - templated collections
- If introducing `declare(strict_types=1);`, apply consistently within a new module/area;
  avoid mixing strategies in an existing file unless you verified no behavior changes.

### Naming
- Classes/Enums: `PascalCase`
- Methods/vars: `camelCase`
- Constants: `UPPER_SNAKE_CASE`
- Symfony services: let autowiring/autoconfigure handle IDs; avoid custom string IDs unless needed.
- Doctrine entities: singular nouns (e.g., `User`, `InvoiceLine`).

### Error Handling
- Prefer exceptions over sentinel return values.
- Use Symfony HTTP exceptions in controllers (`NotFoundHttpException`, `AccessDeniedHttpException`, etc.).
- Log actionable context:
  - use Monolog via `Psr\\Log\\LoggerInterface`
  - do not log secrets/PII; redact tokens/credentials

## Doctrine / Database
- ORM mapping uses PHP attributes (`type: attribute`), entity path: `src/Entity`.
- Prefer:
  - explicit column types when ambiguity matters
  - immutable date/time types where possible
  - repositories for query logic, services for orchestration/business rules

Common commands (if DB is configured/running):
- Create DB:
  - `php bin/console doctrine:database:create`
- Migrations:
  - `php bin/console doctrine:migrations:diff`
  - `php bin/console doctrine:migrations:migrate`

## Twig / Templates
- Base layout: `templates/base.html.twig` includes `{{ importmap('app') }}`.
- Keep templates:
  - logic-light (formatting/composition, not business rules)
  - escaping-safe (rely on Twig auto-escape; use `|raw` only with trusted content)

## Frontend (Importmap + Stimulus + Turbo)
- JS is ESM modules (import syntax). No bundler assumptions.
- Stimulus controllers live in `assets/controllers/*_controller.js`.
- Keep browser compatibility in mind (modern, but avoid Node-only APIs).

## Docker Notes
- App image: root `Dockerfile` builds dependencies using `composer:lts` then copies into `php:8.4-fpm-alpine`.
- Build arg `APP_ENV` controls whether dev deps are installed; compose passes `APP_ENV` to build and runtime.
- `DATABASE_URL` in `compose.yaml` points to the `database` service (not `127.0.0.1`).
- Nginx image: `docker/nginx/Dockerfile` serves `public/` and proxies PHP to `127.0.0.1:9000`.
- Healthcheck uses `/api/health` via `curl` in nginx container.
- ACR build task YAMLs exist under `docker/**/acr-build-task.yml` for Azure Container Registry Tasks.

## Repository-Specific Notes
- The app is container-first; code is baked into images (no bind mounts by default).
- `APP_ENV=dev` requires dev deps; ensure `APP_ENV` build arg matches runtime env.
- Basic health endpoint: `GET /api/health` returns `{ "status": "ok" }`.
- PDO CRUD endpoints:
  - `GET /api/items` supports `page` and `per_page` (default 100, max 1000) and returns `{ data, meta }`.
  - `POST /api/items` accepts `{ "name": "..." }`.
  - `GET/PUT/DELETE /api/items/{id}`.

## Cursor / Copilot Rules
- No Cursor rules found in `.cursor/rules/` or `.cursorrules`.
- No Copilot rules found in `.github/copilot-instructions.md`.
If rules are added later, update this document with the required constraints.
