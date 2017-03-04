# Redbox Docker

A "batteries not included" best practice development environment for Magento 2,
based on Docker.

By this, we mean that this only contains the server configuration, and does no
magic around installing Magento. We found that we needed a best practice
development environment for projects we already had. As such, Redbox Docker is
not picky about how your projects are configured. You just need to set it up in
the root of your project.

Redbox Docker is a very thin wrapper around Docker Compose.

## Installation

### Dependencies

*	[Docker][install-docker]
*	[Composer][install-composer] (and PHP)

Install via Composer! (Packagist entry is coming)

```bash
composer global config repositories.rd vcs https://github.com/maxbucknell/rd.git
composer global config "minimum-stability" "dev"
composer global config "prefer-stable" "true"
composer global require redbox/rd
```

## Getting Started

This will walk you through setting up a clean installation of Magento. To use
Redbox Docker on an existing project, see the section below on that topic.

First of all, create a Magento 2 project

```bash
composer create-project \
  --repository-url=https://repo.magento.com/ \
  magento/project-community-edition:~2.1.0 \
  --no-install \
  test-rd
```

This creates a Magento 2 base project in the `test-rd` directory. Switch to it,
and set up Redbox Docker:

```bash
rd init
rd start
```

Now we can begin to install Magento. First step is the dependencies:

```bash
rd run -- composer install
```

Now is probably a good time to make sure the permissions are correct:

```bash
rd fix-permissions
```

And make sure the database exists:

```bash
rd run -- mysql -hmagento_database -uroot -proot
MySQL [(none)]> create database showoff;
```

And then the installation. The following command will do it all, but feel free
to change anything if you need to.

```bash
rd run -- n98-magerun2 setup:install \
  --backend-frontname="admin" \
  --db-host="magento_database" \
  --db-name="showoff" \
  --db-user="root" \
  --db-password="root" \
  --base-url="http://test-rd.loc/" \
  --language="en_US" \
  --timezone="UTC" \
  --currency="EUR" \
  --admin-user="redbox.digital" \
  --admin-password="password123" \
  --admin-email="redbox.digital@example.com" \
  --admin-firstname="Redbox" \
  --admin-lastname="Digital"
```

If you are running Enterprise, you can configure the Message Queue framework
with these arguments:

```
  --amqp-host="magento_messagequeue" \
  --amqp-port="5672" \
  --amqp-user="guest" \
  --amqp-password="guest" \
  --amqp-virtualhost="/" \
```

Now there are some final setup tasks, which are optional but encouraged to get
a quick environment:

```bash
rd run -d -- n98-magerun2 setup:static-content:deploy
rd run -- n98-magerun2 cache:flush
rd run -- n98-magerun2 setup:di:compile
```

After this, your Magento 2 installation should be ready to use, but you won't
be able to access it. To do this, run `rd info`, and copy the IP address of the
`webserver` container. Add an entry to your hosts file:

```
<ip_address> test-rd.loc
```

If you visit `test-rd.loc/` in your browser, you should see the home page. Log
into the admin with `redbox.digital` and `password123`.

To see more details and available options, please check out the rest of the
documentation.

```bash
rd init
rd start
rd info
# Edit your /etc/hosts file to point your base URL to the IP address of the webserver
# Edit your app/etc/env.php to point your database at `magento_database`.
rd run -- n98-magerun2 db:import <path/to/database.sql>
```

## Command Reference

### `rd init`

Bootstrap a new Redbox Docker project, or update an existing one.

This command should be run from the root of your Magento 2 installation.


### `rd start`

Start the Redbox Docker environment, and make it ready for use.

This command searches up its directory tree for a Redbox Docker environment, so
can be run from any directory inside your Magento 2 installation.

#### Options

| Flag                 | Description |
| :------------------- | :---------- |
| `-p`, `--map-ports`  | Map ports of Redbox Docker environment to host. By default, this does not happen, and Redbox Docker containers are accessible only from their IP addresses. Use this if you don't like editing your hosts file, or if you are running Docker on a remote machine, including a Mac or Windows computer. |
| `-d`, `--use-debian` | Redbox Docker uses Alpine Linux by default. There are some compatibility issues. If you don't want to think about this, use Debian. |

### `rd info`

Print some useful information about the Redbox Docker environment.

This command searches up its directory tree for a Redbox Docker environment, so
can be run from any directory inside your Magento 2 installation.

### `rd run`

Run a command inside the Redbox Docker environment.

There are many administrative tasks that require integration with the Magento
installation. This includes (but is not limited to):

*	Running a database dump.
*	Running a cron command to debug.
*	Installing dependencies via Composer.

You can do this by `exec`ing a command inside the PHP container, but it is not
the best idea. That will force you to install various command line tools inside
the app container.

