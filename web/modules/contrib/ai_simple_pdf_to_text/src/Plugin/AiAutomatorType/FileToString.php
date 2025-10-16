<?php

namespace Drupal\ai_simple_pdf_to_text\Plugin\AiAutomatorType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai_automators\Attribute\AiAutomatorType;

/**
 * The rules for a string_long field.
 */
#[AiAutomatorType(
  id: 'simple_pdf_to_text_string_long',
  label: new TranslatableMarkup('Simple PDF to Text'),
  field_rule: 'string_long',
  target: '',
)]
class FileToString extends FileToTextBase {

  /**
   * {@inheritDoc}
   */
  public $title = 'Simple PDF to Text';

}
