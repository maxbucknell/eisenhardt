# Eisenhardt

A "batteries not included" best practice development environment for Magento 2,
based on Docker.

By this, we mean that this only contains the server configuration, and does no
magic around installing Magento. We found that we needed a best practice
development environment for projects we already had. As such, Eisenhardt is
not picky about how your projects are configured. You just need to set it up in
the root of your project.

Eisenhardt is a very thin wrapper around Docker Compose.

## Installation

### Dependencies

*	[Docker][install-docker]
*	[Composer][install-composer] (and PHP)
*	[mkcert][install-mkcert]

Install via Composer!

```bash
composer global require maxbucknell/eisenhardt
```

## Getting Started

This will walk you through setting up a clean installation of Magento. To use
Eisenhardt on an existing project, see the section below on that topic.

First of all, create a Magento 2 project

```bash
composer create-project \
  --repository-url=https://repo.magento.com/ \
  magento/project-community-edition:~2.1.0 \
  --no-install \
  test-eisenhardt
```

This creates a Magento 2 base project in the `test-eisenhardt` directory. Switch to it,
and set up Eisenhardt:

```bash
eisenhardt init test-eisenhardt.loc
eisenhardt start
```

Now we can begin to install Magento. First step is the dependencies:

```bash
eisenhardt run -- composer install
```

Now is probably a good time to make sure the permissions are correct:

```bash
eisenhardt fix-permissions
```

And make sure the database exists:

```bash
eisenhardt run -- mysql -hdatabase -uroot -proot
MySQL [(none)]> create database showoff;
```

And then the installation. The following command will do it all, but feel free
to change anything if you need to.

```bash
eisenhardt run -- n98-magerun2 setup:install \
  --backend-frontname="admin" \
  --db-host="database" \
  --db-name="showoff" \
  --db-user="root" \
  --db-password="root" \
  --http-cache-hosts="varnish:6081" \
  --base-url="http://test-eisenhardt.loc/" \
  --language="en_US" \
  --timezone="UTC" \
  --currency="EUR" \
  --admin-user="magento.admin" \
  --admin-password="password123" \
  --admin-email="magento.admin@example.com" \
  --admin-firstname="Magento" \
  --admin-lastname="Admin"
```

If you are running Enterprise, you can configure the Message Queue framework
with these arguments:

```
  --amqp-host="rabbitmq" \
  --amqp-port="5672" \
  --amqp-user="guest" \
  --amqp-password="guest" \
  --amqp-virtualhost="/" \
```

Now there are some final setup tasks, which are optional but encouraged to get
a quick environment:

```bash
eisenhardt run -d -- n98-magerun2 setup:static-content:deploy
eisenhardt run -- n98-magerun2 cache:flush
eisenhardt run -- n98-magerun2 setup:di:compile
```

After this, your Magento 2 installation should be ready to use, but you won't
be able to access it. To do this, run `eisenhardt info`, and copy the IP address of the
`webserver` container. Add an entry to your hosts file:

```
<ip_address> test-eisenhardt.loc
```

If you visit `test-eisenhardt.loc/` in your browser, you should see the home page. Log
into the admin with `magento.admin` and `password123`.

To see more details and available options, please check out the rest of the
documentation.

```bash
eisenhardt init
eisenhardt start
eisenhardt info
# Edit your /etc/hosts file to point your base URL to the IP address of the webserver
# Edit your app/etc/env.php to point your database at `database`.
eisenhardt run -- n98-magerun2 db:import <path/to/database.sql>
```

## Command Reference

### `eisenhardt init`

Bootstrap a new Eisenhardt project, or update an existing one.

This command should be run from the root of your Magento 2 installation.


### `eisenhardt start`

Start the Eisenhardt environment, and make it ready for use.

This command searches up its directory tree for a Eisenhardt environment, so
can be run from any directory inside your Magento 2 installation.

#### Options

| Flag                 | Description |
| :------------------- | :---------- |
| `-p`, `--map-ports`  | Map ports of Eisenhardt environment to host. By default, this does not happen, and Eisenhardt containers are accessible only from their IP addresses. Use this if you don't like editing your hosts file, or if you are running Docker on a remote machine, including a Mac or Windows computer. |

### `eisenhardt info`

Print some useful information about the Eisenhardt environment.

This command searches up its directory tree for a Eisenhardt environment, so
can be run from any directory inside your Magento 2 installation.

### `eisenhardt run`

Run a command inside the Eisenhardt environment.

There are many administrative tasks that require integration with the Magento
installation. This includes (but is not limited to):

*	Running a database dump.
*	Running a cron command to debug.
*	Installing dependencies via Composer.