`rd run` circumvents this by creating a new container, and injecting it into the
Redbox Docker environment's network, and mounting the volumes from the
appserver. It then runs an arbitrary command, and removes itself.

The container used is `redboxdigital/docker-console`, and has an identical PHP
configuration to the appservers. Along with that, it also has a variety of
useful tools preinstalled, including:

*	MySQL client
*	Redis client
*	Composer
*	N98-Magerun2
*	Git
*	Curl
*	Vim

A lot of configuration is also passed from your host to the container. In
particular, Git configuration (including aliases), as well as SSH sockets, and
Composer caches.

If you are running a command that takes flags as arguments, these flags will be
interpreted by the `rd` utility. To avoid this, you can quote your command, or
place your command after a `--`, like so:

```bash
# Prints the Magerun help, not rd help
rd run -- n98-magerun2 --help
```

See the page of that image for more details.

#### Options

| Flag                 | Description |
| :------------------- | :---------- |
| `-x`, `--debug`      | Run the container with Xdebug installed and enabled. If you want to debug a cron task, this is the way to do it. |
| `-d`, `--use-debian` | Redbox Docker uses Alpine Linux by default. There are some compatibility issues. If you don't want to think about this, use Debian. |

### `rd fix-permissions`

Set permissions to something approaching correct for a Magento 2 installation.

By correct, we mean the following:

