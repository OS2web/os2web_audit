<?php

namespace Drupal\os2web_audit\Drush\Commands;

use Drush\Attributes\Command;
use Drupal\os2web_audit\Service\Logger;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Drush\Attributes\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drush\Exceptions\CommandFailedException;
/**
 * Simple command to send log message into audit log.
 */
class Commands extends DrushCommands {

  /**
   * Commands constructor.
   *
   * @param \Drupal\os2web_audit\Service\Logger $auditLogger
   *   Audit logger service.
   */
  public function __construct(
    #[Autowire(service: 'os2web_audit.logger')]
    protected readonly Logger $auditLogger,
  ) {
    parent::__construct();
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
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws CommandFailedException
   */
  #[Command(name: 'audit:log')]
  #[Argument(name: 'log_message', description: "Message to be logged.")]
  public function logMessage(string $log_message): void {
    if (empty($log_message)) {
      throw new CommandFailedException('Log message cannot be empty.');
    }

    $this->auditLogger->info('test', $log_message, FALSE, ['from' => 'drush']);
    $this->auditLogger->error('test', $log_message, TRUE, ['from' => 'drush']);
  }

}
