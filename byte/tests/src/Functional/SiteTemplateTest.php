<?php

declare(strict_types=1);

namespace Drupal\Tests\byte\Functional;

use Composer\InstalledVersions;
use Drupal\canvas\Entity\Component;
use Drupal\canvas\JsonSchemaDefinitionsStreamwrapper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

#[Group('byte')]
#[IgnoreDeprecations]
final class SiteTemplateTest extends BrowserTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // Sitemap module has a config schema problem that is not yet resolved.
    'canvas.component.block.sitemap_syndicate',
  ];

  public function testSiteTemplate(): void {
    $dir = InstalledVersions::getInstallPath('drupal/byte');
    // This is a site template, and therefore meant to be applied only once.
    $this->applyRecipe($dir);

    // Ensure all landing pages are accessible at their expected paths.
    $assert_session = $this->assertSession();
    $paths = ['/home', '/features', '/pricing', '/resources'];
    array_walk($paths, function (string $path) use ($assert_session): void {
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);
    });

    // Ensure all content is renderable without errors.
    $content = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('node')
      ->loadMultiple();
    /** @var \Drupal\node\NodeInterface $node */
    foreach ($content as $node) {
      $this->drupalGet($node->toUrl());
      $this->assertLessThan(500, $this->getSession()->getStatusCode());
    }

    $disabled_components = Component::loadMultiple([
      'block.sitemap',
      'block.sitemap_syndicate',
    ]);
    foreach ($disabled_components as $id => $component) {
      $this->assertFalse($component->status(), "Component $id is enabled but it shouldn't be.");
    }

    $enabled_components = Component::loadMultiple([
      'block.system_menu_block.social',
      'block.system_menu_block.utility',
    ]);
    $this->assertCount(2, $enabled_components);
    foreach ($enabled_components as $id => $component) {
      $this->assertTrue($component->status(), "Component $id is disabled but it shouldn't be.");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function rebuildAll(): void {
    // The rebuild won't succeed without the `json-schema-definitions` stream
    // wrapper. This would normally happen automatically whenever a module is
    // installed, but in this case, all of that has taken place in a separate
    // process, so we need to refresh this process manually.
    // @see canvas_module_preinstall()
    $this->container->get('stream_wrapper_manager')
      ->registerWrapper(
        'json-schema-definitions',
        JsonSchemaDefinitionsStreamwrapper::class,
        JsonSchemaDefinitionsStreamwrapper::getType(),
      );

    parent::rebuildAll();
  }

}
