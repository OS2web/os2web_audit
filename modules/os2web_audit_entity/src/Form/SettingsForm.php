<?php

namespace Drupal\os2web_audit_entity\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * This is the settings for the module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    private EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * The name of the configuration setting.
   *
   * @var string
   */
  public static string $configName = 'os2web_audit_entity.settings';

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
    return 'os2web_audit_entity_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $items = [];
    $roles = $this->getRoles();
    foreach ($roles as $role) {
      $items[$role->id()] = $role->label();
    }

    $config = $this->config(self::$configName);

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select API access roles'),
      '#description' => $this->t('The selected roles will be use to determine who is accessing entities through the API.'),
      '#options' => $items,
      '#default_value' => $config->get('roles') ?? [],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config(self::$configName)
      ->set('roles', $form_state->getValue('roles'))
      ->save();
  }

  /**
   * Get all roles.
   *
   * @return array<\Drupal\Core\Entity\EntityInterface>
   *   An array of role entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getRoles() {
    // Use the role storage to load roles.
    $roleStorage = $this->entityTypeManager->getStorage('user_role');

    return $roleStorage->loadMultiple();
  }

}
