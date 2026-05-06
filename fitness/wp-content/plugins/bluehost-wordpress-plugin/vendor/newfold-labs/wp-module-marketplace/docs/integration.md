---
name: wp-module-marketplace
title: Integration
description: How the module registers and integrates.
updated: 2025-03-18
---

# Integration

The module registers with the Newfold Module Loader via bootstrap.php. The host plugin typically registers a marketplace service and uses it to fetch/display Hiive marketplace products. Depends on wp-module-data. See [dependencies.md](dependencies.md).
