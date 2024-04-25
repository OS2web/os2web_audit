<?php

namespace Drupal\os2web_audit\Commands;

use Drupal\os2web_audit\Service\Logger;
use Drush\Commands\DrushCommands;

/**
 * Simple command to send log message into audit log.
 */
class AuditLogDrushCommands extends DrushCommands {

  /**
   * Os2webAuditDrushCommands constructor.
   *
   * @param \Drupal\os2web_audit\Service\Logger $auditLogger
   *   Audit logger service.
   */
  public function __construct(
    protected readonly Logger $auditLogger,
  ) {
    parent::__construct();
  }

  /**
   * Log a test message to the os2web_audit logger.
   *
   * @param string $log_message
   *   Message to be logged.
   *
   * @command audit:log
   * @usage audit:log 'This is a test message.'
   *   Logs 'This is a test message.' to the os2web_audit logger.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function logMessage(string $log_message = ''): void {
    if (empty($log_message)) {
      throw new \Exception('Log message cannot be empty.');
    }
    $this->auditLogger->log('test', time(), $log_message, ['from' => 'drush']);
  }

}
