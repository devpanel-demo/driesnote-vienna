<?php

declare(strict_types=1);

namespace Drupal\ai_simple_pdf_to_text\Plugin\AiFunctionCall;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\ExecutableFunctionCallInterface;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\ai\Utility\ContextDefinitionNormalizer;
use Smalot\PdfParser\Parser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the Simple PDF to Text function call.
 */
#[FunctionCall(
  id: 'ai_simple_pdf_to_text:simple_pdf_to_text',
  function_name: 'simple_pdf_to_text',
  name: 'Simple PDF to Text',
  description: 'This method extracts text from a PDF document given its file ID or location. Only one of the parameters is required.',
  group: 'information_tools',
  context_definitions: [
    'file_id' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("File ID"),
      description: new TranslatableMarkup("The Drupal file ID of the PDF document to extract text from."),
      required: FALSE,
    ),
    'file_location' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("File Location"),
      description: new TranslatableMarkup("The location (URI/URL) of the PDF document to extract text from."),
      required: FALSE,
    ),
  ],
)]
class PdfToText extends FunctionCallBase implements ExecutableFunctionCallInterface {

  /**
   * Constructs a new ListWebformElements instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\ai\Utility\ContextDefinitionNormalizer $context_definition_normalizer
   *   The context definition normalizer service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    ContextDefinitionNormalizer $context_definition_normalizer,
    protected AccountInterface $account,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $context_definition_normalizer);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FunctionCallInterface|static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ai.context_definition_normalizer'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Collect the context values.
    $file_id = $this->getContextValue('file_id');
    $file_location = $this->getContextValue('file_location');

    if (empty($file_id) && empty($file_location)) {
      $this->setOutput("Either 'file_id' or 'file_location' must be provided.");
      return;
    }
    if (!empty($file_id) && !empty($file_location)) {
      $this->setOutput("Please provide only one of 'file_id' or 'file_location', not both.");
      return;
    }

    // Get file location from file ID if provided.
    if (!empty($file_id)) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $this->entityTypeManager->getStorage('file')->load($file_id);
      if (!$file) {
        $this->setOutput("File with ID '$file_id' not found.");
        return;
      }
      // Make sure the user has access to the file.
      if (!$file->access('view', $this->account)) {
        $this->setOutput("You do not have permission to access the file with ID '$file_id'.");
        return;
      }
      $file_location = $file->getFileUri();
    }

    // Load the parser.
    $parser = new Parser();
    try {
      $text = $parser->parseFile($file_location)->getText();
      $this->setOutput($text);
    }
    catch (\Exception $e) {
      $this->setOutput("Failed to extract text from the PDF document: " . $e->getMessage());
      return;
    }
  }

}
