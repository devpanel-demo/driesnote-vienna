<?php

namespace Drupal\eca_form\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\eca\Hook\TokenHooks as EcaTokenHooks;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

/**
 * Implements token hooks for the ECA Form submodule.
 */
class TokenHooks {

  /**
   * Constructs a new TokenHooks object.
   */
  public function __construct(
    protected EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $info = [];
    $info['types']['current_form'] = [
      'name' => t('Current form'),
      'description' => t('Tokens containing information about the current form. This is only available when reacting upon ECA Form events.'),
      'needs-data' => 'current_form',
    ];
    $info['tokens']['current_form'] = [
      'id' => [
        'name' => t('Form ID'),
        'description' => t('The form ID as machine name.'),
      ],
      'base_id' => [
        'name' => t('Base form ID'),
        'description' => t('The base form ID as machine name. Please note that some notes do not have a base form ID.'),
      ],
      'operation' => [
        'name' => t('Operation'),
        'description' => t('The machine name that identifies the main operation of the form. This is only available on entity forms.'),
      ],
      'mode' => [
        'name' => t('Form display mode'),
        'description' => t('The ID of the used form display mode. This is only available on content entity forms.'),
      ],
      'values' => [
        'name' => t('Submitted values'),
        'description' => t('The form input values submitted by the user. For example, when the form has a "username" input, then its submission value may be accessed with [current_form:values:username]. If you need to compare against raw values, use the condition "Form field: compare submitted value" instead.'),
        'dynamic' => TRUE,
      ],
      'num_errors' => [
        'name' => t('Number of form errors'),
        'description' => t('The number of form validation errors. This is only available after form validation.'),
      ],
      'triggered' => [
        'name' => 'Triggered form element',
        'description' => t('The machine name of the form element that triggered form submission.'),
      ],
    ];
    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    if ($type === 'current_form' && !empty($data['current_form'])) {
      // The current form info is just a DTO, declare it as such and pass
      // through.
      $data['dto'] = $data['current_form'];
      unset($data['current_form']);
      if ($this->eventDispatcher instanceof SymfonyEventDispatcherInterface) {
        foreach ($this->eventDispatcher->getListeners('drupal_hook.tokens') as $listener) {
          if ($listener[0] instanceof EcaTokenHooks) {
            return call_user_func($listener, 'dto', $tokens, $data, $options, $bubbleable_metadata);
          }
        }
      }
    }
    return [];
  }

}