You can do this by `exec`ing a command inside the PHP container, but it is not
the best idea. That will force you to install various command line tools inside
the app container.

`eisenhardt run` circumvents this by creating a new container, and injecting it into the
Eisenhardt environment's network, and mounting the volumes from the
appserver. It then runs an arbitrary command, and removes itself.

The container used is `maxbucknell/console`, and has an identical PHP
configuration to the appservers. Along with that, it also has a variety of
useful tools preinstalled, including:

*	MySQL client (`n98-magerun2 db:con`)
*	Redis client (`redis-cli -h cache`)
*	Composer (`composer`)
*	N98-Magerun2 (`n98-magerun2`)
*	Git (`git`)
*	Curl (`curl`)
*	Vim (`vim`)
*	PV
*	Liquidprompt
*	Node.js (with npm __and__ Yarn)

A lot of configuration is also passed from your host to the container. In
particular, Git configuration (including aliases), as well as SSH Agent sockets, and
Composer caches.

If you are running a command that takes flags as arguments, these flags will be
interpreted by the `eisenhardt` utility. To avoid this, you can quote your command, or
place your command after a `--`, like so:

```bash
# Prints the Magerun help, not eisenhardt help
eisenhardt run -- n98-magerun2 --help
```

See the page of that image for more details.

#### Options

| Flag                 | Description |
| :------------------- | :---------- |
| `-x`, `--debug`      | Run the container with Xdebug installed and enabled. If you want to debug a cron task, this is the way to do it. |

### `eisenhardt fix-permissions`

Set permissions to something approaching correct for a Magento 2 installation.

By correct, we mean the following:

