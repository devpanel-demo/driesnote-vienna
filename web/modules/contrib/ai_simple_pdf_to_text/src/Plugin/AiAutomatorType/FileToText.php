<?php

namespace Drupal\ai_simple_pdf_to_text\Plugin\AiAutomatorType;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai_automators\Attribute\AiAutomatorType;

/**
 * The rules for a text_long field.
 */
#[AiAutomatorType(
  id: 'simple_pdf_to_text_text_long',
  label: new TranslatableMarkup('Simple PDF to Text'),
  field_rule: 'text_long',
  target: '',
)]
class FileToText extends FileToTextBase {

  /**
   * {@inheritDoc}
   */
  public $title = 'Simple PDF to Text';

  /**
   * {@inheritDoc}
   */
  public function storeValues(ContentEntityInterface $entity, array $values, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    // Get text format.
    $textFormat = $this->getTextFormat($fieldDefinition);

    // Then set the value.
    $cleanedValues = [];
    foreach ($values as $value) {
      $cleanedValues[] = [
        'value' => $value,
        'format' => $textFormat,
      ];
    }
    $entity->set($fieldDefinition->getName(), $cleanedValues);
  }

  /**
   * Get text format.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field definition.
   *
   * @return string|null
   *   The format.
   */
  protected function getTextFormat(FieldDefinitionInterface $fieldDefinition) {
    $allFormats = $this->entityTypeManager->getStorage('filter_format')->loadMultiple();
    // Maybe no formats are set.
    if (empty($allFormats)) {
      return NULL;
    }
    $format = $fieldDefinition->getSetting('allowed_formats');
    return $format[0] ?? key($allFormats);
  }

}
