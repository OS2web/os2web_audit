<?php

namespace Drupal\os2web_audit\Plugin\AuditLogger;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\os2web_audit\Service\LokiClient;

/**
 * Stores entities in the database.
 *
 * @AuditLoggerProvider(
 *   id = "loki",
 *   title = @Translation("Grafana Loki"),
 *   description = @Translation("Store entity data in Loki.")
 * )
 */
class Loki extends PluginBase implements AuditLoggerInterface, PluginFormInterface, ConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\os2web_audit\Exception\ConnectionException
   *   If unable to connect to the Loki endpoint.
   * @throws \Drupal\os2web_audit\Exception\AuditException
   *   Errors in logging the packet.
   */
  public function log(string $type, int $timestamp, string $message, array $metadata = []): void {
    $client = new LokiClient([
      'entrypoint' => $this->configuration['entrypoint'],
      'auth' => $this->configuration['auth'],
    ]);

    // Add 'identity' to metadata to be able to filter out all messages from
    // this site in loki.
    if (!empty($this->configuration['identity'])) {
      $metadata['identity'] = $this->configuration['identity'];
    }

    // Convert timestamp to nanoseconds.
    $client->send($type, $timestamp * 1000000000, $message, $metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'entrypoint' => 'http://loki:3100',
      'auth' => [
        'username' => '',
        'password' => '',
      ],
      'identity' => '',
      'curl_options' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['entrypoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Entry Point URL'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['entrypoint'],
    ];

    $form['auth'] = [
      '#tree' => TRUE,
      'username' => [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#default_value' => $this->configuration['auth']['username'],
      ],
      'password' => [
        '#type' => 'password',
        '#title' => $this->t('Password'),
        '#default_value' => $this->configuration['auth']['password'],
      ],
    ];

    $form['identity'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Identity'),
      '#default_value' => $this->configuration['identity'],
      '#description'   => $this->t('A string that will be attached to every log sendt to loki'),
    ];

    $form['curl_options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('cURL Options'),
      '#default_value' => $this->configuration['curl_options'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    // Validate entrypoint.
    if (filter_var($values['entrypoint'], FILTER_VALIDATE_URL) === FALSE) {
      $form_state->setErrorByName('entrypoint', $this->t('Invalid URL.'));
    }

    $curlOptions = array_filter(explode(',', $values['curl_options']));
    foreach ($curlOptions as $option) {
      [$key] = explode(' =>', $option);
      $key = trim($key);
      if (!(str_starts_with($key, 'CURLOPT') && defined($key))) {
        $form_state->setErrorByName('curl_options', $this->t('%option is not a valid cURL option.', ['%option' => $key]));
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValues();
      $configuration = [
        'entrypoint' => $values['entrypoint'],
        'auth' => [
          'username' => $values['auth']['username'],
          'password' => $values['auth']['password'],
        ],
        'identity' => $values['identity'],
        'curl_options' => $values['curl_options'],
      ];
      $this->setConfiguration($configuration);
    }
  }

}
