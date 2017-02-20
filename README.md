# Redbox Docker

A "batteries not included" best practice development environment for Magento 2,
based on Docker.

Just the server setup, nothing about installing, or setting up. We leave that to
you.

## Installation

```bash
composer global config repositories.rd vcs https://github.com/maxbucknell/rd.git
composer global config "minimum-stability" "dev"
composer global config "prefer-stable" "true"
composer global require redbox/rd
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

Once it gives the all clear, run:

```bash
rd info
```

This will show you the IP addresses and status of all your containers.
Everything except the data containers should be running. Add the IP address of
the webserver to your `/etc/hosts` file.

Assuming that you've configured your base URLs correctly, you should be able to
visit it now. Have fun. Read the rest of this document to learn what else has
been done to make your life easier.

## Map Ports

By default, ports are not mapped to the host. This allows multiple projects to
run simultaneously. It is instead preferred to edit one's `etc/hosts` file to
look for the container's IP address directly.

If you want ports to be shared to the Docker host (perhaps you are running
Docker on a non-local machine.

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
*	Mailhog, for receiving emails.

You can find more information about them by following the links to the relevant
image pages on Docker Hub.

## Running Administrative Tasks

To run a command inside your Redbox Docker installation, run:

```bash
rd run -- <your command>
```

This command will create a container based on the
[`redboxdigital/docker-console`][console] image, inject it into the current
network, mount the volumes from the appserver, and execute that command as
a non-privileged user. This is ideal for things like Composer, or Magerun:

```bash
rd run -- n98-magerun setup:upgrade
rd run -- composer update
```

## Permissions Issues

If you have any issues with permissions, there is a command to fix that:

```bash
rd fix-permissions
```

This sets the owner to the host user, and the group to a custom group, `rd-www`,
with group ID `10118`. The appserver runs as this user, and has read permissions
to the installation by its group. It receives write permissions on `var/` and
`pub/media/`, and the sticky bits are set so that new files created have the
same group.

Because this changes permissions, it is highly recommended that Git is
configured to ignore permissions changes.


## Debian

There are some incompatibility issues with Alpine Linux. This is not a problem
if you update `zendframework/zend-stdlib` to `2.7.7`. If you don't want to do
this (you will need a version alias, because Magento requires an older version),
you can use Debian containers.

When you start your system, pass the `-d` flag, and pass the same flag to `rd
run` to use a Debian container for PHP.

## Updating

To update your global `rd` tool, run:

```bash
composer global update redbox/rd
```

To update the current `rd` project, simply re-run the initialization command:

```bash
rd init
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
`"docker"`, and the web server will intelligently route your request. Since
everything else is shared, this switch is totally transparent.

### Debugging Console Commands

To debug a console command, you must pass the `-x` flag to `rd run`. This will
run a container with Xdebug, and set the relevent environment variable to
trigger XDebug with an IDE key of `"docker"`.

## Running MySQL on Host

It is a common request to run MySQL on one's host. This can be for performance
reasons, or simply convenience. If you want to do this, you'll need to replace
your `env.php` database settings with this section:

```php
<?php

// ...
'db' => 
array (
  'connection' => 
  array (
    'indexer' => 
    array (
      'host' => `/sbin/ip route|awk '/default/ { print $3 }'`,
      'dbname' => '{DATABASE}',
      'username' => '{USER}',
      'password' => '{PASSWORD}',
      'model' => 'mysql4',
      'engine' => 'innodb',
      'initStatements' => 'SET NAMES utf8;',
      'active' => '1',
      'persistent' => NULL,
    ),
    'default' => 
    array (
      'host' => `/sbin/ip route|awk '/default/ { print $3 }'`,
      'dbname' => '{DATABASE}',
      'username' => '{USER}',
      'password' => '{PASSWORD}',
      'model' => 'mysql4',
      'engine' => 'innodb',
      'initStatements' => 'SET NAMES utf8;',
      'active' => '1',
    ),
  ),
  'table_prefix' => '',
),
// ...
```

This will dynamically set the Database host to **your** host IP according to the
Docker container.
