<?php

/**
 * @file
 * Prepares a code base to run database updates for testing.
 *
 * Forcibly uninstalls Lightning Dev, switches the installation profile from
 * Standard to Minimal, and deletes defunct config objects.
 */

use Drupal\node\Entity\NodeType;

$config_factory = Drupal::configFactory();

$config_factory
  ->getEditable('core.extension')
  // openapi_redoc was renamed to openapi_ui_redoc, so we need to delete all
  // mention of it from the database.
  ->clear('module.openapi_redoc')
  ->save();

Drupal::keyValue('system.schema')->delete('openapi_redoc');

$config_factory
  ->getEditable('entity_browser.browser.media_browser')
  ->delete();

$config_factory->getEditable('media.type.tweet')->delete();

Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

// Delete all configuration associated with the Page content type, since certain
// Behat fixture contexts reinstall Lightning Page.
$node_type = NodeType::load('page');
if ($node_type) {
  $node_type->delete();
}

user_role_revoke_permissions('authenticated', ['use text format basic_html']);
