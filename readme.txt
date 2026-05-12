=== PurpleBox Storage ===
Contributors: purplebox
Tags: storage, units, tenants, contracts, management
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 3.2.0
License: GPLv2 or later

Self-storage unit and tenant management for WordPress.

== Description ==

PurpleBox Storage is a complete self-storage facility management plugin for WordPress. Manage your storage units, tenants, and rental contracts directly from the WordPress admin dashboard.

**Features:**

* Dashboard with occupancy stats and availability overview
* Storage unit inventory with size categories, features, and status tracking
* Tenant directory with Emirates ID tracking and contact details
* Contract creation wizard with PDF upload support
* Bulk actions for unit status management
* Filterable, searchable list tables
* Clean WordPress admin integration

== Installation ==

1. Upload the `purplebox-plugin` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the 'PurpleBox' menu in the admin sidebar

== Changelog ==

= 3.2.0 =
* Manual unit status override (mark units as rented/booked without a contract)
* Full contract editing (tenant and units now editable)
* Reorganized contract detail page with grid layout
* One-time Excel inventory import button
* Dashboard and reports respect manual status

= 2.2.1 =
* Added filled agreement PDF download from contract detail
* Added per-unit cancellation for multi-unit contracts
* Added visible version footer on contract detail

= 1.0.0 =
* Initial release
* Dashboard with unit stats and availability bars
* Storage unit management (CRUD)
* Tenant management (CRUD)
* Contract creation wizard
