<?php

namespace Drupal\os2web_audit\Plugin\AdvancedQueue\JobType;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
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
      $container->get('logger.factory'),
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
    private readonly LoggerChannelFactoryInterface $watchdog,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Processes the LogMessages job.
   */
  public function process(Job $job): JobResult {
    $payload = $job->getPayload();

    $logger_context = [
      'job_id' => $job->getId(),
      'operation' => 'response from queue',
    ];

    try {
      $this->logger->log($payload['type'], $payload['timestamp'], $payload['line'], $payload['plugin_id'], $payload['metadata']);
      $this->watchdog->get(Logger::OS2WEB_AUDIT_LOGGER_CHANNEL)->info('Successfully audit logged message.', $logger_context);

      return JobResult::success();
    }
    catch (\Exception $e) {
      $this->watchdog->get(Logger::OS2WEB_AUDIT_LOGGER_CHANNEL)->error(sprintf('Failed audit logging message: %s', $e->getMessage()), $logger_context);

      return JobResult::failure($e->getMessage());
    }
  }

}
