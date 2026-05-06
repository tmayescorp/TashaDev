---
name: wp-module-activation
title: Development
description: Lint, test, and workflow.
updated: 2025-03-18
---

# Development

## Linting

- **PHP:** `composer run lint`, `composer run fix`. Uses `phpcs.xml` and `newfold-labs/wp-php-standards`.

## Testing

- **Codeception wpunit:** `composer run test`, `composer run test-coverage`. Open `tests/_output/html/index.html` for coverage.

## Workflow

1. Make changes in `includes/` or `bootstrap.php`.
2. Run `composer run lint` and `composer run test` before committing.
3. When adding or changing partners or hooks, update [integration.md](integration.md) and [overview.md](overview.md). When cutting a release, update **docs/changelog.md**.
