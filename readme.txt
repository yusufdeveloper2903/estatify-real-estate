=== Estatify ===
Contributors: yusufyuldashev
Tags: real estate, listings, property, gutenberg blocks, map
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Real-estate listings for block themes: property post type, filterable REST API and blocks for search, grid, map, mortgage calculator and property details.

== Description ==

Estatify is a demo / portfolio plugin. It adds:

* A `property` post type with type, location and feature taxonomies.
* Typed post meta (price, bedrooms, bathrooms, area, address, coordinates, status, gallery) edited in a block-editor sidebar panel.
* A REST endpoint, `GET /wp-json/estatify/v1/properties`, with filtering, sorting and pagination.
* Five blocks: Property Search, Property Grid, Property Map (Leaflet + OpenStreetMap), Mortgage Calculator and Property Details.
* Block templates for single properties and property archives.
* A settings page for currency, area unit and map/mortgage defaults.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/estatify` (the compiled `build/` folder is included).
2. Activate it, then add the Estatify blocks to any page.
3. Optional: run `wp estatify seed` to import the Lisbon demo listings.

== Changelog ==

= 1.0.0 =
* First release.
