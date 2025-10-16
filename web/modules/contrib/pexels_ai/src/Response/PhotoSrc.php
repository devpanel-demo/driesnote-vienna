<?php

namespace Drupal\pexels_ai\Response;

/**
 * Represents the src object of a Photo from the Pexels API.
 */
class PhotoSrc {

  /**
   * The original image URL.
   */
  protected string $original;

  /**
   * The large 2x image URL.
   */
  protected string $large2x;

  /**
   * The large image URL.
   */
  protected string $large;

  /**
   * The medium image URL.
   */
  protected string $medium;

  /**
   * The small image URL.
   */
  protected string $small;

  /**
   * The portrait image URL.
   */
  protected string $portrait;

  /**
   * The landscape image URL.
   */
  protected string $landscape;

  /**
   * The tiny image URL.
   */
  protected string $tiny;

  /**
   * Constructs a PhotoSrc object.
   *
   * @param array $data
   *   The src data from the API response.
   */
  public function __construct(array $data) {
    $this->original = $data['original'] ?? '';
    $this->large2x = $data['large2x'] ?? '';
    $this->large = $data['large'] ?? '';
    $this->medium = $data['medium'] ?? '';
    $this->small = $data['small'] ?? '';
    $this->portrait = $data['portrait'] ?? '';
    $this->landscape = $data['landscape'] ?? '';
    $this->tiny = $data['tiny'] ?? '';
  }

  /**
   * Converts the object to an array.
   *
   * @return array
   *   The src data as an array.
   */
  public function toArray(): array {
    return [
      'original' => $this->original,
      'large2x' => $this->large2x,
      'large' => $this->large,
      'medium' => $this->medium,
      'small' => $this->small,
      'portrait' => $this->portrait,
      'landscape' => $this->landscape,
      'tiny' => $this->tiny,
    ];
  }

  /**
   * Gets the original image URL.
   *
   * @return string
   *   The original image URL.
   */
  public function getOriginal(): string {
    return $this->original;
  }

  /**
   * Sets the original image URL.
   *
   * @param string $original
   *   The original image URL.
   */
  public function setOriginal(string $original): void {
    $this->original = $original;
  }

  /**
   * Gets the large 2x image URL.
   *
   * @return string
   *   The large 2x image URL.
   */
  public function getLarge2x(): string {
    return $this->large2x;
  }

  /**
   * Sets the large 2x image URL.
   *
   * @param string $large2x
   *   The large 2x image URL.
   */
  public function setLarge2x(string $large2x): void {
    $this->large2x = $large2x;
  }

  /**
   * Gets the large image URL.
   *
   * @return string
   *   The large image URL.
   */
  public function getLarge(): string {
    return $this->large;
  }

  /**
   * Sets the large image URL.
   *
   * @param string $large
   *   The large image URL.
   */
  public function setLarge(string $large): void {
    $this->large = $large;
  }

  /**
   * Gets the medium image URL.
   *
   * @return string
   *   The medium image URL.
   */
  public function getMedium(): string {
    return $this->medium;
  }

  /**
   * Sets the medium image URL.
   *
   * @param string $medium
   *   The medium image URL.
   */
  public function setMedium(string $medium): void {
    $this->medium = $medium;
  }

  /**
   * Gets the small image URL.
   *
   * @return string
   *   The small image URL.
   */
  public function getSmall(): string {
    return $this->small;
  }

  /**
   * Sets the small image URL.
   *
   * @param string $small
   *   The small image URL.
   */
  public function setSmall(string $small): void {
    $this->small = $small;
  }

  /**
   * Gets the portrait image URL.
   *
   * @return string
   *   The portrait image URL.
   */
  public function getPortrait(): string {
    return $this->portrait;
  }

  /**
   * Sets the portrait image URL.
   *
   * @param string $portrait
   *   The portrait image URL.
   */
  public function setPortrait(string $portrait): void {
    $this->portrait = $portrait;
  }

  /**
   * Gets the landscape image URL.
   *
   * @return string
   *   The landscape image URL.
   */
  public function getLandscape(): string {
    return $this->landscape;
  }

  /**
   * Sets the landscape image URL.
   *
   * @param string $landscape
   *   The landscape image URL.
   */
  public function setLandscape(string $landscape): void {
    $this->landscape = $landscape;
  }

  /**
   * Gets the tiny image URL.
   *
   * @return string
   *   The tiny image URL.
   */
  public function getTiny(): string {
    return $this->tiny;
  }

  /**
   * Sets the tiny image URL.
   *
   * @param string $tiny
   *   The tiny image URL.
   */
  public function setTiny(string $tiny): void {
    $this->tiny = $tiny;
  }

}
