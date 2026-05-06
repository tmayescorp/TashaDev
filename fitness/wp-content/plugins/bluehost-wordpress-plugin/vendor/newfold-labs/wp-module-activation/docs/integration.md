---
name: wp-module-activation
title: Integration
description: How the module registers and integrates.
updated: 2025-03-18
---

# Integration

## How the module runs

The module does not register with the Newfold Module Loader as a toggleable module. It hooks into **`newfold_container_set`** (in `bootstrap.php`). When the host (or loader) sets the container, the callback runs:

```php
new Activation( $container );
```

`Activation` receives the container and instantiates `Partners( $container )`. So the host only needs to:

1. Include the package (Composer).
2. Set the container via `NewfoldLabs\WP\ModuleLoader\container( $container )` as usual. This module will run when that happens.

## Partners

The `Partners` class creates one instance of each partner integration (Akismet, CreativeMail, Jetpack, MonsterInsights, OptinMonster, WpForms, Yoast, WordPress) and calls `init()` on each. Those classes typically add activation hooks or filters for their respective plugins. See `includes/Partners/` for per-partner logic.

## Fresh install

`Partners` adds a filter on `plugins_loaded` (`is_fresh_install`) to detect fresh installs and run any one-time activation logic. The container is used to get the plugin file/basename for context.
