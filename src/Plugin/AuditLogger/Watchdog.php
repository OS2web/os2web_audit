<?php

namespace Drupal\os2web_audit\Plugin\AuditLogger;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stores entities in the database.
 *
 * @AuditLoggerProvider(
 *   id = "watchdog",
 *   title = @Translation("Watchdog"),
 *   description = @Translation("Store entity data in the database.")
 * )
 */
class Watchdog extends PluginBase implements AuditLoggerInterface, ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly LoggerChannelFactoryInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function log(string $type, int $timestamp, string $message, array $metadata = []): void {
    $data = '';
    array_walk($metadata, function ($val, $key) use (&$data) {
      $data .= " $key=\"$val\"";
    });

    $this->logger->get('os2web_audit')->info('%type: %line (%data)', [
      '%type' => $type,
      '%line' => $message,
      '%data' => $data,
    ]);
  }

}
