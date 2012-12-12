Visitors
=======================

An extension to remember authenticated visitors on your bolt.cm site

This extension uses <a href="http://hybridauth.sourceforge.net" target="_blank">hybridauth</a> for the actual authentication process

Installation
=======================
Copy `config.yml.dist` to `config.yml` in the same directory.

To enable a provider set the value `enabled: true` in the configuration and replace the example provider keys with real values.

See <a href="http://hybridauth.sourceforge.net/userguide.html" target="_blank">the hybrid auth userguide</a> for the basic configuration options and how to get the needed keys.

An example of the provider keys

	providers:
	  Google:
	    enabled: true
	    keys:
	      id: "*** your id here ***"
	      secret: "*** your secret here ***"

Usage
=======================
When installed visitors can login from 'http://example.com/visitors/login' using one of the configured authentication methods

Known issues
=======================
This extension needs a base template in the current theme called `base.twig` e.g.:

	{% include '_header.twig' %}

	<h1>
	    {{ title }}
	</h1>

	{{ markup }}

	{% include '_footer.twig' %}