<?php

namespace Drupal\eca\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\eca\Plugin\Action\ActionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\eca\Service\Actions;

/**
 * Provides hooks related to config schemas.
 */
class ConfigSchemaHooks {

  /**
   * Constructs the config schema hook object.
   */
  public function __construct(
    protected Actions $actionService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_config_schema_info_alter().
   */
  #[Hook('config_schema_info_alter')]
  public function configSchemaInfoAlter(array &$definitions): void {
    foreach ($this->actionService->actions() as $action) {
      $key = 'action.configuration.' . $action->getPluginId();
      if (isset($definitions[$key]) && !($action instanceof ActionInterface) && $action instanceof ConfigurableInterface) {
        $definitions[$key]['mapping']['replace_tokens'] = [
          'type' => 'boolean',
          'label' => 'Replace tokens',
          'requiredKey' => FALSE,
        ];
        $actionType = $action->getPluginDefinition()['type'] ?? '';
        if ($actionType === 'entity' || $this->entityTypeManager->getDefinition($actionType, FALSE)) {
          $definitions[$key]['mapping']['object'] = [
            'type' => 'string',
            'label' => 'Token name holding the entity',
            'requiredKey' => FALSE,
          ];
        }
      }
    }
  }

}
