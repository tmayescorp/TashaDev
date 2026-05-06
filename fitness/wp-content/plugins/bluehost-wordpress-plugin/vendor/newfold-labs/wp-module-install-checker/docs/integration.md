---
name: wp-module-install-checker
title: Integration
description: How the module registers and integrates.
updated: 2025-03-18
---

# Integration

The module registers with the Newfold Module Loader via bootstrap.php. Other modules (e.g. wp-module-onboarding-data) use it to determine fresh install status and installation date.
