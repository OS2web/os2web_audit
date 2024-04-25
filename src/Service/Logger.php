<?php

namespace Drupal\os2web_audit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
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
  ) {
  }

  /**
   * Logs a message at info level.
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
   * @param array $metadata
   *   Additional metadata for the log message. Default is an empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function info(string $type, int $timestamp, string $line, bool $logUser = false, array $metadata = []): void {
    $this->log($type, $timestamp, $line, $logUser, $metadata + ['level' => 'info']);
  }

  /**
   * Logs a message at error level.
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
   * @param array $metadata
   *   Additional metadata for the log message. Default is an empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function error(string $type, int $timestamp, string $line, bool $logUser = false, array $metadata = []): void {
    $this->log($type, $timestamp, $line, $logUser, $metadata + ['level' => 'error']);
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
   * @param array $metadata
   *   Additional metadata for the log message. Default is an empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function log(string $type, int $timestamp, string $line, bool $logUser = false, array $metadata = []): void {
    $config = $this->configFactory->get(SettingsForm::$configName);
    $plugin_id = $config->get('provider');

    // @todo: default logger (file)
    // @todo: Fallback logger on error.
    $configuration = $this->configFactory->get(PluginSettingsForm::getConfigName())->get($plugin_id);
    $logger = $this->loggerManager->createInstance($plugin_id, $configuration ?? []);

    if ($logUser) {
      // Add user id to the log message metadata.
      $user = \Drupal::currentUser();
      $metadata['userId'] = $user->id();
    }

    $logger->log($type, $timestamp, $line, $metadata);
  }

}
