# v3.1.1
## 02-06-2021

1. [](#bugfix)
    * Link to docs

# v3.1.0
## 10-04-2021

1. [](#improved)
    * API-alignment to use `Grav\Common\Page\Interfaces\PageInterface`, transparently
    * Code cleanup
2. [](#new)
    * Revert content index-location to `user://data/persist`
3. [](#bugfix)
    * Correct key in indexed metadata

# v3.0.0
## 07-03-2021

1. [](#new)
    * Compatibility with Grav Core 1.7
    * Separate buttons and tasks for Index and Content in Admin
2. [](#improved)
    * Stream-resolution in FileStorage Adapter
    * Code-quality
3. [](#bugfix)
    * Method-alignment
    * Method-fallback
    * Exception-catching

# v2.1.3
## 26-09-2020

1. [](#bugfix)
    * Handle route-prefix more gracefully

# v2.1.2
## 24-09-2020

1. [](#bugfix)
    * Do not strip route-prefix

# v2.1.1
## 24-09-2020

1. [](#improved)
    * Media routes sanitizing
2. [](#bugfix)
    * Canonical URLs

# v2.1.0
## 22-09-2020

1. [](#bugfix)
    * Do not end copyMedia() prematurely
    * Avoid double-slash prefix

# v2.0.0
## 14-03-2020

1. [](#new)
    * Assets-prefix option
2. [](#bugfix)
    * CommandLineCollection-initialization
3. [](#improved)
    * Version-constraint

# v2.0.0-beta.1
## 06-03-2020

1. [](#improved)
    * Grav-initialization
    * API-cleanup
    * Asset-parsing and -rewriting
2. [](#new)
    * Offline-option to avoid trying to download remote assets
3. [](#bugfix)
    * Lock version-dependency to Core v1.6.22
    * Revert blueprints-logic
    * Fix permissions-selectize field

# v2.0.0-alpha.3
## 11-02-2020

1. [](#new)
    * Customizable permissions for Quick Tray, Generation-buttons
    * Parameter-handling for Config and Twig
    * Deprecate Preset-page in Admin
2. [](#improved)
    * Command-preview in Admin
    * Admin tab-order
    * README
    * Blueprints

# v2.0.0-alpha.2
## 06-02-2020

1. [](#improved)
    * Admin-preview
    * Configuration
    * Blueprints
2. [](#new)
    * Customizable permissions for Quick Tray, Generation-buttons
    * Deprecate Preset-page in Admin

# v2.0.0-alpha.1
## 01-02-2020

1. [](#new)
    * API-refactor
    * Presets
2. [](#improved)
    * Blueprints
    * Asset- and media-handling
    * Script in Admin
3. [](#bugfix)
    * Blueprints & CLI (@klonfish)

# v1.0.1
## 15-02-2020

1. [](#bugfix)
    * Target-selection (@klonfish, #1 and #2)

# v1.0.0
## 30-11-2019

1. [](#new)
    * Initial public release
