---
name: composer-install
description: "Use when the user wants to install a new Composer package (composer require). Reads the package documentation first, runs recommended post-install commands (publish config, run migrations, etc.), and verifies the installation."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file.
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Output in the same language as the user's request.
- All messages formatted as markdown.

**Trigger:**
- User asks to install, add, or require a Composer package.
- User runs `composer require vendor/package`.

**Steps:**

## 1. Read package documentation before installation

- **Packagist / Repository lookup**: Before running `composer require`, look up the package on Packagist or its source repository (GitHub/GitLab) to find installation instructions.
- **README / Installation guide**: Read the package README, INSTALL, or Getting Started documentation. Use the repository URL from Packagist metadata or the user-provided URL.
- **Identify post-install steps**: Extract all recommended post-install commands from the documentation, such as:
  - Publishing configuration files (`php artisan vendor:publish`)
  - Running database migrations (`php artisan migrate`)
  - Adding service providers or aliases (if not auto-discovered)
  - Generating assets or running build commands
  - Setting environment variables
  - Any other setup steps mentioned in the documentation

## 2. Install the package

- Run `composer require vendor/package` (with the version constraint if specified by the user).
- Verify the installation completed successfully (no conflicts or errors).
- If conflicts occur, report them clearly and suggest resolution steps.

## 3. Run recommended post-install commands

- Execute each post-install command identified in step 1, in the order recommended by the documentation.
- For Laravel packages, common post-install steps include:
  - `php artisan vendor:publish --provider="Vendor\\Package\\ServiceProvider"` — publish config, views, migrations, etc.
  - `php artisan migrate` — run new migrations if the package provides them.
- Skip steps that are not applicable to the current project setup.
- Report the result of each command.

## 4. Verify installation

- Run `composer show vendor/package` to confirm the installed version.
- Check that the package is properly registered (e.g., service provider auto-discovery for Laravel).
- If the package provides a test or health-check command, run it.

## 5. Summary

- Provide a brief summary of:
  - Installed package and version.
  - Post-install commands that were executed and their results.
  - Any manual steps the user still needs to complete.
  - Notable features or configuration options from the documentation.
