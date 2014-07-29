<?php

/**
 * @file
 * Feeds hooks for feeds_ex.
 */

/**
 * Implements hook_feeds_plugins().
 */
function feeds_ex_feeds_plugins() {
  $path = drupal_get_path('module', 'feeds_ex') . '/src';

  return array(
    'FeedsExBase' => array(
      'hidden' => TRUE,
      'handler' => array(
        'parent' => 'FeedsParser',
        'class' => 'FeedsExBase',
        'file' => 'FeedsExBase.inc',
        'path' => $path,
      ),
    ),
    'FeedsExXml' => array(
      'name' => 'XML Xpath parser',
      'description' => 'Parse XML with XPath.',
      'handler' => array(
        'parent' => 'FeedsExBase',
        'class' => 'FeedsExXml',
        'file' => 'FeedsExXml.inc',
        'path' => $path,
      ),
    ),
    'FeedsExHtml' => array(
      'name' => 'HTML Xpath parser',
      'description' => 'Parse HTML with XPath.',
      'handler' => array(
        'parent' => 'FeedsExXml',
        'class' => 'FeedsExHtml',
        'file' => 'FeedsExHtml.inc',
        'path' => $path,
      ),
    ),
  );
}
