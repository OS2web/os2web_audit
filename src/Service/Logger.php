<?php

namespace Drupal\os2web_audit\Service;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\os2web_audit\Exception\AuditException;
use Drupal\os2web_audit\Exception\ConnectionException;
use Drupal\os2web_audit\Form\PluginSettingsForm;
use Drupal\os2web_audit\Form\SettingsForm;
use Drupal\os2web_audit\Plugin\LoggerManager;

/**
 * Class Logger.
 *
 * Helper service to send log messages in the right direction.
 */
class Logger {

  public function __construct(
    private readonly LoggerManager $loggerManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AccountProxyInterface $currentUser,
    private readonly LoggerChannelFactoryInterface $watchdog,
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
    $this->log($type, time(), $line, $logUser, $metadata + ['level' => 'info']);
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
    $this->log($type, time(), $line, $logUser, $metadata + ['level' => 'error']);
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
   * @param bool $logUser
   *   Log information about the current logged-in user (need to track who has
   *   lookup information in external services). Default: false.
   * @param array<string, string> $metadata
   *   Additional metadata for the log message. Default is an empty array.
   */
  private function log(string $type, int $timestamp, string $line, bool $logUser = FALSE, array $metadata = []): void {
    $config = $this->configFactory->get(SettingsForm::$configName);
    $plugin_id = $config->get('provider') ?? SettingsForm::OS2WEB_AUDIT_DEFUALT_PROVIDER;
    $configuration = $this->configFactory->get(PluginSettingsForm::getConfigName())->get($plugin_id);

    if ($logUser) {
      // Add user id to the log message metadata.
      $metadata['userId'] = $this->currentUser->getEmail();
    }

    try {
      /** @var \Drupal\os2web_audit\Plugin\AuditLogger\AuditLoggerInterface $logger */
      $logger = $this->loggerManager->createInstance($plugin_id, $configuration ?? []);
      $logger->log($type, $timestamp, $line, $metadata);
    }
    catch (PluginException $e) {
      $this->watchdog->get('os2web_audit')->error($e->getMessage());
    }
    catch (AuditException | ConnectionException $e) {
      // Change metadata into string.
      $data = implode(', ', array_map(function ($key, $value) {
        return $key . " => " . $value;
      }, array_keys($metadata), $metadata));

      // Fallback to send log message info watchdog.
      $msg = sprintf("Plugin: %s, Type: %s, Msg: %s, Metadata: %s", $e->getPluginName(), $type, $line, $data);
      $this->watchdog->get('os2web_audit')->info($msg);
      $this->watchdog->get('os2web_audit_error')->error($e->getMessage());
    }
  }

}
