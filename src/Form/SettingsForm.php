<?php

namespace Drupal\os2web_audit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os2web_audit\Plugin\LoggerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * This is the settings for the module.
 */
class SettingsForm extends ConfigFormBase {
  public const OS2WEB_AUDIT_DEFUALT_PROVIDER = 'watchdog';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    private readonly LoggerManager $loggerManager,
  ) {
    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.os2web_audit_logger')
    );
  }

  /**
   * The name of the configuration setting.
   *
   * @var string
   */
  public static string $configName = 'os2web_audit.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::$configName];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'os2web_audit_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::$configName);

    $plugins = $this->loggerManager->getDefinitions();
    ksort($plugins);
    $options = array_map(function ($plugin) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $title */
      $title = $plugin['title'];
      return $title->render();
    }, $plugins);

    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Log provider'),
      '#description' => $this->t('Select the logger provider you which to use'),
      '#options' => $options,
      // We let watchdog be the default provider.
      '#default_value' => $config->get('provider') ?? self::OS2WEB_AUDIT_DEFUALT_PROVIDER,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config(self::$configName)
      ->set('provider', $form_state->getValue('provider'))
      ->save();
  }

}
