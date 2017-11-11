# LDP Plugin for wordpress

[![Build Status](https://travis-ci.org/assemblee-virtuelle/wp-ldp.svg?branch=master)](https://travis-ci.org/assemblee-virtuelle/wp-ldp) <a href="https://codeclimate.com/github/assemblee-virtuelle/wp-ldp"><img src="https://codeclimate.com/github/assemblee-virtuelle/wp-ldp/badges/gpa.svg" /></a> [![Coverage Status](https://coveralls.io/repos/github/assemblee-virtuelle/wp-ldp/badge.svg?branch=master)](https://coveralls.io/github/assemblee-virtuelle/wp-ldp?branch=master)

The goal of this project is to be able to manage (create, list, display) LDP resources directly from a Wordpress site backend.

# Architecture

## Content types

This plugin defines two custom content types:
- ldp_resource: allows users to create resources on the fly
- ldp_site: allows users and the site itself to manage a list of known websites in order to establish a federation

## Taxonomies

This plugin defines one custom taxonomy:
- ldp_container: corresponds to a LDP direct container accordingly to the official specs. It contains resources of a certain and unique type  

## Settings

It also adds a 'LDP Models' section in the Settings section, allowing users to define which models they would like to use to generate their resources (for now, a basic People model only having a name and description is used).

## API

The LDP specification basically being a REST API specification to serve semantic data, this plugin defines an API having a few endpoints available.

- /api/ldp/v1/schema : returns the schema of the LDP resources and containers available on the current site
- /api/ldp/v1/*container*/ : returns the list of resources associated with the current container
- /api/ldp/v1/*container*/*resource*/ : returns the whole definition of the current resource
- /api/ldp/v1/sites : returns the list of sites the current site knows, as part of the federating feature

Those are for the GET endpoints, support for POST, PUT, PATCH and DELETE are on their way.

# The reason

Wordpress is a widely used CMS, especially in the non-profits world because it is free, open-source and coming with a really rich ecosystem. This plugin is part of the development of the AV Proof of concept we need to deliver in a near future.

# Documentation

For more information about installation, use, and features to come, please either see the Wiki or the issues list located:
- https://github.com/assemblee-virtuelle/wpldp/wiki for the wiki
- https://github.com/assemblee-virtuelle/wpldp/issues for the issues
