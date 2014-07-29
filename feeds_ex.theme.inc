<?php

/**
 * @file
 * Theme callbacks for feeds_ex.
 */

/**
 * Themes the parser configuration table.
 */
function theme_feeds_ex_configuration_table(array $variables) {
  $element = $variables['element'];

  $header = array(
    t('Name'),
    t('Machine name'),
    t('Value'),
    t('Raw'),
    t('Debug'),
    t('Remove'),
    t('Weight'),
  );

  $rows = array();

  // Render the context at the top.
  $rows[] = array(
    drupal_render($element['context']['name']),
    '&nbsp;',
    array('data' => drupal_render($element['context']['value'])),
    drupal_render($element['context']['raw']),
    drupal_render($element['context']['debug']),
    '&nbsp;',
    '&nbsp;',
  );

  // Render the sources.
  foreach (element_children($element['sources']) as $source_key) {
    $source = $element['sources'][$source_key];
    $row = array();
    $row[] = drupal_render($element['sources'][$source_key]['name']);
    $row[] = drupal_render($element['sources'][$source_key]['machine_name']);
    $row[] = drupal_render($element['sources'][$source_key]['value']);
    $row[] = drupal_render($element['sources'][$source_key]['raw']);
    $row[] = drupal_render($element['sources'][$source_key]['debug']);
    $row[] = drupal_render($element['sources'][$source_key]['remove']);
    $row[] = drupal_render($element['sources'][$source_key]['weight']);
    $rows[] = array('data' => $row, 'class' => array('draggable'));
  }

  // Render the add row.
  $add_row = array(
    drupal_render($element['add']['name']),
    drupal_render($element['add']['machine_name']),
    drupal_render($element['add']['value']),
    drupal_render($element['add']['raw']),
    drupal_render($element['add']['debug']),
    '&nbsp;',
    drupal_render($element['add']['weight']),
  );
  $rows[] = array('data' => $add_row, 'class' => array('draggable'));

  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => $element['sources']['#id'])));
  $output .= drupal_render_children($element);
  return $output;
}
