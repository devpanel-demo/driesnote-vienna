<?php

namespace Drupal\pexels_ai\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Pexels API access.
 */
class PexelsConfigForm extends ConfigFormBase {

  /**
   * Constructs a new PexelsConfigForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'pexels_ai.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pexels_ai_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Pexels API Key'),
      '#description' => $this->t('Can be found and generated <a href="https://www.pexels.com/api/key/" target="_blank">here</a>.'),
      '#default_value' => $config->get('api_key'),
      '#states' => [
        'visible' => [
          ':input[name="setting_type"]' => ['value' => 'api_key'],
        ],
      ],
    ];

    // Get available media bundles.
    $media_bundles = $this->getMediaBundles();

    $form['media_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Media Bundle'),
      '#description' => $this->t('Select the media bundle to use for storing Pexels images.'),
      '#options' => $media_bundles,
      '#default_value' => $config->get('media_bundle'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateImageFieldOptions',
        'wrapper' => 'image-field-wrapper',
      ],
    ];

    // Container for image field selection (updated via AJAX).
    $form['image_field_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'image-field-wrapper'],
    ];

    $selected_bundle = $form_state->getValue('media_bundle') ?: $config->get('media_bundle');

    if ($selected_bundle) {
      $image_fields = $this->getImageFields($selected_bundle);

      $form['image_field_wrapper']['image_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Image Field'),
        '#description' => $this->t('Select the image field to store the downloaded images.'),
        '#options' => $image_fields,
        '#default_value' => $config->get('image_field'),
        '#required' => TRUE,
        '#empty_option' => $this->t('- Select an image field -'),
        // Make sure form API recognizes this field properly.
        '#parents' => ['image_field'],
      ];
    }
    else {
      $form['image_field_wrapper']['image_field_placeholder'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Please select a media bundle first to see available image fields.') . '</p>',
      ];

      // Add a hidden field to maintain form structure.
      $form['image_field_wrapper']['image_field'] = [
        '#type' => 'hidden',
        '#value' => '',
        '#parents' => ['image_field'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate media bundle and image field.
    $media_bundle = $form_state->getValue('media_bundle');
    $image_field = $form_state->getValue('image_field');

    if (!empty($media_bundle) && empty($image_field)) {
      $form_state->setErrorByName('image_field', $this->t('Please select an image field for the chosen media bundle.'));
    }

    // Additional validation to ensure the selected field exists for the bundle.
    if (!empty($media_bundle) && !empty($image_field)) {
      $available_fields = $this->getImageFields($media_bundle);
      if (!isset($available_fields[$image_field])) {
        $form_state->setErrorByName('image_field', $this->t('The selected image field is not valid for the chosen media bundle.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the image field value using the parents directive path.
    $image_field_value = $form_state->getValue('image_field');

    // Retrieve the configuration.
    $this->config(static::CONFIG_NAME)
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('media_bundle', $form_state->getValue('media_bundle'))
      ->set('image_field', $image_field_value)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * AJAX callback to update image field options.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element to be replaced.
   */
  public function updateImageFieldOptions(array &$form, FormStateInterface $form_state) {
    return $form['image_field_wrapper'];
  }

  /**
   * Get available media bundles.
   *
   * @return array
   *   Array of media bundle options.
   */
  protected function getMediaBundles() {
    $bundles = [];

    try {
      $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();

      foreach ($media_types as $bundle_id => $media_type) {
        $bundles[$bundle_id] = $media_type->label();
      }
    }
    catch (\Exception $e) {
      // Log error if needed.
      $bundles = [];
    }

    return $bundles;
  }

  /**
   * Get image fields for a specific media bundle.
   *
   * @param string $bundle
   *   The media bundle ID.
   *
   * @return array
   *   Array of image field options.
   */
  protected function getImageFields($bundle) {
    $fields = [];

    if (empty($bundle)) {
      return $fields;
    }

    try {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('media', $bundle);

      foreach ($field_definitions as $field_name => $field_definition) {
        // Check if the field is an image field.
        if ($field_definition->getType() === 'image') {
          $fields[$field_name] = $field_definition->getLabel() . ' (' . $field_name . ')';
        }
      }
    }
    catch (\Exception $e) {
      // Log error if needed.
      $fields = [];
    }

    return $fields;
  }

}
