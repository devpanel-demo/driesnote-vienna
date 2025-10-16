<?php

namespace Drupal\pexels_ai\Plugin\AiFunctionCall;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\ExecutableFunctionCallInterface;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\ai\Utility\ContextDefinitionNormalizer;
use Drupal\ai_agents\PluginInterfaces\AiAgentContextInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Utility\Token;
use Drupal\pexels_ai\PexelsApi;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * This function call will help you search images on Pexels.
 */
#[FunctionCall(
  id: 'pexels_ai:download_images_pexels',
  function_name: 'download_pexels_images',
  name: 'Download Pexels Images',
  description: 'This function will download the specific ids from the Pexels database.',
  group: 'information_tools',
  context_definitions: [
    'ids' => new ContextDefinition(
      data_type: 'list',
      label: new TranslatableMarkup("ids"),
      description: new TranslatableMarkup("The numeric ids to download. This will create a media entity for each id. Make sure the ids are valid Pexels photo ids."),
      required: TRUE,
    ),
    'published' => new ContextDefinition(
      data_type: 'boolean',
      label: new TranslatableMarkup("Published"),
      description: new TranslatableMarkup("Whether the media entities should be published. Only set when the user specifies it directly or it is very clear from the context. Defaults to TRUE."),
      required: FALSE,
      default_value: TRUE,
    ),
  ],
)]
class DownloadImages extends FunctionCallBase implements ExecutableFunctionCallInterface, AiAgentContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The Pexels API service.
   *
   * @var \Drupal\pexels_ai\PexelsApi
   */
  protected PexelsApi $pexelsApi;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config factory to get media settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The token service to replace tokens in the file path.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $tokenService;

  /**
   * Load from dependency injection container.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FunctionCallInterface|static {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ai.context_definition_normalizer'),
    );
    $instance->currentUser = $container->get('current_user');
    $instance->pexelsApi = $container->get('pexels_ai.api');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->configFactory = $container->get('config.factory');
    $instance->fileSystem = $container->get('file_system');
    $instance->tokenService = $container->get('token');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // First check so the user has permission to use this function.
    if (!$this->currentUser->hasPermission('use pexels ai')) {
      $this->setOutput('The current user does not have permission to use the Pexels AI image search function.');
      return;
    }

    // Check so that the API key exists.
    if (!$this->pexelsApi->isApiKeySet()) {
      $this->setOutput('The Pexels API key is not configured. Please set it up in the Pexels AI configuration page at /admin/config/pexels_ai/settings.');
      return;
    }

    // The user also needs permission to create media entities.
    if (!$this->currentUser->hasPermission('create media')) {
      $this->setOutput('The current user does not have permission to create media entities, which is required to download images from Pexels.');
      return;
    }

    // Load the media bundle to use from config.
    $config = $this->configFactory->get('pexels_ai.settings');
    $media_bundle = $config->get('media_bundle') ?? 'image';
    $image_field = $config->get('image_field') ?? 'field_media_image';

    // Check so the media bundle exists.
    $media_bundles = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    if (!isset($media_bundles[$media_bundle])) {
      $this->setOutput("The media bundle '$media_bundle' configured in Pexels AI settings does not exist. Please check the configuration at /admin/config/pexels_ai/settings.");
      return;
    }

    // Check so the image field exists on this bundle.
    /** @var \Drupal\field\Entity\FieldConfig[] $fields */
    $fields = $this->entityTypeManager->getStorage('field_config')->loadByProperties([
      'entity_type' => 'media',
      'bundle' => $media_bundle,
      'field_name' => $image_field,
    ]);
    if (empty($fields)) {
      $this->setOutput("The image field '$image_field' configured in Pexels AI settings does not exist on the media bundle '$media_bundle'. Please check the configuration at /admin/config/pexels_ai/settings.");
      return;
    }

    // Get the uri scheme for the image field.
    $field_config = reset($fields);
    $settings = $field_config->getSettings();
    $uri_scheme = $settings['uri_scheme'] ?? 'public';
    $directory = $settings['file_directory'] ?? '';
    $directory = $uri_scheme . '://' . $directory;
    $directory = $this->tokenService->replace($directory);
    if (!is_dir($directory)) {
      // Try to create the directory.
      if (!$this->fileSystem->mkdir($directory, NULL, TRUE)) {
        $this->setOutput("The directory '$directory' for the image field does not exist and could not be created. Cannot download images.");
        return;
      }
    }

    // Get a temporary file path to download the images to.
    $tmp_path = $this->fileSystem->realpath('temporary://pexels_ai');
    if (!is_dir($tmp_path)) {
      // Try to create the directory.
      if (!$this->fileSystem->mkdir($tmp_path, NULL, TRUE)) {
        $this->setOutput("The temporary directory '$tmp_path' could not be created. Cannot download images.");
        return;
      }
    }

    // Get the input values.
    $ids = $this->getContextValue('ids');
    $published = $this->getContextValue('published');
    if ($published === NULL) {
      $published = TRUE;
    }
    $output_medias = [];
    foreach ($ids as $id) {
      // Download the image data from Pexels.
      /** @var \Drupal\pexels_ai\Response\Photo $image_data */
      $image_data = $this->pexelsApi->getPhoto((int) $id);
      if ($image_data) {
        $original_url = $image_data->getSrc()->getOriginal();
        if (empty($original_url)) {
          $this->setOutput("Could not get original image URL for image with id $id.");
          continue;
        }
        // Get the file name from the URL.
        $file_name = basename(parse_url($original_url, PHP_URL_PATH));

        // Download the image to a file in the correct directory.
        $tmp_file = $tmp_path . '/' . $file_name;
        file_put_contents($tmp_file, file_get_contents($original_url));

        $file_path = $directory . '/' . $file_name;
        try {
          $file_path = $this->fileSystem->copy($tmp_file, $file_path, FileSystemInterface::EXISTS_RENAME);
        }
        catch (\Exception $e) {
          $this->setOutput("Could not download image with id $id from URL $original_url. Error: " . $e->getMessage());
          continue;
        }

        // Create a file entity for the downloaded file.
        $file = $this->entityTypeManager->getStorage('file')->create([
          'uri' => $file_path,
          'status' => 1,
        ]);
        $file->save();
        // Create a media entity for the image.
        $media = $this->entityTypeManager->getStorage('media')->create([
          'bundle' => $media_bundle,
          'name' => $image_data->getAlt() ?? 'Pexels: ' . $image_data->getId(),
          'status' => $published ? 1 : 0,
          $image_field => [
            'target_id' => $file->id(),
            'alt' => $image_data->getAlt() ?: 'Pexels Image ' . $image_data->getId(),
            'title' => $image_data->getPhotographer(),
          ],
        ]);
        $media->save();
        // Remove the temporary file.
        @unlink($tmp_file);
        $output_medias[] = [
          'media_id' => $media->id(),
          'media_uuid' => $media->uuid(),
          'media_name' => $media->label(),
          'alt' => $media->get($image_field)->alt,
        ];
        $this->setOutput("Downloaded and created media entity with ID " . $media->id() . " for Pexels image ID " . $image_data->getId() . ".");
      }
      else {
        // If we could not get the image data, return an error message.
        $this->setOutput("Could not download image with id $id. It may not exist.");
      }
    }
    $this->setOutput(Yaml::dump($output_medias, 10, 2));
  }

}
