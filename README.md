# WP LDP
LDP Plugin for wordpress - Still under heavy construction

The goal of this project is to be able to manage (create, list, display) LDP resources directly from a Wordpress site backend.

# Architecture
This plugin defines a custom content type called ldp_resources, which allows users to create resources on the fly. It also adds a 'LDP Models' section in the Settings section, allowing users to define which models they would like to use to generate their resources (for now, a basic People model only having a name and description is used)

# The reason
Wordpress is a widely used CMS, especially in the non-profits world because it is free, open-source and coming with a really rich ecosystem. This plugin is part of the development of the AV Proof of concept we need to deliver in a near future.
