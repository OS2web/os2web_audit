<?php

namespace Drupal\os2web_audit\Exception;

/**
 * Class AuditException.
 *
 * Base exception for auditing provider plugins.
 */
class AuditException extends \Exception {

  /**
   * The name of the plugin-.
   *
   * @var string
   */
  private string $pluginName = 'Unknown plugin';

  public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = NULL, ?string $pluginName = NULL) {
    parent::__construct($message, $code, $previous);

    if (isset($pluginName)) {
      $this->pluginName = $pluginName;
    }
  }

  /**
   * Name of the plugin that started the exception.
   *
   * @return string
   *   Name of the plugin if given else "Unknown plugin".
   */
  public function getPluginName(): string {
    return $this->pluginName;
  }

}
