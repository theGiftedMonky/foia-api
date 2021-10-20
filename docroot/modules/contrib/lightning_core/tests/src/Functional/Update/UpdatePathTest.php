<?php

namespace Drupal\Tests\lightning_core\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\NodeType;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests Lightning Core's database update path.
 *
 * @group lightning_core
 */
class UpdatePathTest extends UpdatePathTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/2.0.0-updated-drupal-8.8.0.php.gz',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    // Remove Workflow and Menu UI-related settings from the Page content type.
    NodeType::load('page')
      ->unsetThirdPartySetting('lightning_workflow', 'workflow')
      ->unsetThirdPartySetting('menu_ui', 'available_menus')
      ->unsetThirdPartySetting('menu_ui', 'parent')
      ->save();
  }

  /**
   * Tests that update path completes without errors in the UI.
   */
  public function testUpdatePath() : void {
    $this->runUpdates();
    $this->drush('update:lightning', [], ['yes' => NULL]);
  }

}
