---
name: wp-module-activation
title: Getting started
description: Prerequisites, install, and run.
updated: 2025-03-18
---

# Getting started

## Prerequisites

- **PHP** 7.3+.
- **Composer** and **WordPress** (for running the module and tests).

## Install

```bash
composer install
```

No production dependencies; dev deps include WordPress, Codeception, PHPCS.

## Run tests

```bash
composer run test
composer run test-coverage   # then open tests/_output/html/index.html
```

## Lint

```bash
composer run lint
composer run fix
```

## Using in a host plugin

1. Depend on `newfold-labs/wp-module-activation` via Composer.
2. Ensure the module’s `bootstrap.php` is loaded (via Composer autoload `files`) before or when the container is set.
3. When the host calls `container( $container )`, the action `newfold_container_set` runs and this module’s `Activation` and `Partners` run. No further configuration required.

See [integration.md](integration.md) for details.
