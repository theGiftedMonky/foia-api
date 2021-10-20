<?php

namespace Drupal\Tests\lightning_layout\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\lightning_layout\Traits\PanelsIPETrait;

/**
 * Tests Lightning Layout's integration with Panelizer's wizard.
 *
 * @group lightning_layout
 */
class PanelizerWizardTest extends WebDriverTestBase {

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
   * Saving a panelized entity should not affect blocks placed via IPE.
   */
  public function testBlockPlacement() {
    $user = $this->createUser([], NULL, TRUE);
    $page = $this->createNode([
      'type' => 'landing_page',
      'uid' => $user->id(),
    ]);

    $this->drupalLogin($user);
    $this->drupalGet($page->toUrl());

    // Add a Who's online block to the page.
    $this->getBlockForm('views_block:who_s_online-who_s_online_block', 'Lists (Views)')->pressButton('Add');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->saveLayout();
    $this->assertSession()->elementExists('css', '[data-block-plugin-id="views_block:who_s_online-who_s_online_block"]');

    // Save the page via edit form, assert the block is still there.
    $this->drupalGet($page->toUrl('edit-form'));
    $this->assertSession()->buttonExists('Save')->click();
    $this->assertSession()->elementExists('css', '[data-block-plugin-id="views_block:who_s_online-who_s_online_block"]');
  }

  /**
   * Tests switching between defined layouts in the Panelizer wizard.
   */
  public function testChangeLayouts() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('landing_page_creator');
    $account->addRole('layout_manager');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/admin/structure/panelizer/edit/node__landing_page__full__two_column/content');
    $page->clickLink('Add new block');
    $this->waitForLink('Authored by')->click();
    $this->waitForField('region')->selectOption('first');
    $this->waitForButton('Add block')->press();
    $this->assertBlockExistsInRegion('Authored by', 'first');
    $page->pressButton('Update and save');

    $edit_form = $this->drupalCreateNode(['type' => 'landing_page'])
      ->toUrl('edit-form');
    $this->drupalGet($edit_form);
    $page->selectFieldOption('Full content', 'Two Column');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Authored by');
    $this->drupalGet($edit_form);
    $page->selectFieldOption('Full content', 'Single Column');
    $page->pressButton('Save');
    $assert_session->pageTextNotContains('Authored by');
  }

  /**
   * Returns the table row for a specific block in a specific region.
   *
   * @param string $block_label
   *   The label of the block to locate.
   * @param string $region
   *   The machine name of the region in which the block is expected to be.
   */
  private function assertBlockExistsInRegion($block_label, $region) {
    $page = $this->getSession()->getPage();

    // array_map() callback. Traverses from a region select list to the table
    // row that contains it.
    $row_map = function (NodeElement $select) {
      // $select->containing DIV->table cell->table row.
      return $select->getParent()->getParent()->getParent();
    };

    $elements = array_filter(
      $page->findAll('css', 'table#blocks tr > td > div > select.block-region-select'),
      function (NodeElement $element) use ($region) {
        return $element->getValue() == $region;
      }
    );

    /** @var \Behat\Mink\Element\NodeElement $row */
    foreach (array_map($row_map, $elements) as $row) {
      // The first cell is the one with the label; find() will return the first
      // matched element, which should be the first cell.
      $row_label = $row->find('css', 'td')->getText();
      if (trim($row_label) == $block_label) {
        return;
      }
    }
    $this->fail("Expected block '$block_label' to be present in '$region' region.");
  }

  /**
   * Waits for a button with a specified locator.
   *
   * @param string $locator
   *   The button ID, value or alt string.
   */
  private function waitForButton($locator) {
    $button = $this->assertSession()->waitForButton($locator);
    $this->assertNotEmpty($button);
    return $button;
  }

  /**
   * Waits for a link with specified locator.
   *
   * @param string $locator
   *   The link ID, title, text or image alt.
   */
  private function waitForField($locator) {
    $field = $this->assertSession()->waitForField($locator);
    $this->assertNotEmpty($field);
    return $field;
  }

  /**
   * Waits for a link with specified locator.
   *
   * @param string $locator
   *   The link ID, title, text or image alt.
   */
  private function waitForLink($locator) {
    $link = $this->assertSession()->waitForLink($locator);
    $this->assertNotEmpty($link);
    return $link;
  }

}
