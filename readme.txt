=== WP-LDP ===
Contributors: balessan
Donate link: http://www.virtual-assembly.org/contribuez-financierement/
Tags: decentralization, federation, linked-data, LDP, RDF, JSON-LD
Requires at least: 4.4.0
Tested up to: 4.8
Stable tag: 4.8
Version: 2.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin aims to emulate the default caracteristics of a Linked Data Platform compatible server.

== Description ==
# LDP Plugin for wordpress
The goal of this project is to be able to manage (create, list, display) LDP resources directly from a Wordpress site backend.
The definition we apply for [LDP resource](https://www.w3.org/TR/ldp/#ldpr-resource), [LDP containers](https://www.w3.org/TR/ldp/#dfn-linked-data-platform-basic-container) and LDP are based on the [W3C specification released](https://www.w3.org/TR/ldp/ "the LDP specification") in january 2015.

# Architecture
This plugin defines a custom content type called ldp_resource, which allows users to create resources on the fly.
It also adds a custom taxonomy called ldp_container, having a metadata ldp_model being a simple JSON based model to use in accordance with your owns semantic ontologies.
It adds a section in the Settings menu, allowing users to initialize our default PAIR (Projects, Actors, Ideas, Resources) containers to generate their resources in this format.
The documentation regarding the PAIR ontology [can be find on Github](https://github.com/assemblee-virtuelle/pair/).

# The reason
Wordpress is a widely used CMS, especially in the non-profits world because it is free, open-source, solid and coming with a really rich ecosystem.
This plugin is part of the development of the AV Proof of concept we will to deliver in a near future.

# Documentation

For more information about installation, use, and features to come, please either see the Wiki or the issues list located:
- https://github.com/assemblee-virtuelle/wpldp/wiki for the wiki
- https://github.com/assemblee-virtuelle/wpldp/issues for the issues

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wpldp` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> WP-LDP Settings screen if you wish to initialize the PAIRs containers as defined above.

== Frequently Asked Questions ==

= Will that plugin be useful to me out of the box ? =

Outside of the context of willing to share some resources between different websites, as the LDP goal is, it won't be useful to you.

== Screenshots ==

1. The settings menu item
2. The settings page with the container initialization option
3. The containers menu item
4. The default containers listing page
5. The default actor JSON Model
6. The resources menu item
7. The new resource editing page
8. The newly created resource edition form
9. The created resource generated JSON raw view

== Changelog ==

= 2.0.0 =
* Full rewrite of the plugin to base it on the WP Rest API
* Adding autocomplete on resources selection
* Better handling of multiple values object properties
* Minimal search API
* Proper separation of concerns on the JS side

= 1.0.5 =
* Force refreshing the container models when initializing
* Fix the major bug with the form templating

= 1.0.4 =
* Updating the models to converge with the PAIR ontology
* Fix a bug with use of empty on a function return value
* Adding basic WordPress integrated frontend display of the resources and containers, using content negotiation

= 1.0 =
* Publishing the plugin to the wordpress repository

== Upgrade Notice ==

= 1.0 =
No upgrade notice yet