*	All files and folders are owned by the host user (that's you!)
*	All files and folders have the group `eisenhardt-www (10118)
*	All files have permissions `744` (`rwxr--r--`)
*	All folders have permissions `755` (`rwxr-xr-x`)
*	All folders are set to "sticky", which means that new files created
within them inherit the group and permissions settings.
*	`var/` and `pub/` have write permissions on group.
*	`bin/magento` is made executable.

This command can take a while to run, since it touches a lot of files. If you
have `core.fileMode` set to `true` in your Git configuration, this can generate
changes on your files. You can either commit these (they're good permissions,
Brent), or you can tell Git not to track permissions.

### `eisenhardt stop`

Stop the Eisenhardt environment, as if you turned off your servers.

## Configuration for Existing Environments

If you are setting up an existing Magento 2 installation with Eisenhardt, the
only thing you need to change is `env.php`.

Start off by running `eisenhardt init` in the project root, and then `eisenhardt start`. You
will need to add a hosts entry as per the Getting Started instructions. Then set
the `env.php` parameters correctly.

The following `env.php` should work on pretty much all Eisenhardt
installations. Feel free to copy verbatim, or take the relevant parts.

Once done, the database will need to be imported. Create the database first,
with `eisenhardt run -- n98-magerun2 db:create`, and then import from a dump with:

```bash
# path/to/db.sql needs to be within your project root or Docker will not find it.
eisenhardt run -- n98-magerun2 db:import path/to/db.sql
```

A quick cache flush with `eisenhardt run -- n98-magerun2 c:f` and you should be good to
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
      'host' => 'rabbitmq',
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
        'host' => 'database',
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
        'host' => 'database',
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
    'date' => 'Thu, 10 Feb 1994 15:12:49 +1200',
  ),
  'crypt' =>
  array (
    'key' => 'd928820f82d3e459641d334a5f1b427e',
  ),
  'session' =>
  array (
    'save' => 'redis',
    'redis' =>
    array (
      'host' => 'session',
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
          'server' => 'cache',
          'port' => '6379',
          'persistent' => '',
          'database' => 0,
          'password' => '',
          'force_standalone' => 0,
          'connect_retries' => 1,
        ),
      ),
    ),
  ),
  'http_cache_hosts' => 
  array (
    0 => 
    array (
      'host' => 'varnish',
      'port' => '6081',
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

To debug a console command, you must pass the `-x` flag to `eisenhardt run`. This will
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

### RabbitMQ

RabbitMQ will largely take care of itself, but problems do come up. The
container used includes the management console. This can be accessed by going
to `/eisenhardt/rabbitmq`, and logging in. The username and password are both
set to `guest` by default.

### Mail

It is common to want to test emails locally. Eisenhardt comes with MailHog,
which catches all emails that are sent to any email address, and displays them
in a web interface.

It is possible to access MailHog directly, but the best way is to access
`/eisenhardt/mail` on your Magento webserver, which will proxy it across.

In addition to this, all emails are saved in the `.eisenhardt/mail` volume, as a record
and as a convenient way of checking sources directly.

## Technical Details

### `.eisenhardt` Directory

When you run `eisenhardt init`, it creates a directory at the root of your Magento
2 installation. This contains some volumes, the various configuration files for
Docker Compose, and some server configuration files.

It is recommended to check this folder into your project (it comes with
a `.gitignore`). This allows the developers in a team to maintain a consistent
and shared development environment.

While it's possible to edit these files (perhaps your MySQL configuration needs
to be tweaked because you have unusual demands), please bear in mind that
updating the Eisenhardt installation by re-running `eisenhardt init` will not respect
your changes.

### Container Inventory

Eisenhardt comes with a variety of containers, and they all do different
things. Here is a full list:

*	`varnish_webserver` (`nginx:alpine`): The webserver you will visit. Responsible for SSL termination and sending to Varnish.
*	`nginx` (`nginx:alpine`): The webserver in front of PHP.
*	`appserver` (`maxbucknell/php:7.0`): Usually the container running Magento.
*	`appserver_debug` (`maxbucknell/php:7.0-xdebug`): Like `appserver`, but with Xdebug.
*	`database` (`percona:5.6`): The database.
*	`cache` (`redis:alpine`): Cache backend.
*	`varnis` (`maxbucknell/varnish:4`): Page Cache backend
*	`session` (`redis:alpine`): Session storage backend.
*	`mailhog` (`mailhog/mailhog`): SMTP server to catch emails.
*	`rabbitmq` (`rabbitmq:management-alpine`): Message queue backend.
*	`elasticsearch` (`elasticsearch:5`): Elastic Search backend.
*	`kibana` (`kibana:5`): Management backend for Elastic Search.

#### Custom Containers

Varnish and PHP are custom containers. These are available on the Docker Hub,
but are also included in the main Eisenhardt repository. Move into the `dockerfiles/`
directory, and run `./build.sh`.

### Docker Compose Files

Eisenhardt is split into many different YAML files. This allows the
environment to be started in a variety of different configurations. For example,
the port mappings are configured in a different file (`ports.yml`), so the
environment can be started with or without port mappings.

*	`base.yml`: Base YAML file, containing services that would be essential for production. No volumes exist here.
*	`dev.yml`: Services required for development, including
`appserver_debug` and `mailhog`.
*	`ports.yml`: Map ports `:80`, `:443`, `:3306`, `:15672` (RabbitMQ), and `:1080` (Mailhog).

### Project Name

Docker Compose groups services by a "project". A project is usually the set of
services started by a single invocation of `docker-compose up`. A "project"
needs to have a name, which can be specified by the `-p` parameter, but will be
be generated from the name of the directory containing the `*.yml` file by
default.

Eisenhardt sets a custom name for its project based on the name of the
directory of the Magento project. If left to its own devices, Docker Compose
would call its project "eisenhardt" every time, since that's where the YAML
files reside.

### Permissions

By default, Docker runs as the root user. This is fine, because it normally
means that it's allowed to do whatever it needs to do. You run into trouble when
dealing with files that are generated by the Docker container, both in
committing them, and deleting them.

A simple `rm -rf pub/static` becomes problematic.

Because of this, Eisenhardt has all of its servers configured to run as
a non-privileged user. The actual user ID does not matter to Eisenhardt,
because all files should be owned by your host user. Eisenhardt uses group
permissions to do its thing.

Nginx runs as its typical user, `nginx:nginx`. This has no ability to change any
files, but all files should be set to world readable.

PHP runs as a custom user `eisenhardt-www:eisenhardt-www` (`10118:10118`). This is a custom user
(to unify UIDs between Alpine and Debian). The group of all files should be set
to this, because `pub/` and `var/` are set to group writable. PHP only has read
permissions on everything else, since that's all it needs.

The other services to do not interact with the Magento 2 installation at a file
level, so their credentials are less important.

When running `eisenhardt run`, a Docker container is created. Your host's `/etc/passwd`
is mapped into the container, and it is configured to run with your user's ID.
This means that running something like `eisenhardt run -- whoami` will return your
username. However, we also set the user inside that container to have the
primary group of `10118`, or `eisenhardt-www`. This means that all files created inside
an invocation of `eisenhardt run` will maintain consistent permissions.

Eisenhardt comes with a command, `eisenhardt fix-permissions` to set permissions as
described above. It is unusual to have permissions troubles following this
pattern.

[install-docker]: https://docs.docker.com/engine/installation/linux/
[install-composer]: https://getcomposer.org/download/
[install-mkcert]: https://github.com/FiloSottile/mkcert
