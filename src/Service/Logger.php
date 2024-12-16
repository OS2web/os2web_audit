<?php

namespace Drupal\os2web_audit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\advancedqueue\Job;
use Drupal\os2web_audit\Form\PluginSettingsForm;
use Drupal\os2web_audit\Form\SettingsForm;
use Drupal\os2web_audit\Plugin\AdvancedQueue\JobType\LogMessages;
use Drupal\os2web_audit\Plugin\LoggerManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Logger.
 *
 * Helper service to send log messages in the right direction.
 */
class Logger {

  const string OS2WEB_AUDIT_QUEUE_ID = 'os2web_audit';
  const string OS2WEB_AUDIT_LOGGER_CHANNEL = 'os2web_audit_info';

  public function __construct(
    private readonly LoggerManager $loggerManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AccountProxyInterface $currentUser,
    private readonly LoggerChannelFactoryInterface $watchdog,
    private readonly RequestStack $requestStack,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Logs a message at info level.
   *
   * @param string $type
   *   The type of event to log (auth, lookup etc.)
   * @param string $line
   *   The log message.
   * @param bool $logUser
   *   Log information about the current logged-in user (need to track who has
   *   lookup information in external services). Default: false.
   * @param array<string, string> $metadata
   *   Additional metadata for the log message. Default is an empty array.
   */
  public function info(string $type, string $line, bool $logUser = TRUE, array $metadata = []): void {
    $this->createLoggingJob($type, time(), $line, $logUser, $metadata + ['level' => 'info']);
  }

  /**
   * Logs a message at error level.
   *
   * @param string $type
   *   The type of event to log (auth, lookup etc.)
   * @param string $line
   *   The log message.
   * @param bool $logUser
   *   Log information about the current logged-in user (need to track who has
   *   lookup information in external services). Default: false.
   * @param array<string, string> $metadata
   *   Additional metadata for the log message. Default is an empty array.
   */
  public function error(string $type, string $line, bool $logUser = TRUE, array $metadata = []): void {
    $this->createLoggingJob($type, time(), $line, $logUser, $metadata + ['level' => 'error']);
  }

  /**
   * Creates and enqueues logging job.
   *
   * @param string $type
   *   The type of event to log (auth, lookup etc.)
   * @param int $timestamp
   *   The timestamp for the log message.
   * @param string $line
   *   The log message.
   * @param bool $logUser
   *   Log information about the current logged-in user (need to track who has
   *   lookup information in external services). Default: false.
   * @param array<string, string> $metadata
   *   Additional metadata for the log message. Default is an empty array.
   */
  private function createLoggingJob(string $type, int $timestamp, string $line, bool $logUser = FALSE, array $metadata = []): void {

    // Enhance logging data with current user and current request information.
    if ($logUser) {
      // Add user id to the log message metadata.
      $metadata['userId'] = $this->currentUser->getEmail();
    }

    // Log request IP for information more information.
    $request = $this->requestStack->getCurrentRequest();
    $ip_address = $request->getClientIp();
    if (!is_null($ip_address)) {
      $line .= sprintf(' Remote ip: %s', $ip_address);
    }

    $config = $this->configFactory->get(SettingsForm::$configName);
    $plugin_id = $config->get('provider') ?? SettingsForm::OS2WEB_AUDIT_DEFUALT_PROVIDER;

    $payload = [
      'type' => $type,
      'timestamp' => $timestamp,
      'line' => $line,
      'plugin_id' => $plugin_id,
      'metadata' => $metadata,
    ];

    try {
      $queueStorage = $this->entityTypeManager->getStorage('advancedqueue_queue');
      /** @var \Drupal\advancedqueue\Entity\Queue|null $queue */
      $queue = $queueStorage->load(self::OS2WEB_AUDIT_QUEUE_ID);

      if (NULL === $queue) {
        throw new \Exception(sprintf('Queue (%s) not found.', self::OS2WEB_AUDIT_QUEUE_ID));
      }

      $job = Job::create(LogMessages::class, $payload);

      $queue->enqueueJob($job);
    }
    catch (\Exception $exception) {
      $this->watchdog->get(self::OS2WEB_AUDIT_LOGGER_CHANNEL)->error(sprintf('Failed creating job: %s', $exception->getMessage()), $payload);
    }

  }

  /**
   * Logs a message using a plugin-specific logger.
   *
   * @param string $type
   *   The type of event to log (auth, lookup etc.)
   * @param int $timestamp
   *   The timestamp for the log message.
   * @param string $line
   *   The log message.
   * @param string $plugin_id
   *   The logging plugin id.
   * @param array<string, string> $metadata
   *   Additional metadata for the log message. Default is an empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\os2web_audit\Exception\ConnectionException
   * @throws \Drupal\os2web_audit\Exception\AuditException
   */
  public function log(string $type, int $timestamp, string $line, string $plugin_id, array $metadata = []): void {

    $configuration = $this->configFactory->get(PluginSettingsForm::getConfigName())->get($plugin_id);

    /** @var \Drupal\os2web_audit\Plugin\AuditLogger\AuditLoggerInterface $logger */
    $logger = $this->loggerManager->createInstance($plugin_id, $configuration ?? []);
    $logger->log($type, $timestamp, $line, $metadata);
  }

}
