<?php

namespace Drupal\pexels_ai\Plugin\AiFunctionCall;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\ExecutableFunctionCallInterface;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\ai\Utility\ContextDefinitionNormalizer;
use Drupal\ai_agents\PluginInterfaces\AiAgentContextInterface;
use Drupal\pexels_ai\PexelsApi;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * This function call will help you search images on Pexels.
 */
#[FunctionCall(
  id: 'pexels_ai:search_images_pexels',
  function_name: 'search_pexels_images',
  name: 'Search Pexels Images',
  description: 'This function will search the Pexels database for the search query and return results. What is important when using this is that orientation, size and color should be set using the parameters and not included in the query. The query should be kept short, with no more than 5 words. For example, if the user wants a blue image, do not put "blue" in the query, but use the color parameter instead. Only include words that are essential to the search in the query.',
  group: 'information_tools',
  context_definitions: [
    'query' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Query"),
      description: new TranslatableMarkup("The search words to search images in Pexels for. Keep it short, with no more than 5 words. Colors, size or orientation should use the parameters below and not be included in the query."),
      required: TRUE,
    ),
    'orientation' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Orientation"),
      description: new TranslatableMarkup("The orientation of the images. Only set if the user specifies it directly or it is very clear from the context. One of: landscape, portrait, square. Do not put this in the query."),
      required: FALSE,
      constraints: [
        'AllowedValues' => [
          'landscape',
          'portrait',
          'square',
        ],
      ],
    ),
    'size' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Size"),
      description: new TranslatableMarkup("Minimum photo size. Only set if the user specifies it directly or it is very clear from the context. One of: large (24MP), medium (12MP), small (4MP). Do not put this in the query."),
      required: FALSE,
      constraints: [
        'AllowedValues' => [
          'large',
          'medium',
          'small',
        ],
      ],
    ),
    'color' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Color"),
      description: new TranslatableMarkup("Desired photo color. Supported colors: red, orange, yellow, green, turquoise, blue, violet, pink, brown, black, gray, white or any hexidecimal color code (eg. #ffffff). Do not put this in the query."),
      required: FALSE,
    ),
    'page' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup("Page"),
      description: new TranslatableMarkup("The page number to retrieve. Default is 1."),
      required: FALSE,
      default_value: 1,
    ),
    'per_page' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup("Results Per Page"),
      description: new TranslatableMarkup("The number of results to return per page. Default is 15, maximum is 80."),
      required: FALSE,
      default_value: 15,
    ),
  ],
)]
class SearchImages extends FunctionCallBase implements ExecutableFunctionCallInterface, AiAgentContextInterface {

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

    // Get the input values.
    $query = $this->getContextValue('query');
    $orientation = $this->getContextValue('orientation');
    $size = $this->getContextValue('size');
    $color = $this->getContextValue('color');
    $page = $this->getContextValue('page') ?? 1;
    $per_page = $this->getContextValue('per_page') ?? 15;

    // Prepare options for the API call.
    $options = [];
    if ($orientation) {
      $options['orientation'] = $orientation;
    }
    if ($size) {
      $options['size'] = $size;
    }
    if ($color) {
      $options['color'] = $color;
    }
    $options['page'] = $page;
    $options['per_page'] = $per_page;
    try {
      $response = $this->pexelsApi->searchPhotos($query, $options);
      $photos = [];
      foreach ($response->getPhotos() as $photo) {
        $photos[] = [
          'id' => $photo->getId(),
          'width' => $photo->getWidth(),
          'height' => $photo->getHeight(),
          'url' => $photo->getUrl(),
          'photographer' => $photo->getPhotographer(),
          'photographer_url' => $photo->getPhotographerUrl(),
          'alt' => $photo->getAlt(),
          'src' => [
            // Tiny for preview, and original for link.
            'original' => $photo->getSrc()->getOriginal(),
            'tiny' => $photo->getSrc()->getTiny(),
          ],
        ];
      }
      // Set the output to the array of photos.
      $this->setOutput(Yaml::dump($photos, 10, 2));
    }
    catch (\Exception $e) {
      // Handle exceptions, such as API errors.
      $this->setOutput('An error occurred while searching for images: ' . $e->getMessage());
    }
  }

}
