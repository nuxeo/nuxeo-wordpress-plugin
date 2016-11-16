# Nuxeo Wordpress plugin

The Nuxeo Wordpress plugin is a wordpress plugin for embedding Nuxeo documents in a wordpress site.

It uses the Nuxeo Automation API with the [Nuxeo Automation PHP client](http://github.com/nuxeo/nuxeo-automation-php-client).

# Installation

 * Download latest version from [Github releases](https://github.com/nuxeo/nuxeo-wordpress-plugin/releases)
 * Go to `/wp-admin/plugin-install.php?tab=upload` of your Wordpress site
 * Upload the `plugin-wp-nuxeo-VERSION.zip` (where VERSION depends on the version you actually downloaded)

# Usage

Once you have installed the plugin, you must activate it on the plugins page `/wp-admin/plugins.php`

Now you can configure how the Wordpress plugin will connect to you Nuxeo instance on the plugin configuration page `/wp-admin/options-general.php?page=basic-nuxeo-settings`

You need to setup at least the Nuxeo URL, path to the Nuxeo domain to use (e.g., `/default-domain`).

Depending on your Nuxeo configuration, you'll also need to setup the credentials to use.

You can also toggle the debug mode to show/hide the NXQL queries.

Once everything's up, you can use the Nuxeo button and enter either a path to a document, a type of document or an NXQL query to embed documents in your post.

# Code

## Requirements

 * [Composer](https://getcomposer.org/)

## Building

`composer install && zip -r plugin-wp-nuxeo-CUSTOM.tgz plugin-wp-nuxeo`

## Deploy (how to install build product)

 * Go to `/wp-admin/plugin-install.php?tab=upload` of your Wordpress site
 * Upload the `plugin-wp-nuxeo-CUSTOM.zip`

## Docker

We provide a [docker-compose.yml](https://github.com/nuxeo/nuxeo-wordpress-plugin/blob/master/docker-compose.yml) for quick testing

Just install docker-compose and run `docker-compose up`, you'll have a nuxeo running on http://localhost:9081/ and wordpress on http://localhost:9080/

There is also a Nginx SSL proxy to Nuxeo available if you need at http://localhost:9443/

# Contributing / Reporting issues

We are glad to welcome new developers on this initiative, and even simple usage feedback is great

 * Ask your questions on http://answers.nuxeo.com/
 * Report issues on this GitHub repository (see [issues link](https://github.com/nuxeo/nuxeo-wordpress-plugin/issues) on the right) 

# License

[Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0.html)

# About Nuxeo

The [Nuxeo Platform](http://www.nuxeo.com/products/content-management-platform/) is an open source customizable and extensible content management platform for building business applications. It provides the foundation for developing [document management](http://www.nuxeo.com/solutions/document-management/), [digital asset management](http://www.nuxeo.com/solutions/digital-asset-management/), [case management application](http://www.nuxeo.com/solutions/case-management/) and [knowledge management](http://www.nuxeo.com/solutions/advanced-knowledge-base/). You can easily add features using ready-to-use addons or by extending the platform using its extension point system.

The Nuxeo Platform is developed and supported by Nuxeo, with contributions from the community.

Nuxeo dramatically improves how content-based applications are built, managed and deployed, making customers more agile, innovative and successful. Nuxeo provides a next generation, enterprise ready platform for building traditional and cutting-edge content oriented applications. Combining a powerful application development environment with
SaaS-based tools and a modular architecture, the Nuxeo Platform and Products provide clear business value to some of the most recognizable brands including Verizon, Electronic Arts, Sharp, FICO, the U.S. Navy, and Boeing. Nuxeo is headquartered in New York and Paris.
More information is available at [www.nuxeo.com](http://www.nuxeo.com).