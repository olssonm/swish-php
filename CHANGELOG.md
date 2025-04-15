# Changelog

## v3.4 - 2025-04-15

This release fixes a major flaw in v3.3 where the service provider could throw an exception when not using a signing-certificate for payouts.

### What's Changed

* Issue when resolving "null"-paths by @olssonm in https://github.com/olssonm/swish-php/pull/20

**Full Changelog**: https://github.com/olssonm/swish-php/compare/v3.3...v3.4

## v3.3 - 2025-03-31

### What's Changed

* Load certificated paths using Laravel's filesystem by @vinkla in https://github.com/olssonm/swish-php/pull/18
* Add tests for the service provider by @olssonm in https://github.com/olssonm/swish-php/pull/19

**Full Changelog**: https://github.com/olssonm/swish-php/compare/v3.2...v3.3

## v3.2 - 2025-02-19

Added support for Laravel 12. Dropped support for Laravel 10 and PHP 8.2.

### What's Changed

* Laravel 12.x Compatibility by @olssonm in https://github.com/olssonm/swish-php/pull/17

**Full Changelog**: https://github.com/olssonm/swish-php/compare/v3.1...v3.2

## v3.1 - 2024-12-11

### What's Changed

* Support for PHP 8.4 by @olssonm in https://github.com/olssonm/swish-php/pull/16

**Full Changelog**: https://github.com/olssonm/swish-php/compare/v3.0...v3.1

## v3.0 - 2024-08-12

Adds the ability to perform payouts. v3.0 will in most (if not all) cases be backwards compatible with v2.2. However, please [review the upgrade guide](https://github.com/olssonm/swish-php/blob/main/UPGRADE.md) and the [changelog](https://github.com/olssonm/swish-php/blob/main/CHANGELOG.md) for other changes and updates.

### What's Changed

* Add payouts by @olssonm in https://github.com/olssonm/swish-php/pull/8
* Fix capitalization of FUNDING.yml by @olssonm in https://github.com/olssonm/swish-php/pull/10
* Update Laravel integration by @vinkla in https://github.com/olssonm/swish-php/pull/12
* Add SWISH_URL in config by @olssonm in https://github.com/olssonm/swish-php/pull/14

**Full Changelog**: https://github.com/olssonm/swish-php/compare/v2.2...v3.0

## v2.2 - 2024-06-20

### What's Changed

* Exclude non-essential files in production environments by @vinkla in https://github.com/olssonm/swish-php/pull/6

### New Contributors

* @vinkla made their first contribution in https://github.com/olssonm/swish-php/pull/6

**Full Changelog**: https://github.com/olssonm/swish-php/compare/v2.1...v2.2

## v2.1 - 2024-01-08

### What's Changed

* Adding support for phpstan by @pnx in https://github.com/olssonm/swish-php/pull/5

### New Contributors

* @pnx made their first contribution in https://github.com/olssonm/swish-php/pull/5

**Full Changelog**: https://github.com/olssonm/swish-php/compare/v2.0...v2.1

## v2.0 - 2023-05-25

Some rework to package to more easier load .pem-certificates instead of the .p12-variant.

This is a breaking update, however – migrating from v1.0 should take five minutes or less in most cases. Please [review the upgrade guide](https://github.com/olssonm/swish-php/blob/main/UPGRADE.md) for changes.

Support for PHP 7.4 and 8.0 has been dropped.

### What's Changed

- Downgrade to ubuntu 20.04 by @olssonm in https://github.com/olssonm/swish-php/pull/1
- Fix broken build-shield by @olssonm in https://github.com/olssonm/swish-php/pull/2
- Add php 8.2 to the test matrix by @olssonm in https://github.com/olssonm/swish-php/pull/3
- Improve handling of .pem-certificates by @olssonm in https://github.com/olssonm/swish-php/pull/4

### New Contributors

**Full Changelog**: https://github.com/olssonm/swish-php/compare/v1.0...v2.0

## v1.0 - 2022-06-30

### Added

- Initial Release
