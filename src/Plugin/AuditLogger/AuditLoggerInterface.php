<?php

namespace Drupal\os2web_audit\Plugin\AuditLogger;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for AuditLogger plugins.
 */
interface AuditLoggerInterface extends PluginInspectionInterface {

  /**
   * Logs a message with optional metadata.
   *
   * @param string $type
   *   The type of event to log (auth, lookup etc.)
   * @param int $timestamp
   *   The timestamp of the log entry.
   * @param string $line
   *   The log message.
   * @param array<string, string> $metadata
   *   Additional metadata associated with the log entry. Defaults to an empty
   *   array.
   *
   * @throws \Drupal\os2web_audit\Exception\ConnectionException
   *   If unable to connect to the Loki endpoint.
   * @throws \Drupal\os2web_audit\Exception\AuditException
   *   Errors in logging the packet.
   */
  public function log(string $type, int $timestamp, string $line, array $metadata = []): void;

}
