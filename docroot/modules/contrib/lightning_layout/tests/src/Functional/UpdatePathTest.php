<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests Lightning Layout's database update path.
 *
 * @group lightning_layout
 * @group lightning
 */
class UpdatePathTest extends UpdatePathTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/1.0.0-rc1.php.gz',
    ];
  }

  /**
   * Tests Lightning Layout's database update path.
   */
  public function testUpdatePath() {
    require_once __DIR__ . '/../../update.php';
    $this->runUpdates();
    $this->drush('update:lightning', [], ['yes' => NULL]);
  }

}