*	All files and folders are owned by the host user (that's you!)
*	All files and folders have the group `rd-www (10118)
*	All files have permissions `744` (`rwxr--r--`)
*	All folders have permissions `755` (`rwxr-xr-x`)
*	All folders are set to "sticky", which means that new files created
within them inherit the group and permissions settings.
*	`var/` and `pub/` have write permissions on group.
*	`bin/magento` is made executable.

This command can take a while to run, since it touches a lot of files. If you
have `core.fileMode` set to `true` in your Git configuration, this can generate
changes on your files. You can either commit these (they're good permissions,
brent), or you can tell Git not to track permissions.

### `rd stop`

Stop the Redbox Docker environment, as if you turned off your servers.

## Configuration for Existing Environments

If you are setting up an existing Magento 2 installation with Redbox Docker, the
only thing you need to change is `env.php`.

Start off by running `rd init` in the project root, and then `rd start`. You
will need to add a hosts entry as per the Getting Started instructions. Then set
the `env.php` parameters correctly.

The following `env.php` should work on pretty much all Redbox Docker
installations. Feel free to copy verbatim, or take the relevant parts.

Once done, the database will need to be imported. Create the database first,
with `rd run -- n98-magerun2 db:create`, and then import from a dump with:

```bash
# path/to/db.sql needs to be within your project root or Docker will not find it.
rd run -- n98-magerun2 db:import path/to/db.sql
```

A quick cache flush with `rd run -- n98-magerun2 c:f` and you should be good to
go.

>	Note: that we have queue configuration in here, for the Enterprise
Message Queue framework. If you are using Community edition, you remove it, but
it's probably not going to cause any damage by remaining.

```php
<?php
return array (
  'backend' =>
  array (
    'frontName' => 'admin',
  ),
  'queue' =>
  array (
    'amqp' =>
    array (
      'host' => 'magento_messagequeue',
      'port' => '5672',
      'user' => 'guest',
      'password' => 'guest',
      'virtualhost' => '/',
      'ssl' => '0',
    ),
  ),
  'db' =>
  array (
    'connection' =>
    array (
      'indexer' =>
      array (
        'host' => 'magento_database',
        'dbname' => 'magento2',
        'username' => 'root',
        'password' => 'root',
        'model' => 'mysql4',
        'engine' => 'innodb',
        'initStatements' => 'SET NAMES utf8;',
        'active' => '1',
        'persistent' => NULL,
      ),
      'default' =>
      array (
        'host' => 'magento_database',
        'dbname' => 'magento2',
        'username' => 'root',
        'password' => 'root',
        'model' => 'mysql4',
        'engine' => 'innodb',
        'initStatements' => 'SET NAMES utf8;',
        'active' => '1',
      ),
    ),
    'table_prefix' => '',
  ),
  'install' =>
  array (
    'date' => 'Fri, 17 Jul 2015 16:38:39 +0100',
  ),
  'crypt' =>
  array (
    'key' => '220d5f683d141d326d3e185f0dcd569c',
  ),
  'session' =>
  array (
    'save' => 'redis',
    'redis' =>
    array (
      'host' => 'magento_session',
      'port' => '6379',
      'password' => '',
      'timeout' => '2.5',
      'persistent_identifier' => '',
      'database' => '1',
      'compression_threshold' => '2048',
      'compression_library' => 'gzip',
      'log_level' => '1',
      'max_concurrency' => '6',
      'break_after_frontend' => '5',
      'break_after_adminhtml' => '30',
      'first_lifetime' => '600',
      'bot_first_lifetime' => '60',
      'bot_lifetime' => '7200',
      'disable_locking' => '0',
      'min_lifetime' => '60',
      'max_lifetime' => '2592000',
    ),
  ),
  'resource' =>
  array (
    'default_setup' =>
    array (
      'connection' => 'default',
    ),
  ),
  'x-frame-options' => 'SAMEORIGIN',
  'MAGE_MODE' => 'developer',
  'cache_types' =>
  array (
    'config' => 1,
    'layout' => 1,
    'block_html' => 1,
    'collections' => 1,
    'reflection' => 1,
    'db_ddl' => 1,
    'eav' => 1,
    'full_page' => 1,
    'config_integration' => 1,
    'config_integration_api' => 1,
    'target_rule' => 1,
    'translate' => 1,
    'config_webservice' => 1,
    'compiled_config' => 1,
  ),
  'cache' =>
  array (
    'frontend' =>
    array (
      'default' =>
      array (
        'backend' => 'Cm_Cache_Backend_Redis',
        'backend_options' =>
        array (
          'server' => 'magento_cache',
          'port' => '6379',
          'persistent' => '',
          'database' => 0,
          'password' => '',
          'force_standalone' => 0,
          'connect_retries' => 1,
        ),
      ),
    ),
    'page_cache' =>
    array (
      'backend' => 'Cm_Cache_Backend_Redis',
      'backend_options' =>
      array (
        'server' => 'magento_fpc',
        'port' => '6379',
        'database' => 2,
        'compress_data' => '0',
      ),
    ),
  ),
);
```

## Common Tasks

### Debugging

There are two app servers running PHP-FPM, both largely identical. Where they
differ is that one of them has Xdebug installed.

These two containers are named `appserver` and `appserver_debug` respectively.
You should switch to use that container if you need to debug. Having no Xdebug
installed on the container used most of the time saves you from the performance
penalty associated with Xdebug.

Switching is very easy. Simply send the Xdebug cookie with an IDE key of
`"docker"`, and the web server will intelligently route your request. Since
everything else is shared, this switch is totally transparent.

#### Debugging Console Commands

To debug a console command, you must pass the `-x` flag to `rd run`. This will
run a container with Xdebug, and set the relevent environment variable to
trigger XDebug with an IDE key of `"docker"`.

### Run MySQL on Host

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

### Mail

It is common to want to test emails locally. Redbox Docker comes with MailHog,
which catches all emails that are sent to any email address, and displays them
in a web interface.

`rd info` will show you the IP address of this container. You can either hit
that up directly, or add a subdomain for it in your hosts file. If you are using
Redbox Docker with the ports mapped, this dashboard is mounted at port `:1080`.

In addition to this, all emails are saved in the `.rd/mail` volume, as a record
and as a convenient way of checking sources directly.

## Technical Details

### `.rd` Directory

When you run `rd init`, it creates a directory at the root of your Magento
2 installation. This contains some volumes, the various configuration files for
Docker Compose, and some server configuration files.

It is recommended to check this folder into your project (it comes with
a `.gitignore`). This allows the developers in a team to maintain a consistent
and shared development environment.

While it's possible to edit these files (perhaps your MySQL configuration needs
to be tweaked because you have unusual demands), please bear in mind that
updating the Redbox Docker installation by re-running `rd init` will not respect
your changes.

### Container Inventory

Redbox Docker comes with a variety of containers, and they all do different
things. Here is a full list:

*	`magento_webserver` (`nginx:alpine`): The container your browser will visit.
*	`magento_appserver` (`redboxdigital/php:7.0`): Usually the container running Magento.
*	`magento_appserver_debug` (`redboxdigital/php:7.0-xdebug`): Like `magento_appserver`, but with Xdebug.
*	`magento_database` (`percona:5.6`): The database.
*	`magento_cache` (`redis:alpine`): Cache backend.
*	`magento_fpc` (`redis:alpine`): FPC storage backend.
*	`magento_session` (`redis:alpine`): Session storage backend.
*	`magento_mail` (`mailhog/mailhog`): SMTP server to catch emails.
*	`magento_messagequeue` (`rabbitmq:management-alpine`): Message queue backend.

There are also two data-only containers, used for mounting volumes:

*	`magento_appdata` (`alpine`): Volume for Magento installation.
*	`magento_databasedata` (`alpine`): Volume for MySQL data.

### Docker Compose Files

Redbox Docker is split into many different YAML files. This allows the
environment to be started in a variety of different configurations. For example,
the port mappings are configured in a different file (`ports.yml`), so the
environment can be started with or without port mappings.

*	`base.yml`: Base YAML file, containing services that would be essential for production. No volumes exist here.
*	`dev.yml`: Services required for development, including `magento_appserver_debug` and `magento_mail`.
*	`appvolumes.yml`: `magento_appdata` plus `volumes_from` directives.
*	`dbvolumes.yml`: `magento_databasedata` plus `volumes_from` directives.
*	`debian.yml`: Switches `magento_appserver` to use a Debian based image.
*	`debian-dev.yml`: Switches `magento_appserver_debug` to use a Debian based image.
*	`ports.yml`: Map ports `:80`, `:443`, `:3306`, `:15672` (RabbitMQ), and `:1080` (Mailhog).

### Project Name

Docker Compose groups services by a "project". A project is usually the set of
services started by a single invocation of `docker-compose up`. A "project"
needs to have a name, which can be specified by the `-p` parameter, but will be
be generated from the name of the directory containing the `*.yml` file by
default.

Redbox Docker sets a custom name for its project based on the name of the
directory of the Magento project. If left to its own devices, Docker Compose 

### Permissions

By default, Docker runs as the root user. This is fine, because it normally
means that it's allowed to do whatever it needs to do. You run into trouble when
dealing with files that are generated by the Docker container, both in
committing them, and deleting them.

A simple `rm -rf pub/static` becomes problematic.

Because of this, Redbox Docker has all of its servers configured to run as
a non-privileged user. The actual user ID does not matter to Redbox Docker,
because all files should be owned by your host user. Redbox Docker uses group
permissions to do its thing.

Nginx runs as its typical user, `nginx:nginx`. This has no ability to change any
files, but all files should be set to world readable.

PHP runs as a custom user `rd-www:rd-www` (`10118:10118`). This is a custom user
(to unify UIDs between Alpine and Debian). The group of all files should be set
to this, because `pub/` and `var/` are set to group writable. PHP only has read
permissions on everything else, since that's all it needs.

The other services to do not interact with the Magento 2 installation at a file
level, so their credentials are less important.

When running `rd run`, a Docker container is created. Your host's `/etc/passwd`
is mapped into the container, and it is configured to run with your user's ID.
This means that running something like `rd run -- whoami` will return your
username. However, we also set the user inside that container to have the
primary group of `10118`, or `rd-www`. This means that all files created inside
an invocation of `rd run` will maintain consistent permissions.

Redbox Docker comes with a command, `rd fix-permissions` to set permissions as
described above. It is unusual to have permissions troubles following this
pattern.

### Debian

Redbox Docker is based on Alpine Linux. Having small containers is a good idea
for a few different reasons, but mostly it's just good fun and takes up
a laughably small amount of space on my computer.

However, there are a few issues with PHP compatibility. The one I hit is
described in this Magento issue. It can be fixed as described in the ticket, but
if you don't want to do that, or just don't feel comfortable with Alpine, Debian
based containers are totally supported.

To run the Magento application with Debian, pass the `--use-debian` flag to `rd
start`. To run a one-off console command (such as `setup:static-content:deploy`)
on a Debian container, pass `--use-debian` as a flag to `rd run`.

These will tell Redbox Docker to use the special Debian versions of the
`redboxdigital/php` images.

## To Do

There are a few features that we would like to implement, but haven't had the
chance to yet.

### Varnish

Currently, Redbox Docker is using Redis as the back end to Magento's shim of
Varnish for FPC. This is not ideal.

#### Docker Image

There is no official image for Varnish, so we will need to make one. Some
thought must be given to how startup time paramaters are set, because it's
likely that some of these parameters will need to be customisable.

Varnish exists in the `apk` repositories, so it shouldn't be too hard to set up
a Dockerfile pretty quickly.

#### Debugging

The VCL that Magento generates is pretty standard, and can be shipped with
Redbox Docker. However, some customisations will need to be made in development
mode so that XDebug can be used through it.

#### Easy to Disable

It's entirely possible that a developer might not want to use Varnish. It can
probably be achieved just by turning off Full Page Cache, but we should
investigate making it easy to run Redbox Docker without Varnish. This will have
a knock on impact to the Nginx configuration.

### TLS

Right now, everything runs over simple HTTP. While HTTPS is not a requirement
for local development, it's a good idea to run with it tested so you can verify
that you won't have issues with things like mixed assets when you deploy.

We need some way of generating certificates through `rd run`, and making Nginx
support this. More work is needed.

### Enterprise Edition

Redbox Docker was designed to work with Enterprise Edition by default, since
that's what we use most of the time. It would be nice to make this a flag, and
remove unnecessary containers for Community Edition.

[install-docker]: https://docs.docker.com/engine/installation/linux/
[install-composer]: https://getcomposer.org/download/
