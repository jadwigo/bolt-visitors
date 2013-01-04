Visitors
=======================

An extension to remember authenticated visitors on your bolt.cm site

This extension uses <a href="http://hybridauth.sourceforge.net" target="_blank">hybridauth</a> for the actual authentication process

Installation
=======================
Download and extract the extension to a directory called Visitors in your Bolt extension directory.

Create the database tables manually by using the queries below.

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

You can also use the following functions and snippets in your templates:

*Looking up a user*

The function `{{ knownvisitor() }}` loads the current visitor - if any, and returns `false` when no known visitor is recognized.

After this function the following variables are available too:

    {{ visitor.username }}
    {{ visitor.id }}

*Login page*

The function `{{ showvisitorlogin() }}` shows links to all enabled login providers. After authentication a user is redirected to the homepage.

*Logout link*

The function `{{ showvisitorlogout() }}` shows a link to the logout page. After logging out a user is redirected to the homepage.

*Profile* 

The function `{{ showvisitorprofile() }}` shows the username and a logout link at the moment.

*Template example*

If you want to use this in a template, you can use the following code:

    {% if knownvisitor() %}
        <p>Hello, {{ visitor.username }}.</p>
        {{ showvisitorlogout() }}
    {% else %}
        {{ showvisitorlogin() }}
    {% endif %}



Known issues
=======================
This extension needs a base template in the current theme called `base.twig` e.g.:

    {% include '_header.twig' %}

    <h1>
        {{ title }}
    </h1>

    {{ markup }}

    {% include '_footer.twig' %}

Database
=======================

You need to manually create the db tables.

For Mysql:

    CREATE TABLE IF NOT EXISTS `bolt_sessions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `sessiontoken` varchar(255) NOT NULL,
      `lastseen` datetime NOT NULL,
      `visitor_id` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

    CREATE TABLE IF NOT EXISTS `bolt_visitors` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(255) DEFAULT NULL,
      `provider` varchar(255) DEFAULT NULL,
      `providerdata` text,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

For SQLite:

    CREATE TABLE 'bolt_visitors' ('id' INTEGER PRIMARY KEY NOT NULL, 'username' VARCHAR(64), 'provider' VARCHAR(64), 'providerdata' TEXT);
    CREATE TABLE 'bolt_sessions' ('id' INTEGER PRIMARY KEY NOT NULL, 'sessiontoken' VARCHAR(64), 'lastseen' DATETIME, 'visitor_id' INTEGER);

Editing SQLite databases is relatively easy with a tool like phpLiteAdmin ( <a href="http://phpliteadmin.googlecode.com">http://phpliteadmin.googlecode.com</a> )
