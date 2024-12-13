<?php

namespace Drupal\os2web_audit\Plugin\AdvancedQueue\JobType;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\os2web_audit\Exception\AuditException;
use Drupal\os2web_audit\Exception\ConnectionException;
use Drupal\os2web_audit\Service\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Log messages job.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2web_audit\Plugin\AdvancedQueue\JobType\LogMessages",
 *   label = @Translation("Audit Log messages"),
 * )
 */
class LogMessages extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('os2web_audit.logger'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly Logger $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Processes the LogMessages job.
   */
  public function process(Job $job): JobResult {
    $payload = $job->getPayload();

    try {
      $this->logger->log($payload['type'], $payload['timestamp'], $payload['line'], $payload['plugin_id'], $payload['metadata']);

      return JobResult::success();
    }
    catch (PluginException | ConnectionException | AuditException $e) {
      return JobResult::failure($e->getMessage());
    }
  }

}
