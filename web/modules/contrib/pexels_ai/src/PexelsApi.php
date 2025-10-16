<?php

namespace Drupal\pexels_ai;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\pexels_ai\Response\Photo;
use Drupal\pexels_ai\Response\SearchResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Pexels API.
 */
class PexelsApi {

  /**
   * The base host.
   */
  protected string $baseHost = 'https://api.pexels.com/';

  /**
   * Constructs a new Pexels API object.
   *
   * @param \GuzzleHttp\Client $client
   *   Http client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected Client $client,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    // We try to setup the API Key from config.
    $config = $this->configFactory->get('pexels_ai.settings');
    $api_key = $config->get('api_key');
    if ($api_key) {
      // Load the Key.
      /** @var \Drupal\key\Entity\Key $key_entity */
      $key_entity = $this->entityTypeManager->getStorage('key')->load($api_key);
      $this->setApiKey($key_entity->getKeyValue());
    }
  }

  /**
   * Sets connect data.
   *
   * @param string $baseUrl
   *   The base url.
   */
  public function setConnectData($baseUrl) {
    $this->baseHost = $baseUrl;
  }

  /**
   * Sets the API key.
   *
   * @param string $apiKey
   *   The API key.
   */
  public function setApiKey($apiKey) {
    $this->client = new Client([
      'headers' => [
        'Authorization' => $apiKey,
      ],
    ]);
  }

  /**
   * Gets if a API key is set.
   *
   * @return bool
   *   TRUE if set, FALSE otherwise.
   */
  public function isApiKeySet(): bool {
    try {
      $response = $this->client->request('GET', rtrim($this->baseHost, '/') . '/v1/curated', [
        'query' => ['per_page' => 1],
        'connect_timeout' => 10,
        'timeout' => 10,
      ]);
      return $response->getStatusCode() === 200;
    }
    catch (GuzzleException $e) {
      return FALSE;
    }
  }

  /**
   * Search for photos on Pexels.
   *
   * @param string $query
   *   The search query (e.g., 'Nature', 'Tigers', 'People').
   * @param array $options
   *   Optional parameters:
   *   - orientation: 'landscape', 'portrait', or 'square'
   *   - size: 'large' (24MP), 'medium' (12MP), or 'small' (4MP)
   *   - color: Color name or hex code (e.g., 'red', '#ffffff')
   *   - locale: Locale code (e.g., 'en-US')
   *   - page: Page number (default: 1)
   *   - per_page: Results per page (default: 15, max: 80)
   *
   * @return \Drupal\pexels_ai\Response\SearchResponse
   *   The search response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If the API request fails.
   */
  public function searchPhotos(string $query, array $options = []): SearchResponse {
    $params = ['query' => $query];

    // Add optional parameters if provided.
    $validOptions = ['orientation', 'size', 'color', 'locale', 'page', 'per_page'];
    foreach ($validOptions as $option) {
      if (isset($options[$option])) {
        $params[$option] = $options[$option];
      }
    }

    $response = $this->makeRequest('v1/search', $params);
    $data = Json::decode($response);

    return new SearchResponse($data);
  }

  /**
   * Get information about a specific photo.
   *
   * @param int $photoId
   *   The ID of the photo to retrieve.
   *
   * @return \Drupal\pexels_ai\Response\Photo
   *   The photo object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If the API request fails.
   */
  public function getPhoto(int $photoId): Photo {
    $response = $this->makeRequest("v1/photos/{$photoId}");
    $data = Json::decode($response);

    return new Photo($data);
  }

  /**
   * Get curated photos from Pexels.
   *
   * @param array $options
   *   Optional parameters:
   *   - page: Page number (default: 1)
   *   - per_page: Results per page (default: 15, max: 80)
   *
   * @return \Drupal\pexels_ai\Response\SearchResponse
   *   The curated photos response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If the API request fails.
   */
  public function getCuratedPhotos(array $options = []): SearchResponse {
    $params = [];

    // Add optional parameters if provided.
    $validOptions = ['page', 'per_page'];
    foreach ($validOptions as $option) {
      if (isset($options[$option])) {
        $params[$option] = $options[$option];
      }
    }

    $response = $this->makeRequest('v1/curated', $params);
    $data = Json::decode($response);

    return new SearchResponse($data);
  }

  /**
   * Download a photo from a given URL.
   *
   * @param string $photoUrl
   *   The URL of the photo to download.
   * @param string $destination
   *   The local file path where to save the photo.
   *
   * @return bool
   *   TRUE if the download was successful, FALSE otherwise.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If the download request fails.
   */
  public function downloadPhoto(string $photoUrl, string $destination): bool {
    try {
      // Create a new client without authorization for downloading.
      $downloadClient = new Client();

      $response = $downloadClient->request('GET', $photoUrl, [
        'sink' => $destination,
        'connect_timeout' => 30,
        'timeout' => 120,
      ]);

      return $response->getStatusCode() === 200;
    }
    catch (GuzzleException $e) {
      // Log the error or handle it as needed.
      return FALSE;
    }
  }

  /**
   * Download a photo by Photo object.
   *
   * @param \Drupal\pexels_ai\Response\Photo $photo
   *   The photo object.
   * @param string $destination
   *   The local file path where to save the photo.
   * @param string $size
   *   The size to download (original, large, medium, small, etc.).
   *
   * @return bool
   *   TRUE if the download was successful, FALSE otherwise.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If the download request fails.
   */
  public function downloadPhotoFromObject(Photo $photo, string $destination, string $size = 'original'): bool {
    $src = $photo->getSrc();

    $photoUrl = match($size) {
      'large2x' => $src->getLarge2x(),
      'large' => $src->getLarge(),
      'medium' => $src->getMedium(),
      'small' => $src->getSmall(),
      'portrait' => $src->getPortrait(),
      'landscape' => $src->getLandscape(),
      'tiny' => $src->getTiny(),
      default => $src->getOriginal(),
    };

    if (empty($photoUrl)) {
      return FALSE;
    }

    return $this->downloadPhoto($photoUrl, $destination);
  }

  /**
   * Get the rate limit information from the last response headers.
   *
   * @return array
   *   Array containing rate limit information:
   *   - limit: Total request limit for the monthly period
   *   - remaining: Remaining requests
   *   - reset: UNIX timestamp when the period resets
   */
  public function getRateLimitInfo(): array {
    // This would need to be implemented to store headers from the last
    // response. For now, return empty array as placeholder.
    return [
      'limit' => NULL,
      'remaining' => NULL,
      'reset' => NULL,
    ];
  }

  /**
   * Make Pexels call.
   *
   * @param string $path
   *   The path.
   * @param array $query_string
   *   The query string.
   * @param string $method
   *   The method.
   * @param string $body
   *   Data to attach if POST/PUT/PATCH.
   * @param array $options
   *   Extra headers.
   *
   * @return string|object
   *   The return response.
   */
  protected function makeRequest($path, array $query_string = [], $method = 'GET', $body = '', array $options = []) {
    // Don't wait to long if its model.
    if ($path == 'api/tags') {
      $options['connect_timeout'] = 5;
      $options['timeout'] = 5;
    }
    else {
      $options['connect_timeout'] = 120;
      $options['read_timeout'] = 120;
      $options['timeout'] = 120;
    }

    // JSON unless its multipart.
    if (empty($options['multipart'])) {
      $options['headers']['Content-Type'] = 'application/json';
    }
    if ($body) {
      $options['body'] = Json::encode($body);
    }

    $new_url = rtrim($this->baseHost, '/') . '/' . $path;
    $new_url .= count($query_string) ? '?' . http_build_query($query_string) : '';

    $res = $this->client->request($method, $new_url, $options);

    return $res->getBody();
  }

}
