# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v2.4.2] - 2025-03-20

### Fixed

- Make cache adapter compatible with older PSR cache adapter

## [v2.4.1] - 2025-02-24

### Fixed

- Removed version from composer.json

## [v2.4.0] - 2024-11-06

### Added

- A notice will appear on the settings page of the plugin if a newer version of the plugin is available.
- Added "Magento Order Increment ID" to payment metadata when creating the payment in Truelayer.
- PSR-16 cache adapter
- Automatically generate cache encryption key

### Changed

- Using constant filename new uploaded private keys
- Increased PHP dependency version
- Increased TrueLayer PHP Client dependency version

### Fixed

- Potential security issue when deleting a private key

## [v2.3.0] - 2024-09-27

### Added

- Release channel configuration option
- Fix settings visibility based on scopes

### Changed

- Stricter PHPStan rules

## [v2.2.0] - 2024-09-02

### Added

- Test validity of private key file when testing credentials.
- Store transaction ID in sales_order_payment table last_trans_id column.

### Fixed

- Check credentials button will correctly use unsaved values from the form fields.
- View logs button will show the end of the log file instead of the beginning.

## [v2.1.0] - 2024-07-17

### Added

- New option to hide or show payment method description

### Changed

- Improved UX for failed or canceled payments by redirecting users back to the checkout billing step
- More aggressive payment status checking on user return flow

### Fixed

- Payment method description not rendering on checkout page

## [v2.0.0] - 2024-06-19

### Added

- Payment metadata including store and order ID
- Support for handling failing refunds

### Changed

- Place orders upfront and update them throughout the payment lifecycle
- Improved logging

### Fixed

- Minicart cache busting
- Refund metadata being set to NULL
- Improved database indexes
- Improved idempotency for webhooks
- Payment creation failing when shipping address not required
- Issues duplicating Quotes

## [v1.0.10] - 2024-05-07

### Fixed

- Fix shipping method being dropped in some cases

## [v1.0.10] - 2024-04-12

### Changed

- Stop sending mobile number when creating payments

## [v1.0.9] - 2024-03-22

### Fixed

- Update version

## [v1.0.8] - 2024-03-18

### Fixed

- Replace jqXHR.success with jqXHR.done

## [v1.0.7] - 2024-03-14

### Fixed

- Payment totals not being rounded up, occasionally leading to a 1p difference

## [v1.0.6] - 2023-09-13

### Changed

- Wait for payment status updates on customer checkout
- Admin panel improvements and fixes
