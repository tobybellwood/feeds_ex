<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Feeds\Parser\QueryPathXmlParser.
 */

namespace Drupal\feeds_ex\Feeds\Parser;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;

/**
 * Defines a XML parser using QueryPath.
 *
 * @FeedsParser(
 *   id = "querypathxml",
 *   title = @Translation("QueryPath XML"),
 *   description = @Translation("Parse XML with QueryPath.")
 * )
 */
class QueryPathXmlParser extends XmlParser {

  /**
   * Options passed to QueryPath.
   *
   * @var array
   */
  protected $queryPathOptions = array(
    'ignore_parser_warnings' => TRUE,
    'use_parser' => 'xml',
    'strip_low_ascii' => FALSE,
    'replace_entities' => FALSE,
    'omit_xml_declaration' => TRUE,
    'encoding' => 'UTF-8',
  );

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $document = $this->prepareDocument($feed, $fetcher_result);
    $parser = new QueryPath($document, NULL, $this->queryPathOptions);
    $query_path = $parser->find($this->config['context']['value']);

    $state = $feed->state(FEEDS_PARSE);

    if (!$state->total) {
      $state->total = $query_path->size();
    }

    $start = (int) $state->pointer;
    $state->pointer = $start + $feed->importer->getLimit();
    $state->progress($state->total, $state->pointer);

    return $query_path->slice($start, $feed->importer->getLimit());
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $result = new QueryPath($row, $expression, $this->queryPathOptions);

    if ($result->size() == 0) {
      return;
    }

    $config = $this->config['sources'][$machine_name];

    $return = array();

    if (strlen($config['attribute'])) {
      foreach ($result as $node) {
        $return[] = $node->attr($config['attribute']);
      }
    }
    elseif (!empty($config['inner'])) {
      foreach ($result as $node) {
        $return[] = $node->innerXML();
      }
    }
    elseif (!empty($config['raw'])) {
      foreach ($result as $node) {
        $return[] = $this->getRawValue($node);
      }
    }
    else {
      foreach ($result as $node) {
        $return[] = $node->text();
      }
    }

    // Return a single value if there's only one value.
    return count($return) === 1 ? reset($return) : $return;
  }

  /**
   * Returns the raw value.
   *
   * @param QueryPath $node
   *   The QueryPath object to return a raw value for.
   *
   * @return string
   *   A raw string value.
   */
  protected function getRawValue(QueryPath $node) {
    return $node->xml();
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $expression = trim($expression);
    if (!$expression) {
      return;
    }
    try {
      $parser = new QueryPath(NULL, $expression);
    }
    catch (CSSParseException $e) {
      return check_plain($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function configFormTableHeader() {
    return array(
      'attribute' => t('Attribute'),
    ) + parent::configFormTableHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function configFormTableColumn(FormStateInterface $form_state, array $values, $column, $machine_name) {
    switch ($column) {
      case 'attribute':
        return array(
          '#type' => 'textfield',
          '#title' => t('Attribute name'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($values['attribute']) ? $values['attribute'] : '',
          '#size' => 10,
          '#maxlength' => 1024,
        );

      default:
        return parent::configFormTableColumn($form_state, $values, $column, $machine_name);
    }
  }

}
