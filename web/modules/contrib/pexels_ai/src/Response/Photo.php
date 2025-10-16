<?php

namespace Drupal\pexels_ai\Response;

/**
 * Represents a Photo resource from the Pexels API.
 */
class Photo {

  /**
   * The photo ID.
   */
  protected int $id;

  /**
   * The real width of the photo in pixels.
   */
  protected int $width;

  /**
   * The real height of the photo in pixels.
   */
  protected int $height;

  /**
   * The Pexels URL where the photo is located.
   */
  protected string $url;

  /**
   * The name of the photographer who took the photo.
   */
  protected string $photographer;

  /**
   * The URL of the photographer's Pexels profile.
   */
  protected string $photographerUrl;

  /**
   * The ID of the photographer.
   */
  protected int $photographerId;

  /**
   * The average color of the photo.
   */
  protected string $avgColor;

  /**
   * An assortment of different image sizes.
   */
  protected PhotoSrc $src;

  /**
   * Whether the photo is liked.
   */
  protected bool $liked;

  /**
   * Text description of the photo for use in the alt attribute.
   */
  protected string $alt;

  /**
   * Constructs a Photo object.
   *
   * @param array $data
   *   The photo data from the API response.
   */
  public function __construct(array $data) {
    $this->id = $data['id'] ?? 0;
    $this->width = $data['width'] ?? 0;
    $this->height = $data['height'] ?? 0;
    $this->url = $data['url'] ?? '';
    $this->photographer = $data['photographer'] ?? '';
    $this->photographerUrl = $data['photographer_url'] ?? '';
    $this->photographerId = $data['photographer_id'] ?? 0;
    $this->avgColor = $data['avg_color'] ?? '';
    $this->src = new PhotoSrc($data['src'] ?? []);
    $this->liked = $data['liked'] ?? FALSE;
    $this->alt = $data['alt'] ?? '';
  }

  /**
   * Converts the object to an array.
   *
   * @return array
   *   The photo data as an array.
   */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'width' => $this->width,
      'height' => $this->height,
      'url' => $this->url,
      'photographer' => $this->photographer,
      'photographer_url' => $this->photographerUrl,
      'photographer_id' => $this->photographerId,
      'avg_color' => $this->avgColor,
      'src' => $this->src->toArray(),
      'liked' => $this->liked,
      'alt' => $this->alt,
    ];
  }

  /**
   * Gets the photo ID.
   *
   * @return int
   *   The photo ID.
   */
  public function getId(): int {
    return $this->id;
  }

  /**
   * Sets the photo ID.
   *
   * @param int $id
   *   The photo ID.
   */
  public function setId(int $id): void {
    $this->id = $id;
  }

  /**
   * Gets the width of the photo.
   *
   * @return int
   *   The width in pixels.
   */
  public function getWidth(): int {
    return $this->width;
  }

  /**
   * Sets the width of the photo.
   *
   * @param int $width
   *   The width in pixels.
   */
  public function setWidth(int $width): void {
    $this->width = $width;
  }

  /**
   * Gets the height of the photo.
   *
   * @return int
   *   The height in pixels.
   */
  public function getHeight(): int {
    return $this->height;
  }

  /**
   * Sets the height of the photo.
   *
   * @param int $height
   *   The height in pixels.
   */
  public function setHeight(int $height): void {
    $this->height = $height;
  }

  /**
   * Gets the Pexels photo URL.
   *
   * @return string
   *   The Pexels photo URL.
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * Sets the Pexels photo URL.
   *
   * @param string $url
   *   The Pexels photo URL.
   */
  public function setUrl(string $url): void {
    $this->url = $url;
  }

  /**
   * Gets the photographer name.
   *
   * @return string
   *   The photographer name.
   */
  public function getPhotographer(): string {
    return $this->photographer;
  }

  /**
   * Sets the photographer name.
   *
   * @param string $photographer
   *   The photographer name.
   */
  public function setPhotographer(string $photographer): void {
    $this->photographer = $photographer;
  }

  /**
   * Gets the photographer URL.
   *
   * @return string
   *   The photographer URL.
   */
  public function getPhotographerUrl(): string {
    return $this->photographerUrl;
  }

  /**
   * Sets the photographer URL.
   *
   * @param string $photographerUrl
   *   The photographer URL.
   */
  public function setPhotographerUrl(string $photographerUrl): void {
    $this->photographerUrl = $photographerUrl;
  }

  /**
   * Gets the photographer ID.
   *
   * @return int
   *   The photographer ID.
   */
  public function getPhotographerId(): int {
    return $this->photographerId;
  }

  /**
   * Sets the photographer ID.
   *
   * @param int $photographerId
   *   The photographer ID.
   */
  public function setPhotographerId(int $photographerId): void {
    $this->photographerId = $photographerId;
  }

  /**
   * Gets the average color.
   *
   * @return string
   *   The average color.
   */
  public function getAvgColor(): string {
    return $this->avgColor;
  }

  /**
   * Sets the average color.
   *
   * @param string $avgColor
   *   The average color.
   */
  public function setAvgColor(string $avgColor): void {
    $this->avgColor = $avgColor;
  }

  /**
   * Gets the PhotoSrc object.
   *
   * @return PhotoSrc
   *   The PhotoSrc object.
   */
  public function getSrc(): PhotoSrc {
    return $this->src;
  }

  /**
   * Sets the PhotoSrc object.
   *
   * @param PhotoSrc $src
   *   The PhotoSrc object.
   */
  public function setSrc(PhotoSrc $src): void {
    $this->src = $src;
  }

  /**
   * Gets the liked status.
   *
   * @return bool
   *   Whether the photo is liked.
   */
  public function isLiked(): bool {
    return $this->liked;
  }

  /**
   * Sets the liked status.
   *
   * @param bool $liked
   *   Whether the photo is liked.
   */
  public function setLiked(bool $liked): void {
    $this->liked = $liked;
  }

  /**
   * Gets the alt text.
   *
   * @return string
   *   The alt text.
   */
  public function getAlt(): string {
    return $this->alt;
  }

  /**
   * Sets the alt text.
   *
   * @param string $alt
   *   The alt text.
   */
  public function setAlt(string $alt): void {
    $this->alt = $alt;
  }

  /**
   * Gets the original image URL.
   *
   * @return string
   *   The original image URL.
   */
  public function getOriginalUrl(): string {
    return $this->src->getOriginal();
  }

  /**
   * Gets the medium image URL.
   *
   * @return string
   *   The medium image URL.
   */
  public function getMediumUrl(): string {
    return $this->src->getMedium();
  }

  /**
   * Gets the small image URL.
   *
   * @return string
   *   The small image URL.
   */
  public function getSmallUrl(): string {
    return $this->src->getSmall();
  }

}
