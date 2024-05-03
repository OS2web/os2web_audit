# OS2Web Audit

This audit module can be used to track changes and perform audit logging on
drupal sites.

## Features

This module includes three plugins that facilitate logging information to Loki,
files, or to the database through Drupal's watchdog logger.

These logging providers are designed using Drupal's plugin APIs. Consequently,
it opens up possibilities for creating new AuditLogger plugins within other
modules, thus enhancing the functionality of this audit logging.

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

The module exposes n simple `Logger` service which can log an `info` and`error`
messages.

Inject the logger service named `os2web_audit.logger` and send messages into the
logger as shown below:

```php
$msg = sprintf('Fetch personal data from service with parameter: %s', $param);
$this->auditLogger->info('Lookup', $msg);
```
