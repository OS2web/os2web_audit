# OS2Web Audit

This audit module can be used to track changes and perform audit logging on
drupal sites.

## Features

This module includes three plugins that facilitate logging information to Loki,
files, or to the database through Drupal's watchdog logger.

These logging providers are designed using Drupal's plugin APIs. Consequently,
it opens up possibilities for creating new AuditLogger plugins within other
modules, thus enhancing the functionality of this audit logging.

For performance purposes we use a queue system. This avoids hindering
performance more than necessary as the actual logging is done async. Furthermore,
this allows for retries in case any audit log plugins should fail.

## Installation

Enable the module and go to the modules setting page at
`/admin/config/os2web_audit/settings/`.

```shell
composer require os2web/os2web_audit
drush pm:enable os2web_audit
```

### Drush

The module provides a Drush command named audit:log. This command enables you
to log a test message to the configured logger. The audit:log command accepts a
string that represents the message to be logged.

The message provided, will be logged twice, once as an informational message
and once as an error message.

```shell
drush audit:log 'This is a test message'
```

## Usage

The module exposes a simple `Logger` service which can log an `info` and `error`
messages.

Inject the logger service named `os2web_audit.logger` and send messages into the
logger as shown below:

```php
$msg = sprintf('Fetch personal data from service with parameter: %s', $param);
$this->auditLogger->info('Lookup', $msg);
```

### Queue

The actual logging is handled by jobs in an [Advanced
Queue](https://www.drupal.org/project/advancedqueue) queue.

The queue, OS2Web audit (`os2web_audit`), must be
processed by a server `cron` job, e.g.

```sh
drush advancedqueue:queue:process os2web_audit
```

List the queue (and all other queues) with

```sh
drush advancedqueue:queue:list
```

or go to `/admin/config/system/queues/jobs/os2web_audit` for a
graphical overview of jobs in the queue.

### Coding standards

#### PHP files (PHP_CodeSniffer)

Check PHP coding standards

```shell
docker run --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer install
docker run --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer coding-standards-check
```

Apply coding standard changes

```shell
docker run --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer install
docker run --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer coding-standards-apply
```

### Code analysis

phpstan is used to perform static analysis of the code. Run the following script:

```sh
./scripts/code-analysis
```

#### Markdown files

```shell
docker run --interactive --rm --volume "$PWD:/md" itkdev/markdownlint markdownlint '**/*.md' --fix
docker run --interactive --rm --volume "$PWD:/md" itkdev/markdownlint markdownlint '**/*.md'
```
