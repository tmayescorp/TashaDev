---
name: wp-module-activation
title: Overview
description: What the module does and who maintains it.
updated: 2025-03-18
---

# Overview

## What the module does

**wp-module-activation** handles activation-related logic for Newfold WordPress brand plugins. When the host sets the container (`newfold_container_set`), this module runs and:

- **Activation** – Instantiates an `Activation` class that receives the container.
- **Partners** – Instantiates `Partners`, which initializes partner integrations (Akismet, CreativeMail, Jetpack, MonsterInsights, OptinMonster, WpForms, Yoast, WordPress). Each partner’s `init()` runs to register activation or post-activation behavior.
- **Fresh install** – A `plugins_loaded` filter is used to detect fresh installs and run any one-time logic.

The module does not register itself with the loader as a toggleable module; it runs when the container is set, so the host must include it as a Composer dependency and load it (e.g. via autoload) before or when the container is set.

## Who maintains it

- **Newfold Labs** (Newfold Digital). Distributed via Newfold Satis.
