<?php

namespace Drupal\pexels_ai\Response;

/**
 * Represents a search response from the Pexels Photo API.
 */
class SearchResponse {

  /**
   * An array of Photo objects.
   *
   * @var \Drupal\pexels_ai\Response\Photo[]
   */
  protected array $photos;

  /**
   * The current page number.
   */
  protected int $page;

  /**
   * The number of results returned with each page.
   */
  protected int $perPage;

  /**
   * The total number of results for the request.
   */
  protected int $totalResults;

  /**
   * URL for the previous page of results, if applicable.
   */
  protected ?string $prevPage;

  /**
   * URL for the next page of results, if applicable.
   */
  protected ?string $nextPage;

  /**
   * Constructs a SearchResponse object.
   *
   * @param array $data
   *   The search response data from the API.
   */
  public function __construct(array $data) {
    $this->photos = [];
    if (isset($data['photos']) && is_array($data['photos'])) {
      foreach ($data['photos'] as $photoData) {
        $this->photos[] = new Photo($photoData);
      }
    }

    $this->page = $data['page'] ?? 1;
    $this->perPage = $data['per_page'] ?? 15;
    $this->totalResults = $data['total_results'] ?? 0;
    $this->prevPage = $data['prev_page'] ?? NULL;
    $this->nextPage = $data['next_page'] ?? NULL;
  }

  /**
   * Gets all photos in the response.
   *
   * @return \Drupal\pexels_ai\Response\Photo[]
   *   An array of Photo objects.
   */
  public function getPhotos(): array {
    return $this->photos;
  }

  /**
   * Sets the photos in the response.
   *
   * @param \Drupal\pexels_ai\Response\Photo[] $photos
   *   An array of Photo objects.
   */
  public function setPhotos(array $photos): void {
    $this->photos = $photos;
  }

  /**
   * Gets the total number of results.
   *
   * @return int
   *   The total number of results.
   */
  public function getTotalResults(): int {
    return $this->totalResults;
  }

  /**
   * Sets the total number of results.
   *
   * @param int $totalResults
   *   The total number of results.
   */
  public function setTotalResults(int $totalResults): void {
    $this->totalResults = $totalResults;
  }

  /**
   * Gets the current page number.
   *
   * @return int
   *   The current page number.
   */
  public function getPage(): int {
    return $this->page;
  }

  /**
   * Sets the current page number.
   *
   * @param int $page
   *   The current page number.
   */
  public function setPage(int $page): void {
    $this->page = $page;
  }

  /**
   * Gets the number of results per page.
   *
   * @return int
   *   The number of results per page.
   */
  public function getPerPage(): int {
    return $this->perPage;
  }

  /**
   * Sets the number of results per page.
   *
   * @param int $perPage
   *   The number of results per page.
   */
  public function setPerPage(int $perPage): void {
    $this->perPage = $perPage;
  }

  /**
   * Gets the previous page URL.
   *
   * @return string|null
   *   The previous page URL or NULL if no previous page.
   */
  public function getPrevPage(): ?string {
    return $this->prevPage;
  }

  /**
   * Sets the previous page URL.
   *
   * @param string|null $prevPage
   *   The previous page URL or NULL if no previous page.
   */
  public function setPrevPage(?string $prevPage): void {
    $this->prevPage = $prevPage;
  }

  /**
   * Gets the next page URL.
   *
   * @return string|null
   *   The next page URL or NULL if no next page.
   */
  public function getNextPage(): ?string {
    return $this->nextPage;
  }

  /**
   * Sets the next page URL.
   *
   * @param string|null $nextPage
   *   The next page URL or NULL if no next page.
   */
  public function setNextPage(?string $nextPage): void {
    $this->nextPage = $nextPage;
  }

  /**
   * Checks if there's a next page.
   *
   * @return bool
   *   TRUE if there's a next page, FALSE otherwise.
   */
  public function hasNextPage(): bool {
    return !empty($this->nextPage);
  }

  /**
   * Checks if there's a previous page.
   *
   * @return bool
   *   TRUE if there's a previous page, FALSE otherwise.
   */
  public function hasPrevPage(): bool {
    return !empty($this->prevPage);
  }

  /**
   * Gets the next page URL.
   *
   * @return string|null
   *   The next page URL or NULL if no next page.
   */
  public function getNextPageUrl(): ?string {
    return $this->nextPage;
  }

  /**
   * Gets the previous page URL.
   *
   * @return string|null
   *   The previous page URL or NULL if no previous page.
   */
  public function getPrevPageUrl(): ?string {
    return $this->prevPage;
  }

  /**
   * Gets the first photo in the response.
   *
   * @return \Drupal\pexels_ai\Response\Photo|null
   *   The first photo or NULL if no photos.
   */
  public function getFirstPhoto(): ?Photo {
    return $this->photos[0] ?? NULL;
  }

  /**
   * Gets the number of photos in the current response.
   *
   * @return int
   *   The number of photos.
   */
  public function getPhotoCount(): int {
    return count($this->photos);
  }

  /**
   * Checks if the response is empty.
   *
   * @return bool
   *   TRUE if no photos found, FALSE otherwise.
   */
  public function isEmpty(): bool {
    return empty($this->photos);
  }

  /**
   * Converts the response to an array.
   *
   * @return array
   *   The search response as an array.
   */
  public function toArray(): array {
    $photosArray = [];
    foreach ($this->photos as $photo) {
      $photosArray[] = $photo->toArray();
    }

    return [
      'photos' => $photosArray,
      'page' => $this->page,
      'per_page' => $this->perPage,
      'total_results' => $this->totalResults,
      'prev_page' => $this->prevPage,
      'next_page' => $this->nextPage,
    ];
  }

}
