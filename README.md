# Redbox Docker

A "batteries not included" best practice development environment for Magento 2,
based on Docker.

Just the server setup, nothing about installing, or setting up. We leave that to
you.

## Installation

```bash
composer require -g redboxdigital/rd
rd info
```

## Usage

To create a new Magento environment for an existing project, switch to the root
directory of your Magento 2 project, and do this:

```bash
rd init
```

This command initialises a Docker environment in your working directory. You can
start your servers by running:

```bash
rd start
```

You should get some information about your environment, including the IP address
and port number at which the webserver is running.

Assuming that you've configured your base URLs correctly, you should be able to
visit it now. Have fun. Read the rest of this document to learn what else has
been done to make your life easier.

## What's Inside

When you run `rd init`, it creates a directory in your project root called
`.rd/`, in much the same way that `git init` creates a directory called `.git/`.
We recommend that you check this into your project. It's small, and it's a good
idea to share local environments. However, if you don't want to, you can either
add it to your project's `.gitignore`, or (even better) a global `.gitignore`.

You can have a look in here, but it's mostly a set of Docker Compose
configuration files, and a data directory for MySQL.

## Containers

Redbox Docker tries to be a best practice environment for Magento 2 development.
In particular, we have containers for:

*	Nginx
*	PHP 7.0 (with all required extension)
*	Percona 5.6
*	Redis (for cache and session backend)
*	RabbitMQ (with management interface)
*	Mailcatcher, for receiving emails.

You can find more information about them by following the links to the relevant
image pages on Docker Hub.

## Running Administrative Tasks

## Updating

To update your global `rd` tool, run:

```bash
composer update -g redboxdigital/rd
```

To update the current `rd` project, run:

```bash
rd update
```

This will erase any changes you've made to the Docker Compose configuration
files. If you have any changes you want to retain, then you shouldn't update.

## Debugging

There are two app servers running PHP-FPM, both largely identical. Where they
differ is that one of them has Xdebug installed.

These two containers are named `appserver` and `appserver_debug` respectively.
You should switch to use that container if you need to debug. Having no Xdebug
installed on the container used most of the time saves you from the performance
penalty associated with Xdebug.

Switching is very easy. Simply send the Xdebug cookie with an IDE key of
`docker`, and the web server will intelligently route your request. Since
everything else is shared, this switch is totally transparent.

## Running MySQL on Host

It is a common request to run MySQL on one's host. This can be for performance
reasons, or simply convenience. If you want to do this, you'll need to replace
your `env.php` database settings with this section:

```php
<?php

// ...

[
    // Code to pull host IP address
]
```

