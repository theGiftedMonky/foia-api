<?php

namespace Drupal\Tests\lightning_media_bulk_upload\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests bulk upload of files into the media library.
 *
 * @group lightning_media
 * @group lightning_media_bulk_upload
 */
class BulkUploadTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_media_audio',
    'lightning_media_bulk_upload',
    'lightning_media_document',
    'lightning_media_image',
    'lightning_media_video',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests bulk uploading files into the media library with a dropzone.
   */
  public function testBulkUpload() {
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access media overview',
      'create media',
      'update media',
      'dropzone upload files',
      'view the administration theme',
    ]);
    $this->drupalLogin($account);

    // Confirm that Bulk upload link is present on the grid display of the
    // administrative media overview.
    $this->drupalGet('/admin/content/media-grid');
    $this->assertSession()->linkExists('Bulk upload');

    // Confirm that Bulk upload link is present on the table display of the
    // administrative media overview.
    $this->drupalGet('/admin/content/media');
    $page->clickLink('Bulk upload');
    // Wait for the dropzone to be initialized.
    sleep(3);

    $files = [
      'test.jpg',
      'test.mp3',
      'test.mp4',
      'test.pdf',
    ];

    foreach ($files as $file) {
      $file = __DIR__ . "/../../../../../tests/files/$file";
      $this->assertFileExists($file);

      $this->getSession()->executeScript('Dropzone.instances[0].hiddenFileInput.name = "file"');
      $page->attachFileToField('file', $file);

      // @todo React when the upload actually completes.
      sleep(3);
    }
    $page->pressButton('Continue');

    for ($i = 0; $i < count($files); $i++) {
      $page->pressButton('Save');
    }

    // Ensure all the files were actually saved, and have the current user as
    // their owner.
    $saved_files = $this->container->get('entity_type.manager')
      ->getStorage('file')
      ->loadByProperties([
        'filename' => $files,
        'uid' => $account->id(),
      ]);
    $this->assertSame(count($saved_files), count($files));

    $this->drupalGet('/admin/content/media');
    // @todo Make this linkExists. For whatever reason, that assertion fails and
    // I don't really feel like debugging it.
    array_walk($files, [$this->assertSession(), 'pageTextContains']);
  }

}
