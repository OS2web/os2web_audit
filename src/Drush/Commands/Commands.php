<?php

namespace Drupal\os2web_audit\Drush\Commands;

use Drupal\os2web_audit\Service\Logger;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Drush\Attributes\Option;
use Drush\Attributes\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple command to send log message into audit log.
 */
class Commands extends DrushCommands {

  /**
   * Os2webAuditDrushCommands constructor.
   *
   * @param \Drupal\os2web_audit\Service\Logger $auditLogger
   *   Audit logger service.
   */
  public function __construct(
    #[Autowire(service: 'os2web_audit.logger')]
    protected readonly Logger $auditLogger,
  ) {
  }

    /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('os2web_audit.logger'),
    );
  }


  /**
   * Log a test message to the os2web_audit logger.
   *
   * @param string $log_message
   *   Message to be logged.
   *
   * @command audit:log
   * @usage audit:log 'This is a test message'
   *   Logs 'This is a test message' to the os2web_audit logger.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   */
   #[Command(name: 'audit:log')]
   #[Option(name: 'log_message', description: "The test message to be logged")]
  public function logMessage(string $log_message = ''): void {
    if (empty($log_message)) {
      throw new \Exception('Log message cannot be empty.');
    }
    $this->auditLogger->info('test', $log_message, FALSE, ['from' => 'drush']);
    $this->auditLogger->error('test', $log_message, TRUE, ['from' => 'drush']);
  }

}
