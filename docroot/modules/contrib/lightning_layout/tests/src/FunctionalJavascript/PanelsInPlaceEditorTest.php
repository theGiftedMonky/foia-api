<?php

namespace Drupal\Tests\lightning_layout\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\lightning_layout\Traits\PanelsIPETrait;

/**
 * Tests Lightning Layout's integration with the Panels in-place editor.
 *
 * @group lightning_layout
 * @group orca_public
 */
class PanelsInPlaceEditorTest extends WebDriverTestBase {

  use PanelsIPETrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_landing_page',
    'lightning_roles',
    'views',
  ];

  /**
   * Tests that one-off entity-specific customizations can be done OOTB.
   */
  public function testPerEntityCustomization() {
    $assert_session = $this->assertSession();

    // The 'access user profiles' permission is needed to see the view we are
    // going to place in the layout.
    $account = $this->drupalCreateUser(['access user profiles']);
    $account->addRole('landing_page_creator');
    $account->save();
    $this->drupalLogin($account);

    $node = $this->drupalCreateNode(['type' => 'landing_page']);
    $this->drupalGet($node->toUrl());

    $plugin_id = 'views_block:who_s_online-who_s_online_block';
    $this->getBlockForm($plugin_id, 'Lists (Views)')
      ->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $this->saveLayout();
    $this->getSession()->reload();
    $assert_session->elementExists('css', "[data-block-plugin-id='$plugin_id']");
  }

  /**
   * Tests changing layouts in the in-place editor.
   */
  public function testChangeLayouts() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('landing_page_creator');
    $account->save();
    $this->drupalLogin($account);

    $node = $this->drupalCreateNode(['type' => 'landing_page']);
    $this->drupalGet($node->toUrl());

    $this->changeLayout('Columns: 3', 'layout_threecol_25_50_25');
    $assert_session->pageTextContains('Region: first');
    $assert_session->pageTextContains('Region: second');
    $assert_session->pageTextContains('Region: third');

    $this->changeLayout('Columns: 2', 'layout_twocol');
    $assert_session->pageTextContains('Region: first');
    $assert_session->pageTextContains('Region: second');
    $assert_session->pageTextNotContains('Region: third');
  }

  /**
   * Changes the layout of the current entity in the Panels IPE.
   *
   * @param string $category
   *   The layout category, e.g. "Columns: 3".
   * @param string $layout_id
   *   The layout's plugin ID.
   */
  private function changeLayout($category, $layout_id) {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $page->clickLink('Change Layout');
    $this->waitForVisibleElement('css', "a[data-category='$category']")->click();
    $this->waitForVisibleElement('css', "a[data-layout-id='$layout_id']")->click();
    $assert_session->assertWaitOnAjaxRequest();

    // If the layout is configurable (as it may be in Drupal 8.8 and later), we
    // need to click another button.
    $layout_class = $this->container->get('plugin.manager.core.layout')
      ->getDefinition($layout_id)
      ->getClass();

    if (is_a($layout_class, '\Drupal\Core\Plugin\PluginFormInterface', TRUE)) {
      $page->pressButton('Change Layout');
      $assert_session->assertWaitOnAjaxRequest();
    }
  }

  /**
   * Waits for the specified selector to be visible.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   */
  private function waitForVisibleElement($selector, $locator) {
    $element = $this->assertSession()->waitForElementVisible($selector, $locator);
    $this->assertNotEmpty($element);
    return $element;
  }

}
