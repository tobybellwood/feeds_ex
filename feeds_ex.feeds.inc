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

  // In some cases, the module is not loaded yet when this include gets called.
  // @todo Figure out why.
  drupal_load('module', 'feeds_ex');

  $plugins = array(
    'Base' => array(
      'hidden' => TRUE,
      'handler' => array(
        'parent' => 'FeedsParser',
        'class' => 'Base',
        'file' => 'Base.inc',
        'path' => $path,
      ),
    ),
    'Xml' => array(
      'name' => 'XML Xpath parser',
      'description' => 'Parse XML with XPath.',
      'handler' => array(
        'parent' => 'Base',
        'class' => 'Xml',
        'file' => 'Xml.inc',
        'path' => $path,
      ),
    ),
    'Html' => array(
      'name' => 'HTML Xpath parser',
      'description' => 'Parse HTML with XPath.',
      'handler' => array(
        'parent' => 'Xml',
        'class' => 'Html',
        'file' => 'Html.inc',
        'path' => $path,
      ),
    ),
  );

  if (feeds_ex_library_path('jmespath.php', 'vendor/autoload.php')) {
    $plugins['JmesPath'] = array(
      'name' => 'JSON JMESPath parser',
      'description' => 'Parse JSON with JMESPath.',
      'handler' => array(
        'parent' => 'Base',
        'class' => 'JmesPath',
        'file' => 'JmesPath.inc',
        'path' => $path,
      ),
    );
    $plugins['JmesPathLines'] = array(
      'name' => 'JSON Lines JMESPath parser',
      'description' => 'Parse JSON Lines with JMESPath.',
      'handler' => array(
        'parent' => 'JmesPath',
        'class' => 'JmesPathLines',
        'file' => 'JmesPathLines.inc',
        'path' => $path,
      ),
    );
  }

  if (\Drupal::moduleHandler()->moduleExists('querypath')) {
    $plugins['QueryPathXml'] = array(
      'name' => 'QueryPath XML parser',
      'description' => 'Parse XML with QueryPath.',
      'handler' => array(
        'parent' => 'Xml',
        'class' => 'QueryPathXml',
        'file' => 'QueryPathXml.inc',
        'path' => $path,
      ),
    );
    $plugins['QueryPathHtml'] = array(
      'name' => 'QueryPath HTML parser',
      'description' => 'Parse HTML with QueryPath.',
      'handler' => array(
        'parent' => 'QueryPathXml',
        'class' => 'QueryPathHtml',
        'file' => 'QueryPathHtml.inc',
        'path' => $path,
      ),
    );
  }

  if (feeds_ex_jsonpath_library_path()) {
    $plugins['JsonPath'] = array(
      'name' => 'JSON JSONPath parser',
      'description' => 'Parse JSON with JSONPath.',
      'handler' => array(
        'parent' => 'Base',
        'class' => 'JsonPath',
        'file' => 'JsonPath.inc',
        'path' => $path,
      ),
    );
    $plugins['JsonPathLines'] = array(
      'name' => 'JSON Lines JSONPath parser',
      'description' => 'Parse JSON Lines with JSONPath.',
      'handler' => array(
        'parent' => 'JsonPath',
        'class' => 'JsonPathLines',
        'file' => 'JsonPathLines.inc',
        'path' => $path,
      ),
    );
  }

  return $plugins;
}
