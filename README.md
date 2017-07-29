# LDP Plugin for wordpress

[![Build Status](https://travis-ci.org/assemblee-virtuelle/wp-ldp.svg?branch=master)](https://travis-ci.org/assemblee-virtuelle/wp-ldp) <a href="https://codeclimate.com/github/assemblee-virtuelle/wp-ldp"><img src="https://codeclimate.com/github/assemblee-virtuelle/wp-ldp/badges/gpa.svg" /></a>

The goal of this project is to be able to manage (create, list, display) LDP resources directly from a Wordpress site backend.

# Architecture
This plugin defines a custom content type called ldp_resources, which allows users to create resources on the fly. It also adds a 'LDP Models' section in the Settings section, allowing users to define which models they would like to use to generate their resources (for now, a basic People model only having a name and description is used).

# The reason
Wordpress is a widely used CMS, especially in the non-profits world because it is free, open-source and coming with a really rich ecosystem. This plugin is part of the development of the AV Proof of concept we need to deliver in a near future.

# Documentation

For more information about installation, use, and features to come, please either see the Wiki or the issues list located:
- https://github.com/assemblee-virtuelle/wpldp/wiki for the wiki
- https://github.com/assemblee-virtuelle/wpldp/issues for the issues
