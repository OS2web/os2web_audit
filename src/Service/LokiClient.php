<?php

namespace Drupal\os2web_audit\Service;

use Drupal\os2web_audit\Exception\AuditException;
use Drupal\os2web_audit\Exception\ConnectionException;

/**
 * Class LokiClient.
 *
 * This is based/inspired by https://github.com/itspire/monolog-loki.
 */
class LokiClient implements LokiClientInterface {

  /**
   * Location of the loki entry point.
   *
   * @var string|null
   */
  protected ?string $entrypoint;

  /**
   * Basic authentication username and password.
   *
   * @var array<string>
   */
  protected array $basicAuth = [];

  /**
   * Custom options for CURL command.
   *
   * @var array<string, string>
   */
  protected array $customCurlOptions = [];

  /**
   * Curl handler.
   *
   * @var \CurlHandle|null
   */
  private ?\CurlHandle $connection = NULL;

  /**
   * Default constructor.
   *
   * @param array<string, string|array<string, string>> $apiConfig
   *   Configuration for the loki connection.
   */
  public function __construct(
    array $apiConfig,
  ) {
    $this->entrypoint = $this->getEntrypoint($apiConfig['entrypoint']);
    $this->customCurlOptions = $apiConfig['curl_options'] ?? [];

    if (isset($apiConfig['auth']) && !empty($apiConfig['auth']['username']) && !empty($apiConfig['auth']['password'])) {
      $this->basicAuth = $apiConfig['auth'];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\os2web_audit\Exception\ConnectionException
   *   If unable to connect to the Loki endpoint.
   * @throws \Drupal\os2web_audit\Exception\AuditException
   *   Errors in logging the packet.
   */
  public function send(string $label, int $epoch, string $line, array $metadata = []): void {
    $packet = [
      'streams' => [
        [
          'stream' => [
            'type' => $label,
          ],
          'values' => [
            [(string) $epoch, $line],
          ],
        ],
      ],
    ];

    if (!empty($metadata)) {
      $packet['streams'][0]['stream'] += $metadata;
    }

    $this->sendPacket($packet);
  }

  /**
   * Ensure the URL to entry point is correct.
   *
   * @param string $entrypoint
   *   Entry point URL.
   *
   * @return string
   *   The entry point URL formatted without a slash in the ending.
   */
  private function getEntrypoint(string $entrypoint): string {
    if (!str_ends_with($entrypoint, '/')) {
      return $entrypoint;
    }

    return substr($entrypoint, 0, -1);
  }

  /**
   * Send a packet to the Loki ingestion endpoint.
   *
   * @param array<string, mixed> $packet
   *   The packet to send.
   *
   * @throws \Drupal\os2web_audit\Exception\ConnectionException
   *   If unable to connect to the Loki endpoint.
   * @throws \Drupal\os2web_audit\Exception\AuditException
   *   Errors in logging the packet.
   */
  private function sendPacket(array $packet): void {
    try {
      $payload = json_encode($packet, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    catch (\JsonException $e) {
      throw $this->auditException('Payload could not be encoded.', 0, $e);
    }

    if (NULL === $this->connection) {
      $url = sprintf('%s/loki/api/v1/push', $this->entrypoint);
      $this->connection = curl_init($url);

      if (FALSE === $this->connection) {
        throw $this->connectionException('Unable to initialize curl connection to ' . $url);
      }
    }

    if (FALSE !== $this->connection) {
      $curlOptions = array_replace(
        [
          CURLOPT_CONNECTTIMEOUT_MS => 500,
          CURLOPT_TIMEOUT_MS => 200,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_POSTFIELDS => $payload,
          CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
          ],
        ],
        $this->customCurlOptions
      );

      if (!empty($this->basicAuth)) {
        $curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $curlOptions[CURLOPT_USERPWD] = implode(':', $this->basicAuth);
      }

      curl_setopt_array($this->connection, $curlOptions);
      $result = curl_exec($this->connection);

      if (FALSE === $result) {
        throw $this->connectionException('Error sending packet to Loki');
      }

      if (curl_errno($this->connection)) {
        throw $this->auditException(curl_error($this->connection), curl_errno($this->connection));
      }

      $code = curl_getinfo($this->connection, CURLINFO_HTTP_CODE);

      if (!in_array($code, [200, 204], TRUE)) {
        throw $this->auditException('Error sending packet to Loki', $code);
      }
    }
  }

  /**
   * Creates an audit exception.
   *
   * @param string $message
   *   The log message.
   * @param int $code
   *   The error code.
   * @param ?\Throwable $previous
   *   The previous throwable.
   * @param string $pluginName
   *   The plugin name.
   *
   * @return \Drupal\os2web_audit\Exception\AuditException
   *   The created exception.
   */
  private function auditException(string $message = '', int $code = 0, ?\Throwable $previous = NULL, string $pluginName = 'Loki'): AuditException {
    return new AuditException(
      message: $message,
      code: $code,
      previous: $previous,
      pluginName: $pluginName,
    );
  }

  /**
   * Creates a connection exception.
   *
   * @param string $message
   *   The log message.
   * @param int $code
   *   The error code.
   * @param ?\Throwable $previous
   *   The previous throwable.
   * @param string $pluginName
   *   The plugin name.
   *
   * @return \Drupal\os2web_audit\Exception\ConnectionException
   *   The created exception.
   */
  private function connectionException(string $message = '', int $code = 0, ?\Throwable $previous = NULL, string $pluginName = 'Loki'): ConnectionException {
    return new ConnectionException(
      message: $message,
      code: $code,
      previous: $previous,
      pluginName: $pluginName,
    );
  }

}
